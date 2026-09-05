# 🏗️ 가비아 프로덕션 서버 운영 레퍼런스

> [!NOTE]
> 이 문서는 AI 에이전트와 운영자가 hyanglin-legacy 프로덕션 서버의 장애를 신속하게 조사하고 해결할 수 있도록 작성된 운영 레퍼런스입니다.
> 최종 업데이트: 2026-09-05

---

## 1. 서버 아키텍처 (Quick Reference)

| 항목 | 값 |
|---|---|
| 호스트 IP | `45.115.154.229` (SSH 포트 `2222`) |
| SSH 명령 | `ssh -p 2222 wonhyukc@45.115.154.229` (별칭: `sshy`) |
| OS | Ubuntu (가비아 클라우드) |
| 디스크 | 100GB (`/dev/vda4` → `/`) |

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
| `default.conf` | `/home/wonhyukc/hyanglin-home-infra/nginx/conf.d/default.conf` | 메인 HTTPS 프록시, CSP, sub_filter |
| `blockips.conf` | `/home/wonhyukc/hyanglin-home-infra/nginx/conf.d/blockips.conf` | 악성 IP 차단 목록 |
| `finance.conf` | `/home/wonhyukc/hyanglin-home-infra/nginx/conf.d/finance.conf` | 재정관리 시스템 프록시 |

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
| Password | `Jy0320Ks9702!` |
| Database | `hr2` |
| Table prefix | `xe_` |

---

## 6. MySQL 접근 방법

> [!WARNING]
> MySQL 컨테이너에는 직접 `mysql` 명령이 안 될 수 있습니다. **PHP 컨테이너에서 PDO로 접근**하세요.

```bash
sudo docker exec hyanglin-home-src-web-1 php -r '
$pdo = new PDO("mysql:host=172.18.0.1;dbname=hr2;charset=utf8", "root", "Jy0320Ks9702!");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query("YOUR SQL HERE");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) { print_r($row); }
'
```

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
2. Nginx 에러 로그:
   ```bash
   sudo docker logs hyanglin-home-infra-nginx-1 --tail 50
   ```
3. HTTP 응답 코드 확인:
   ```bash
   curl -s -o /dev/null -w '%{http_code}' https://www.hyanglin.org/images/icon3.png
   ```

### 디스크 용량 부족

1. 전체 디스크 확인:
   ```bash
   df -h /
   ```
2. Docker 용량:
   ```bash
   sudo docker system df
   ```
3. MySQL binlog 확인 (PHP PDO):
   ```sql
   SHOW BINARY LOGS
   ```
4. 큰 디렉토리 확인:
   ```bash
   du -sh /var/www/* /var/lib/docker/* 2>/dev/null | sort -rh | head -10
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

| 날짜 | 인시던트 | 원인 | 조치 | 이슈 |
|---|---|---|---|---|
| 2026-09-04 | 디스크 100% 고갈 | 해외 봇 40만 건 공격 → MySQL binlog 26GB 폭증 | binlog 정리, IP 차단, binlog 만료 3일 단축 | #46 |
| 2026-09-05 AM | 이미지 깨짐 + 글쓰기 폼 소실 | 봇 대응 과정에서 Nginx rate limiting 과도 적용 | rate limiting 제거 | #44 |
| 2026-09-05 | 로그인 세션 짧아짐 | `use_db_session=N` → PHP 파일 세션(24분) 폴백 | `use_db_session=Y`로 변경 | #45 |
| 2026-09-05 | 메인 페이지 하단 빈 공간 | #41 iframe 높이 JS가 기존 로직과 충돌 | #41 JS에 모바일 조건 추가 | #47 |

---

## 11. 에이전트 유의사항

> [!CAUTION]
> 아래 사항을 반드시 숙지하고 작업하세요. 이 항목들은 과거 인시던트에서 반복적으로 발생한 실수들입니다.

1. **포트 혼동 금지**: `8080`(메인 홈페이지) ≠ `3000`(재정 시스템)
2. **파일 위치**: 소스 코드는 호스트 `/var/www/hyanglin-home-src/src/`에 있음 (바인드 마운트)
3. **MySQL 접근**: 컨테이너 내 `mysql` CLI 안 됨 → PHP PDO 사용
4. **최근 변경 파일 찾기**:
   ```bash
   find /var/www/hyanglin-home-src/src -not -path '*/cache/*' -not -path '*/files/*' -printf '%T@ %Tc %p\n' | sort -rn | head
   ```
5. **에이전트 세션 이력 찾기**: `/home/hyuk/.gemini/antigravity-cli/brain/*/` 디렉토리에서 `transcript.jsonl` 검색
6. **Nginx 설정 변경 시 반드시 테스트**: `nginx -t` 먼저 실행 후 `nginx -s reload`
7. **rate limiting 적용 금지**: 메인 페이지는 한 번에 50개 이상의 리소스를 동시 로드하므로 일반적인 rate limiting은 정상 사용자도 차단함
