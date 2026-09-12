# 🏗️ 가비아 프로덕션 서버 운영 레퍼런스

> [!IMPORTANT]
> **서버 인프라(디스크·NAS·보안 패치·이력)의 SSOT는 [gabia-server.md](gabia-server.md)입니다.**
> 디스크 구조, fstab, 커널·패키지 관리, 주요 이력 등은 반드시 그 문서를 먼저 확인하세요.
> 이 문서는 Docker/Nginx/XE 애플리케이션 계층의 운영 절차에 집중합니다.
>
> 최종 업데이트: 2026-09-10

---

## 1. 서버 아키텍처 (Quick Reference)

| 항목 | 값 |
|---|---|
| 호스트 IP | `45.115.154.229` (SSH 포트 `2222`) |
| SSH 명령 | `ssh -p 2222 wonhyukc@45.115.154.229` (별칭: `sshy`) |
| OS | Ubuntu 24.04.5 LTS (가비아 클라우드) |
| 디스크 구조 | vda 100GB (OS) + vdb 300GB (`/data`) + NAS 1TB (`/nas`) → [gabia-server.md](gabia-server.md) 참조 |

---

## 2. Docker 컨테이너 맵

| 컨테이너 이름 | 이미지 | 포트 | 역할 | Compose 위치 |
|---|---|---|---|---|
| `hyanglin-home-src-web-1` | `hyanglin-home-src-web` | `8080→80` | PHP 5.6 XE 메인 홈페이지 | `~/hyanglin-home-src` |
| `hyanglin-home-infra-nginx-1` | `nginx:alpine` | `80,443` | HTTPS 프록시 + SSL | `~/hyanglin-home-infra` |
| `hyanglin-home-infra-legacy-mysql-1` | `mysql:5.7` | `3307→3306` | XE 데이터베이스 | `~/hyanglin-home-infra` |
| `hyanglin-home-infra-certbot-1` | `certbot/certbot` | - | Let's Encrypt 인증서 | `~/hyanglin-home-infra` |

---

## 3. 볼륨 마운트 (중요!)

> [!IMPORTANT]
> XE 소스 코드는 컨테이너 내부가 아니라 **호스트 바인드 마운트**입니다.
> 컨테이너를 재빌드/재시작해도 소스 코드는 유지됩니다.

- 호스트: `/var/www/hyanglin-home-src/src` → 컨테이너: `/var/www/html`
- php.ini: `/var/www/hyanglin-home-src/php.ini` → `/usr/local/etc/php/php.ini`

---

## 4. Nginx 설정 파일 위치

| 파일 | 호스트 경로 | 용도 |
|---|---|---|
| `default.conf` | `/home/wonhyukc/hyanglin-home-infra/nginx/conf.d/default.conf` | 메인 HTTPS 프록시, CSP, sub_filter, 로그 포맷 |
| `blockips.conf` | `/home/wonhyukc/hyanglin-home-infra/nginx/conf.d/blockips.conf` | 악성 IP 차단 목록 |
| `finance.conf` | `/home/wonhyukc/hyanglin-home-infra/nginx/conf.d/finance.conf` | 재정관리 시스템 프록시 |

**Nginx 로그 파일 위치 (호스트 마운트, `/data/log/nginx/`):**

| 파일 | 내용 |
|---|---|
| `/data/log/nginx/access.log` | 전체 접속 로그 (IP, URL, 상태코드, UA, 응답시간) |
| `/data/log/nginx/error.log` | 에러 및 이상 동작 로그 |
| `/data/log/nginx/access.log-YYYYMMDD.gz` | 14일치 압축 보관 (logrotate 자동 관리) |

```bash
# 실시간 접속 로그 확인
tail -f /data/log/nginx/access.log

# 특정 IP 접속 이력 조회
grep '1.2.3.4' /data/log/nginx/access.log

# 404/403/500 에러만 필터
grep ' 40[34] \| 500 ' /data/log/nginx/access.log | tail -30

# 압축된 과거 로그 조회
zcat /data/log/nginx/access.log-20260910.gz | grep '특정패턴'
```

> [!NOTE]
> logrotate 정책: 매일 로테이션, **14일 보존**, 단일 파일 **100MB 초과 시 즉시 로테이션**, gzip 압축.
> 설정 파일: `/etc/logrotate.d/nginx-docker` (로컬 SSOT: `server-scripts/logrotate-nginx`)

**Nginx 설정 변경 후 적용:**

```bash
sudo docker exec hyanglin-home-infra-nginx-1 nginx -t && sudo docker exec hyanglin-home-infra-nginx-1 nginx -s reload
```

> [!CAUTION]
> 반드시 `nginx -t`로 문법 검증을 먼저 수행한 후 `nginx -s reload`를 실행하세요.

---

## 5. XE 설정 파일

| 파일 | 컨테이너 내 경로 | 호스트 경로 | 용도 |
|---|---|---|---|
| `db.config.php` | `/var/www/html/files/config/db.config.php` | `/var/www/hyanglin-home-src/src/files/config/db.config.php` | DB 접속 정보, `use_db_session`, `use_rewrite` 등 |
| `config.inc.php` | `/var/www/html/config/config.inc.php` | `/var/www/hyanglin-home-src/src/config/config.inc.php` | PHP 세션 설정, 전역 설정 |

**DB 접속 정보:**

| 항목 | 값 |
|---|---|
| Host | `172.18.0.1` (Docker bridge) |
| User | `root` |
| Password | KeePass `KT_MYSQL_PW` (Title: `hyanglin-homepage .env.production`) |
| Database | `hr2` |
| Table prefix | `xe_` |

---

## 6. MySQL 데이터베이스 아키텍처 및 접근 방법 (SSOT)

> [!IMPORTANT]
> **실제 운영 DB는 Docker 컨테이너가 아니라 호스트 머신에 네이티브 서비스로 구동 중인 MySQL 5.7 (포트 3306)입니다.**
> (Docker Compose의 3307 포트 legacy-mysql 컨테이너는 미사용 컨테이너입니다.)

| 접속 위치 | 접속 방식 | 계정 / 비밀번호 | 대상 호스트 | 비고 |
|---|---|---|---|---|
| **호스트 쉘** | `sudo mysql -u root` | `root` (비밀번호 없음) | `localhost` | `auth_socket` 소켓 인증으로 즉시 로그인 |
| **Docker 컨테이너** (PHP) | PDO / mysqli | `root` / **KeePass `KT_MYSQL_PW`** | `172.18.0.1:3306` | Docker bridge 게이트웨이 경유 (`root@172.18.%`) |

> [!NOTE]
> **비밀번호 조회 (KeePassXC)**:
> ```bash
> KT_MYSQL_PW=$(secret-tool lookup Title "hyanglin-homepage .env.production" | grep -E '^KT_MYSQL_PW=' | head -1 | cut -d'=' -f2- | tr -d '"' | tr -d "'")
> ```

> [!NOTE]
> 새 레거시 사이트 DB를 추가할 때:
> 1. 호스트에서 `sudo mysql -u root -e "CREATE DATABASE {db_name} CHARACTER SET utf8;"`
> 2. 호스트에서 `zcat {덤프}.sql.gz | sudo mysql -u root {db_name}`
> 3. `root@172.18.%`에 전체 권한(`GRANT ALL PRIVILEGES ON *.* TO 'root'@'172.18.%';`)이 부여되어 있으므로 컨테이너에서 즉시 접속 가능합니다.

**기본 쿼리 패턴 (테이블명 정상 출력):**
```bash
sudo docker exec hyanglin-home-src-web-1 php -r '
define("__XE__", true);
include("/var/www/html/files/config/db.config.php");
$pdo = new PDO("mysql:host=172.18.0.1;charset=latin1", "root", $db_info->master_db["db_password"]);
$pdo->exec("SET NAMES latin1");
$stmt = $pdo->query("YOUR SQL HERE");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { print_r($row); }
'
```

**한글 테이블명을 포함한 information_schema 조회 시 HEX 패턴:**
```bash
sudo docker exec hyanglin-home-src-web-1 php -r '
define("__XE__", true);
include("/var/www/html/files/config/db.config.php");
$pdo = new PDO("mysql:host=172.18.0.1;charset=latin1", "root", $db_info->master_db["db_password"]);
$pdo->exec("SET NAMES latin1");
foreach($pdo->query("SELECT HEX(table_name) tn, ROUND((data_length+index_length)/1024/1024,1) mb, table_rows FROM information_schema.tables WHERE table_schema=\"hr2\" ORDER BY (data_length+index_length) DESC LIMIT 15")->fetchAll(PDO::FETCH_NUM) as $r)
  echo pack("H*",$r[0])."  ".$r[1]." MB  rows=".$r[2]."\n";
'
```

**특정 DB 데이터 조회 (dbname 명시):**
```bash
sudo docker exec hyanglin-home-src-web-1 php -r '
define("__XE__", true);
include("/var/www/html/files/config/db.config.php");
$pdo = new PDO("mysql:host=172.18.0.1;dbname=hr2;charset=latin1", "root", $db_info->master_db["db_password"]);
$pdo->exec("SET NAMES utf8");
$stmt = $pdo->query("YOUR SQL HERE");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { print_r($row); }
'
```

> [!NOTE]
> `information_schema` 조회 시에는 `charset=latin1` + `SET NAMES latin1` + `HEX()` 패턴을 쓰세요.
> 실제 DB 데이터(게시글 본문 등) 조회 시에는 `dbname=hr2` + `SET NAMES utf8`을 쓰세요.

---

## 7. 메인 페이지 구조 (중요!)

메인 페이지(`www.hyanglin.org`)는 XE page 모듈(`module_srl=66`, `mid=index`)에서 **iframe으로 `main.php`를 임베드**하는 구조입니다:

```
XE 레이아웃 (xe_kimtajo_layout)
 └── XE page (document_srl=91)
     └── <div class="xe_content xe-widget-wrapper" style="height:1215px; float:left">
         └── <iframe id="content_iframe" src="/contents/main.php" style="height:2430px">
     └── <div class="home_list"> (최신 게시글 위젯, margin-top:-405px로 iframe 위에 겹침)
```

**관련 파일:**

| 파일 | 호스트 경로 |
|---|---|
| 레이아웃 | `/var/www/hyanglin-home-src/src/layouts/xe_kimtajo_layout/layout.html` |
| CSS | `/var/www/hyanglin-home-src/src/layouts/xe_kimtajo_layout/css/layout.css` |
| 메인 콘텐츠 | `/var/www/hyanglin-home-src/src/contents/main.php` |
| 위젯 HTML | DB `xe_documents`, `document_srl=91` |

---

## 8. 세션 설정

| 설정 | 값 | 비고 |
|---|---|---|
| `use_db_session` | `Y` | 2026-09-05 `N`→`Y` 변경 |
| session lifetime | 18000초 (5시간) | `session.class.php` `$lifetime` |
| PHP `gc_maxlifetime` | 1440초 (24분) | `use_db_session=Y`이면 무시됨 |
| 자동 로그인 쿠키(`xeak`) | 1년 (31536000초) | `member.controller.php` |

---

## 9. 장애 조사 체크리스트

### 이미지 깨짐 / JS 안 로드

1. Nginx rate limiting 확인:
   ```bash
   grep limit_req /home/wonhyukc/hyanglin-home-infra/nginx/conf.d/default.conf
   ```
2. Nginx 에러 로그 확인:
   ```bash
   tail -50 /data/log/nginx/error.log
   # 또는 실시간
   tail -f /data/log/nginx/access.log | grep ' [45][0-9][0-9] '
   ```
3. HTTP 응답 코드 확인:
   ```bash
   curl -s -o /dev/null -w '%{http_code}' https://www.hyanglin.org/images/icon3.png
   ```

### 디스크 용량 부족

> [!NOTE]
> 서버는 vda(OS) + vdb(`/data`) + NAS(`/nas`) 3중 구조입니다. 자세한 디스크 맵은 [gabia-server.md](gabia-server.md)를 확인하세요.

1. 전체 디스크 확인 (vdb/NAS 포함):
   ```bash
   df -h --include-type=ext4 --include-type=nfs
   ```
2. Docker 용량 (`/data/docker` = `/var/lib/docker` 심볼릭 링크):
   ```bash
   sudo docker system df
   ```
3. MySQL binlog 확인 (PHP PDO):
   ```sql
   SHOW BINARY LOGS
   ```
4. 큰 디렉터리 확인 (`/var/www`는 `/data/www` 심볼릭 링크):
   ```bash
   du -sh /data/* /var/lib/docker/* 2>/dev/null | sort -rh | head -10
   ```

### 메인 페이지 레이아웃 깨짐

1. iframe 높이 확인:
   ```bash
   curl -s https://www.hyanglin.org/ | grep content_iframe
   ```
2. `layout.css` 변경 확인:
   ```bash
   stat /var/www/hyanglin-home-src/src/layouts/xe_kimtajo_layout/css/layout.css
   ```
3. `main.php` 변경 확인:
   ```bash
   stat /var/www/hyanglin-home-src/src/contents/main.php
   ```
4. `.bak` 파일과 비교:
   ```bash
   diff main.php.bak main.php
   ```

### 로그인 세션 문제

1. `use_db_session` 확인:
   ```bash
   grep use_db_session /var/www/hyanglin-home-src/src/files/config/db.config.php
   ```
2. `xe_session` 테이블 존재 확인 (PHP PDO):
   ```sql
   SHOW TABLES LIKE 'xe_session'
   ```

---

## 10. 최근 인시던트 이력

> [!NOTE]
> 서버 인프라 레벨 이력(디스크 확장, NAS 이전, 커널 정리 등)은 [gabia-server.md](gabia-server.md) 참조.
> 여기에는 XE 애플리케이션 계층 인시던트만 기록합니다.

| 날짜 | 인시던트 | 원인 | 조치 | 이슈 |
|---|---|---|---|---|
| 2026-09-04 | 디스크 100% → 서비스 중단 | 해외 봇 40만 건 공격 → MySQL binlog 26GB 폭증 | binlog 정리, IP 차단, 만료일 3일 단축 | [#46](https://github.com/wonhyukc/hyanglin-legacy/issues/46) |
| 2026-09-05 AM | 이미지 깨짐 + 글쓰기 폼 소실 | 봇 대응 과정 Nginx rate limiting 과도 적용 | rate limiting 제거 | #44 |
| 2026-09-05 | 로그인 세션 짧아짐 | `use_db_session=N` → PHP 파일 세션(24분) 폴백 | `use_db_session=Y`로 변경 | #45 |
| 2026-09-05 | 메인 페이지 하단 빈 공간 | #41 iframe 높이 JS가 기존 로직과 충돌 | #41 JS에 모바일 조건 추가 | #47 |

---

## 11. 에이전트 유의사항

> [!CAUTION]
> 아래 사항을 반드시 숙지하고 작업하세요. 이 항목들은 과거 인시던트에서 반복적으로 발생한 실수들입니다.

1. **포트 혼동 금지**: `8080`(메인 홈페이지) ≠ `3000`(재정 시스템)
2. **파일 위치**: 소스 코드는 `/var/www/hyanglin-home-src/src/` (실체는 `/data/www/hyanglin-home-src/src/`, 심볼릭 링크)
3. **MySQL 접근**: 컨테이너 내 `mysql` CLI 안 됨 → PHP PDO 사용
4. **최근 변경 파일 찾기**:
   ```bash
   find /data/www/hyanglin-home-src/src -not -path '*/cache/*' -not -path '*/files/*' -printf '%T@ %Tc %p\n' | sort -rn | head
   ```
5. **에이전트 세션 이력 찾기**: `/home/hyuk/.gemini/antigravity-cli/brain/*/` 디렉터리에서 `transcript.jsonl` 검색
6. **Nginx 설정 변경 시 반드시 테스트**: `nginx -t` 먼저 실행 후 `nginx -s reload`
7. **rate limiting 적용 금지**: 메인 페이지는 한 번에 50개 이상의 리소스를 동시 로드하므로 일반적인 rate limiting은 정상 사용자도 차단함
8. **디스크 경로 주의**: `/var/www`, `/var/lib/docker`, `/var/log`는 모두 `/data/` 하위로 심볼릭 링크됨 — 용량 확인 시 `/data`를 기준으로 볼 것
