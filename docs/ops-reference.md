# 🏗️ 가비아 프로덕션 서버 운영 레퍼런스

> [!IMPORTANT]
> **서버 인프라(디스크·NAS·보안 패치·이력)의 SSOT는 [gabia-server.md](gabia-server.md)입니다.**
> 디스크 구조, fstab, 커널·패키지 관리, 주요 이력 등은 반드시 그 문서를 먼저 확인하세요.
> 이 문서는 Docker/Nginx/XE 애플리케이션 계층의 운영 절차에 집중합니다.
>
> 최종 업데이트: 2026-10-05

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

> 2026-10-05 `docker ps` 실측 기준. 총 18개 컨테이너, 4개 compose 프로젝트.

| 컨테이너 이름 | 이미지 | 포트 | 네트워크 | 역할 |
|---|---|---|---|---|
| `hyanglin-home-infra-nginx-1` | `nginx:alpine` | `80,443` | `hyanglin-home-infra_default` | HTTPS 프록시 + SSL (전 사이트 분기) |
| `hyanglin-home-infra-certbot-1` | `certbot/certbot` | - | `hyanglin-home-infra_default` | Let's Encrypt 인증서 |
| `hyanglin-home-infra-legacy-mysql-1` | `mysql:5.7` | `127.0.0.1:3307→3306` | `hyanglin-home-infra_default` | MySQL 5.7 컨테이너 (상세 §6 참조) |
| `pgsql` | `postgres:15-alpine` | `5432` | `bridge` | PostgreSQL (재정·시네마버킷리스트용) |
| `hyanglin-home-src-web-1` | `hyanglin-home-src-web` | `8080→80` | `hyanglin-home-src_default` | PHP 5.6 XE 메인 홈페이지 |
| `hyanglin-home-src-ongallery-1` | `hyanglin-home-src-ongallery` | `8081→80` | `hyanglin-home-src_default` | 이양노 갤러리 |
| `hyanglin-home-src-parkhk-1` | `hyanglin-home-src-parkhk` | `8082→80` | `hyanglin-home-src_default` | 박형규 기념사업회 |
| `hyanglin-home-src-haerangart-1` | `hyanglin-home-src-haerangart` | `8083→80` | `hyanglin-home-src_default` | 해랑 |
| `hyanglin-home-src-educrit-1` | `hyanglin-home-src-educrit` | `8084→80` | `hyanglin-home-src_default` | 교육비평 |
| `hyanglin-home-src-gilmok-1` | `hyanglin-home-src-gilmok` | `8085→80` | `hyanglin-home-src_default` | 길목 |
| `hyanglin-home-src-simwon-1` | `hyanglin-home-src-simwon` | `8086→80` | `hyanglin-home-src_default` | 심원 아카이브 |
| `hyanglin-home-src-ahn-library-1` | `hyanglin-home-src-ahn-library` | `8087→80` | `hyanglin-home-src_default` | 안병무도서관 (레거시 서빙 중) |
| `hyanglin-home-src-rorobrain-1` | `hyanglin-home-src-rorobrain` | `8088→80` | `hyanglin-home-src_default` | 로로브레인 |
| `hyanglin-home-src-bbook-1` | `hyanglin-home-src-bbook` | `8089→80` | `hyanglin-home-src_default` | 비북 |
| `hyanglin-home-src-moviediary-1` | `hyanglin-home-src-moviediary` | `8090→80` | `hyanglin-home-src_default` | 시네마버킷리스트 레거시 경로 (`/home` 등, XE) |
| `hyanglin-home-src-cnblaw-1` | `hyanglin-home-src-cnblaw` | `8091→80` | `hyanglin-home-src_default` | 법무법인오늘 |
| `cinemabucketlist-web` | `cinemabucketlist-cinemabucketlist-web` | `8092→3000` | `cinemabucketlist_default` | 시네마버킷리스트 Next.js 메인 (서버 경로 `/home/extralee/cinemabucketlist`) |
| `ahn-library-web` | `ahn-library-new-ahn-library-web` | `8093→3000` | `ahn-library-new_default` | nginx 미연결, 프로덕션 미서빙 |

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

### 6-1. Docker 네트워크 대역 설명 (172.17 vs 172.18)

phpMyAdmin 사용자 목록에서 호스트가 `172.17.%`와 `172.18.%` 두 종류로 나뉘는 이유:

| 대역 | Docker 네트워크 이름 | 역할 |
|---|---|---|
| `172.17.x.x` | `bridge` (Docker 기본) | Docker 설치 시 자동 생성되는 기본 네트워크. 현재 실제 컨테이너가 사용하지 않음. |
| `172.18.x.x` | `hyanglin-home` (커스텀) | PHP 앱 컨테이너들이 실제로 사용하는 네트워크. DB 접속은 이 대역으로만 발생. |

> [!IMPORTANT]
> **phpMyAdmin 계정을 새로 만들 때는 반드시 `172.18.%` 호스트로만 만들면 됩니다.**
> `172.17.%`는 현재 아무도 사용하지 않는 대역이므로 신규 계정에 추가하지 않아도 됩니다.
> `localhost` 호스트도 phpMyAdmin 용도에는 불필요합니다.

### 6-2. phpMyAdmin 관리자 계정 목록 (SSOT)

접속 URL: `https://www.cinemabucketlist.com/phpMyAdmin/`
접속 허용 IP: `211.177.80.14` (mountain 머신)만 허용, 그 외 403 차단

| MySQL 계정 | 호스트 | 비밀번호 관리 | 용도 |
|---|---|---|---|
| `root` | `172.18.%` | KeePass `KT_MYSQL_PW` | PHP 앱 자동 사용 (건드리지 말 것) |
| `extralee` | `172.18.%` | 별도 관리 | 관리자 phpMyAdmin 접속 |
| `williamc` | `172.18.%` | 별도 관리 | 관리자 phpMyAdmin 접속 |
| `wonhyukc` | `172.18.%` | 별도 관리 | 관리자 phpMyAdmin 접속 |

> [!NOTE]
> **신규 phpMyAdmin 계정 생성 명령어 (표준):**
> ```bash
> sudo mysql -e "CREATE USER '{계정명}'@'172.18.%' IDENTIFIED WITH mysql_native_password BY '{비밀번호}'; GRANT ALL PRIVILEGES ON *.* TO '{계정명}'@'172.18.%' WITH GRANT OPTION; FLUSH PRIVILEGES;"
> ```
>
> **KT → 가비아 마이그레이션 주의**: mysqldump는 테이블 데이터만 이관하며, MySQL 계정(`mysql.user`)은 자동으로 이관되지 않습니다. 가비아 서버에서 수동으로 재생성해야 합니다.

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

## 10. 모니터링 & 텔레그램 실시간 장애 경보 시스템 (#82)

> 📖 **상세 구축 쿡북 가이드**: 처음부터 따라하기만 하면 5분 만에 세팅되는 [텔레그램 알림 완벽 구축 가이드 (telegram-alert-setup.md)](telegram-alert-setup.md)를 참조하세요.

가비아 프로덕션 서버에서 운영 중인 13개 전체 웹 서비스와 핵심 인프라의 장애를 실시간으로 자동 감지하여, 관리자 텔레그램 슈퍼그룹으로 즉각 경보를 발송하는 시스템입니다.

### 10-1. 2중 감시 체계 (Two-Tier Monitoring) 및 크론 등록

서버가 다운되면 서버 내부 크론도 정지하므로, **서버 내부 상세 점검**과 **로컬 PC 외부 생존 감시**의 2중 감시망으로 운영됩니다.

```
[1계층: 서버 내부 Cron (10분 주기)] 
  └── /home/wonhyukc/scripts/health-check-cron.sh
       ├── 13개 웹 엔드포인트 응답 검사 (8080-8091, 3000)
       ├── 인프라 리소스 검사 (디스크 사용률, Docker 컨테이너 수)
       ├── 정상 (All PASS) → /data/log/health-check.log 기록 (알림 생략)
       └── 장애 감지 (FAIL ≥ 1) → Telegram Bot API 호출 → 텔레그램 슈퍼그룹 실시간 경보

[2계층: 로컬 PC 외부 감시 (5분 주기)]
  └── .bin/alive-check.sh (systemd user timer: hyanglin-alive-check.timer)
       ├── 최후 방어선: 외부에서 https://www.hyanglin.org 생존 확인 (Timeout 20s)
       ├── 서버 다운(응답 불가/비정상 HTTP) 감지 → 텔레그램 슈퍼그룹 즉시 경보
       └── 서버 복구 시 → 복구 알림 전송 (상태 파일 기반 중복 알림 방지)
```

- **1계층 (서버 내부 크론)**:
  - **로컬 저장소 (SSOT)**: [`server-scripts/health-check-cron.sh`](../server-scripts/health-check-cron.sh)
  - **서버 실구동 경로**: `/home/wonhyukc/scripts/health-check-cron.sh`
  - **실행 로그 경로**: `/data/log/health-check.log`
  - **Crontab 등록 상태**:
    ```bash
    */10 * * * * /home/wonhyukc/scripts/health-check-cron.sh >> /data/log/health-check.log 2>&1
    ```
- **2계층 (로컬 외부 감시)**:
  - **스크립트 경로**: `.bin/alive-check.sh`
  - **systemd 서비스/타이머**: `~/.config/systemd/user/hyanglin-alive-check.{service,timer}`
  - **실행 주기**: 5분마다 (`OnUnitActiveSec=5min`, `Persistent=true`)

### 10-2. 검증 대상 항목 (총 16개 지표)

| 구분 | 대상 | 포트 | 판정 기준 |
|---|---|---|---|
| **메인 웹** | 향린교회 메인 홈페이지 | `8080` | HTTP 200, 301, 302 |
| **재정 웹** | 향린 재정 관리 시스템 | `3000` | HTTP 200, 301, 302 |
| **호스팅 웹** | 안병무도서관 | `8087` | HTTP 200, 301, 302 |
| | 이양노 갤러리 | `8081` | HTTP 200, 301, 302 |
| | 법무법인오늘 | `8091` | HTTP 200, 301, 302 |
| | 박형규 기념사업회 | `8082` | HTTP 200, 301, 302 |
| | 해랑 | `8083` | HTTP 200, 301, 302 |
| | 교육비평 | `8084` | HTTP 200, 301, 302 |
| | 길목 | `8085` | HTTP 200, 301, 302 |
| | 심원 아카이브 | `8086` | HTTP 200, 301, 302 |
| | 로로브레인 | `8088` | HTTP 200, 301, 302 |
| | 비북 | `8089` | HTTP 200, 301, 302 |
| | 시네마 버킷리스트 | `8090` | HTTP 200, 301, 302 |
| **인프라** | 루트 파티션 (`/`, vda4) | - | 디스크 사용률 80% 미만 |
| | 데이터 파티션 (`/data`, vdb) | - | 디스크 사용률 80% 미만 |
| | Docker 컨테이너 | - | 실행 중인 컨테이너 13개 이상 |

### 10-3. 텔레그램 연동 규격

- **Bot Token**: `8037347881:AAEXiAPgEC3iEzv-6MwNC0AN2ughbtZqm3E`
- **Chat ID**: `-1004408048565` (슈퍼그룹)
- **전송 방식**: `curl -s -X POST "https://api.telegram.org/bot<TOKEN>/sendMessage" -d "chat_id=<CHAT_ID>" -d "text=<TEXT>" -d "parse_mode=Markdown"`
- **주의사항**: 일반 그룹이 슈퍼그룹으로 자동 승격될 경우 Chat ID가 `-100...` 형태로 바뀌므로 반드시 슈퍼그룹 Chat ID를 유지해야 합니다.

### 10-4. 운영 및 점검 명령어

```bash
# 1. 크론 실행 로그 실시간 확인
tail -f /data/log/health-check.log

# 2. 로컬에서 수동 헬스체크 실행 (1단계 핵심 / 2단계 전체)
bash .agents/skills/health-check/scripts/health-check.sh 1
bash .agents/skills/health-check/scripts/health-check.sh 2

# 3. 서버 내부에서 크론 스크립트 수동 1회 실행
/home/wonhyukc/scripts/health-check-cron.sh
```

---

## 11. 최근 인시던트 이력

> [!NOTE]
> 서버 인프라 레벨 이력(디스크 확장, NAS 이전, 커널 정리 등)은 [gabia-server.md](gabia-server.md) 참조.
> 여기에는 애플리케이션 및 웹 서비스 계층 인시던트만 기록합니다.

| 날짜 | 인시던트 | 원인 | 조치 | 이슈 |
|---|---|---|---|---|
| 2026-09-04 | 디스크 100% → 서비스 중단 | 해외 봇 40만 건 공격 → MySQL binlog 26GB 폭증 | binlog 정리, IP 차단, 만료일 3일 단축 | [#46](https://github.com/wonhyukc/hyanglin-legacy/issues/46) |
| 2026-09-05 AM | 이미지 깨짐 + 글쓰기 폼 소실 | 봇 대응 과정 Nginx rate limiting 과도 적용 | rate limiting 제거 | #44 |
| 2026-09-05 | 로그인 세션 짧아짐 | `use_db_session=N` → PHP 파일 세션(24분) 폴백 | `use_db_session=Y`로 변경 | #45 |
| 2026-09-05 | 메인 페이지 하단 빈 공간 | #41 iframe 높이 JS가 기존 로직과 충돌 | #41 JS에 모바일 조건 추가 | #47 |
| 2026-09-25 | 스왑 부족 및 MySQL hang | 1GB 스왑 고갈로 인한 메모리 압박 및 프로세스 hang | `/data/swapfile` 4GB 증설 (총 5GB 스왑 확보) | [#80](https://github.com/wonhyukc/hyanglin-legacy/issues/80) |
| 2026-09-25 | NFS 캐시 락 교착 및 봇넷 폭주로 홈(8080) 다운 | 12개 도메인 이전 후 분산 봇넷 유입 + `/nas` NFS v3 파일 락(flock) 교착으로 154개 Apache 워커 잠식 | 1) XE 캐시 로컬 SSD 분리 마운트<br>2) Nginx L7 WAF 봇넷 필터링 및 7일 브라우저 캐싱<br>3) Apache MaxConnectionsPerChild 500 워커 교대제 적용 | [#79](https://github.com/wonhyukc/hyanglin-legacy/issues/79) |
| 2026-09-25 | 텔레그램 실시간 장애 경보 구축 | 10분 주기 헬스체크 크론 및 FAIL 시 실시간 알림 연동 | 크론 스크립트 배포, 슈퍼그룹 Chat ID 연동, 실전 검증 | [#82](https://github.com/wonhyukc/hyanglin-legacy/issues/82) |

---

## 12. 에이전트 유의사항

> [!CAUTION]
> 아래 사항을 반드시 숙지하고 작업하세요. 이 항목들은 과거 인시던트에서 반복적으로 발생한 실수들입니다.

1. **포트 혼동 금지**: `8080`(메인 홈페이지) ≠ `3000`(재정 시스템)
2. **파일 위치**: 소스 코드는 `/var/www/hyanglin-home-src/src/` (실체는 `/data/www/hyanglin-home-src/src/`, 심볼릭 링크)
3. **XE 캐시 위치**: `/var/www/html/files/cache`는 반드시 **로컬 SSD(`/data/www/hyanglin-home-src/files/cache`)**로 분리 마운트되어 있어야 함 (NFS 마운트 절대 금지, flock 교착 방지, #79)
4. **MySQL 접근**: 컨테이너 내 `mysql` CLI 안 됨 → PHP PDO 사용
5. **최근 변경 파일 찾기**:
   ```bash
   find /data/www/hyanglin-home-src/src -not -path '*/cache/*' -not -path '*/files/*' -printf '%T@ %Tc %p\n' | sort -rn | head
   ```
6. **에이전트 세션 이력 찾기**: `/home/hyuk/.gemini/antigravity-cli/brain/*/` 디렉터리에서 `transcript.jsonl` 검색
7. **Nginx 설정 변경 시 반드시 테스트**: `nginx -t` 먼저 실행 후 `nginx -s reload`
8. **전역 Rate Limit 및 limit_conn 적용 금지**:
   - 메인 페이지는 한 번에 50개 이상의 리소스(썸네일, 위젯, iframe)를 동시 로드하므로 전역 `limit_conn 50`이나 단순 IP rate limiting을 걸면 정상 교인도 503 에러로 차단되고 이미지가 깨짐 (#44, #79)
   - 정적 파일(CSS/JS/이미지)은 `expires 7d;` 브라우저 캐싱과 함께 무제한 허용하고, **동적 PHP 요청에만 v3 Rate Limit 및 L7 WAF**를 적용할 것
9. **디스크 경로 주의**: `/var/www`, `/var/lib/docker`, `/var/log`는 모두 `/data/` 하위로 심볼릭 링크됨 — 용량 확인 시 `/data`를 기준으로 볼 것

