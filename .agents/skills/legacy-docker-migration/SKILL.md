---
name: legacy-docker-migration
description: 지원이 종료된(EOL) 레거시 웹 서비스/홈페이지를 Docker 환경으로 안전하게 마이그레이션하고 격리·보안화하는 베스트 프랙티스 가이드
---

# 🐳 Legacy Docker Migration — 레거시 도커 이관 전문가 스킬

지원이 종료되거나 현대 서버 OS에서 컴파일/실행이 어려운 구형 홈페이지 및 웹 서비스(PHP 5.x, Python 2.x, Node 0.x, MySQL 5.x 등)를 도커를 활용하여 격리하고, 최신 인프라(가비아, 클라우드 등) 상에서 안전하게 구동하기 위한 가이드라인이다.

## 📌 트리거 조건 (이 스킬을 참조해야 하는 시점)

- `docker-compose.yml`, `Dockerfile`을 작성하거나 수정할 때
- 레거시 홈페이지 소스 코드를 새 서버로 이전하고 컨테이너 환경에서 구동해야 할 때
- EOL(End of Life) 버전의 언어 런타임, 데이터베이스 등을 컨테이너 내부에 적용해야 할 때
- 레거시 아카이빙된 코드를 로컬이나 가상 서버에서 테스트 구동할 때

---

## 🛠️ Docker 기반 레거시 격리 및 보안 베스트 프랙티스

### 1. 보안 샌드박싱 (Security Sandboxing)
- **비루트 사용자(Non-root User) 실행**: 레거시 런타임은 원격 코드 실행(RCE) 등 다양한 취약점에 노출되어 있다. 컨테이너 내부 실행 계정을 반드시 루트(`root`)가 아닌 제한된 일반 사용자(예: `www-data`, `node`)로 강제 전환한다.
  ```dockerfile
  # Dockerfile 예시
  FROM php:5.6-apache
  # 보안 설정 후 권한 제한
  USER www-data
  ```
- **읽기 전용 파일시스템(Read-Only Filesystem)**: 레거시 웹 루트 디렉터리에 악성 스크립트(웹셸)가 업로드되어 실행되는 것을 방지하기 위해, 쓰기 작업이 불필요한 영역은 읽기 전용으로 마운트하고 쓰기가 필요한 특정 폴더(업로드, 임시 디렉터리)만 명시적으로 쓰기 가능 볼륨으로 격리한다.
- **네트워크 차단 (No Out-bound Internet)**: 레거시 컨테이너가 해커의 C&C 서버로 연결을 시도하는 등의 2차 피해를 막기 위해 아웃바운드 인터넷 연결을 기본적으로 차단한다. 컨테이너 간의 통신만 전용 내부 브릿지 네트워크로 허용한다.

### 2. 리버스 프록시 및 WAF 연동
- **SSL 터미네이션 및 프록시**: 레거시 앱 내부에서 HTTPS 암호화를 직접 처리하지 않도록 하고, 현대적인 리버스 프록시(Nginx, Traefik 등)를 전면에 세워 HTTPS 연동과 헤더 보안 설정을 일괄 처리한다.
- **WAF(Web Application Firewall)**: 알려진 레거시 웹 취약점 패턴(SQL Injection, XSS)을 프록시 레이어(예: ModSecurity)에서 미리 차단할 수 있도록 구성한다.

### 3. 리소스 한계 제한 (Resource Constraints)
- 레거시 프로세스가 메모리 누수(Memory Leak) 등으로 인해 호스트 서버의 리소스를 고갈시키는 것을 방지하기 위해, `docker-compose.yml`에서 CPU 및 메모리 제한을 필수적으로 선언한다.
  ```yaml
  # docker-compose.yml 예시
  services:
    legacy-app:
      deploy:
        resources:
          limits:
            cpus: '0.5'
            memory: 512M
  ```

### 4. Docker Compose 기반 Let's Encrypt SSL 부트스트랩 및 Mixed Content 방지
- **더미 인증서 유지 및 In-place 갱신**: Nginx 시동 시 SSL 인증서 파일이 없으면 Nginx 컨테이너가 크래시된다. 최초 더미(Dummy) 인증서를 생성한 후 Nginx를 백그라운드로 구동하고, Certbot이 실행 중인 Nginx를 향해 `--force-renewal`로 덮어쓰도록 구성하여 ACME `Connection refused` 에러를 방지한다.
- **CSP `upgrade-insecure-requests` 헤더 적용**: 레거시 DB나 템플릿에 `http://`로 하드코딩된 이미지/자원이 잔존할 경우, Nginx 설정에 아래 헤더를 탑재하여 브라우저 레벨에서 100% HTTPS로 자동 업그레이드되도록 조치한다.
  ```nginx
  add_header Content-Security-Policy "upgrade-insecure-requests;";
  ```
- **레거시 CMS 템플릿 캐시 삭제**: XpressEngine(XE) 등 레거시 CMS의 경우 `files/config/db.config.php`의 `default_url`을 `https://`로 수정한 뒤 반드시 `files/cache/*` 컴파일 템플릿 캐시를 초기화한다.

### 5. 레거시 이관 시 정적 자원 및 레이아웃 검증 (Asset & Layout Validation)
- **정적 에셋(Asset) 디렉터리의 완전성 검증**: 레거시 프레임워크(XE 등)의 주 데이터 폴더(`home`, `contents`)만 이관할 경우, 시스템 바깥(Document Root)에 독립적으로 존재하는 장식용 `images`, `css`, `js` 폴더가 누락될 수 있다. 가급적 **전체 웹 루트(`/home/hr/www/*`)에 대한 `rsync` / `scp` 동기화**를 수행해야 한다.
- **레거시 CSS 레이아웃 꼼수(Trick) 복구**: 텅 빈 하얀색 이미지(Spacer)를 깔고 위젯을 음수 마진(`margin-top: -405px`)으로 끌어올리는 등의 기형적인 기법이 자주 사용되었다. 이관 후 레이아웃이 텅 비거나 어긋나는 경우, 단순 누락이 아니라 **데이터베이스나 캐시에 있던 인라인/커스텀 CSS(음수 마진 등)가 유실되었을 가능성**을 1순위로 의심하고, 테마 CSS 파일에 `!important`를 사용하여 강제 교정 코드를 주입해야 한다.
- **CSS 배경 이미지 404 에러의 강력 캐싱 대응**: HTML 엑박과 달리 CSS에서 불러오는 배경 이미지의 404 에러는 브라우저에 강력하게 캐시된다. 누락 복구 후에는 사용자에게 반드시 **강력 새로고침(Ctrl+F5 또는 Cmd+Shift+R)**을 안내해야 한다.

### 6. Nginx 역프록시 업로드 용량 제한 (`client_max_body_size`) 설정
- **기본 제한 차단 방지**: 레거시 Apache(기본 업로드 제한 없음/2GB) 환경을 Docker Nginx 역프록시 구조로 마이그레이션할 때, Nginx 설정(`conf.d/default.conf`)에 `client_max_body_size`가 지정되지 않으면 Nginx 기본값(**1MB**)이 적용되어 `413 Request Entity Too Large` 에러가 일어난다.
- **PHP 업로드 용량과 Nginx 용량 동기화**: PHP 컨테이너 내부 `upload_max_filesize` (예: `50M`) 설정에 맞춰 Nginx 설정의 SSL server 블록에도 동일한 용량의 `client_max_body_size`를 필수로 선언한다.
  ```nginx
  server {
      listen 443 ssl;
      client_max_body_size 50M; # PHP upload_max_filesize와 일치
  }
  ```

### 7. XE/CMS 회원 로그인 식별자(Identifier) 사전 판별 규칙
- **DB `user_id`와 실제 로그인 입력값의 구분**: DB 회원 테이블(`xe_member`)에서 `user_id`가 `t61927`로 확인되더라도, 실제 로그인 창에 입력할 아이디 값이 `user_id`라고 단정 짓지 않는다.
- **CMS 식별자 정책 사전 검사 의무화**: 계정 조회/안내 시 반드시 XE 회원 모듈 설정(`$oMemberModel->getMemberConfig()->identifier`) 또는 DB `xe_module_config`를 사전 확인한다.
  - `identifier`가 `email_address`로 설정된 경우: 로그인 시 입력할 '아이디'가 **이메일 주소(`mylovesarah@gmail.com`)**임을 정확히 고지하고 가이드해야 한다.
  - `identifier`가 `user_id`로 설정된 경우: 일반 아이디(`user_id`) 사용 안내.

---

## 🛡️ EOL 레거시 허용 지침 (Harness Bypass)

하네스 검증 시 EOL 이미지가 발견되면 에러가 발생하지만, 레거시 구동 목적으로 불가피하게 지원 종료 버전을 사용해야 하는 경우 **반드시 소스 코드 또는 Dockerfile 내에 허용 주석**을 명시한다:

- Dockerfile 예시:
  ```dockerfile
  # HARNESS-ALLOW-EOL: php5.6 for legacy migration
  FROM php:5.6-fpm
  ```
- docker-compose.yml 예시:
  ```yaml
  # HARNESS-ALLOW-EOL: mysql5.5
  image: mysql:5.5
  ```

---

### 8. ⚠️ rsync 이관 시 반드시 제외할 항목

Node.js 프로젝트가 포함된 디렉터리를 rsync로 이관할 때 다음을 **반드시 제외**한다.
포함하면 수십만 개의 소파일로 인해 rsync가 수 시간 소요되거나 중단된다.

| 제외 항목 | 이유 | 재생성 방법 |
|-----------|------|------------|
| `node_modules/` | 수십만 소파일 → rsync 수 시간 소요 | `pnpm install` / `npm install` |
| `.next/` | Next.js 빌드 캐시 | `next build` |
| `*.log` | 불필요한 로그 파일 | — |

**표준 rsync 명령어:**
```bash
rsync -a \
  --exclude='node_modules' \
  --exclude='.next' \
  --exclude='*.log' \
  <source>/ <destination>/
```

이관 완료 후 서버에서 패키지 재설치:
```bash
cd <destination>/web   # 또는 해당 Node.js 프로젝트 경로
pnpm install           # 또는 npm install
npm run build          # 필요 시
```

---

### 9. 🚀 KT 레거시 서버 → 가비아 이관 실전 패턴 (2026-09-12 검증)

#### 파일 rsync: Pull 방식 필수

가비아 방화벽이 KT IP(`14.63.198.35`)를 차단하므로 **Push(KT→가비아) 방식은 불가**. 가비아에서 KT로 당기는 **Pull 방식**을 사용한다.

**사전 준비 (최초 1회):**
```bash
# 1. 가비아 서버에 임시 키쌍 생성
ssh -p 2222 wonhyukc@45.115.154.229 "
  ssh-keygen -t rsa -b 4096 -f /tmp/kt_tmp_key -N '' -q
  cat /tmp/kt_tmp_key.pub
"

# 2. 생성된 공개키를 KT 서버 authorized_keys에 등록
ssh -p 2222 root@14.63.198.35 "
  echo '<위에서 출력된 공개키>' >> /root/.ssh/authorized_keys
  # KT 서버 sshd에 공개키 인증 활성화 (CentOS 5.8 필수)
  echo 'PubkeyAuthentication yes' >> /etc/ssh/sshd_config
  echo 'RSAAuthentication yes' >> /etc/ssh/sshd_config
  service sshd reload
"
```

**Pull rsync 실행:**
```bash
ssh -p 2222 wonhyukc@45.115.154.229 \
  "rsync -az \
  --exclude='home/files/cache/' --exclude='home/files/env/' \
  -e 'ssh -p 2222 -o StrictHostKeyChecking=no -i /tmp/kt_tmp_key \
      -o KexAlgorithms=+diffie-hellman-group14-sha1 \
      -o HostKeyAlgorithms=+ssh-rsa \
      -o PubkeyAcceptedAlgorithms=+ssh-rsa' \
  root@14.63.198.35:/home/{사이트}/www/ \
  /data/www/{사이트}/"
```

> ⚠️ **SSH 레거시 옵션 3종 세트 필수** (CentOS 5.8 구형 OpenSSH 호환):
> - `KexAlgorithms=+diffie-hellman-group14-sha1` — 키 교환 알고리즘
> - `HostKeyAlgorithms=+ssh-rsa` — 호스트 키 알고리즘
> - `PubkeyAcceptedAlgorithms=+ssh-rsa` — 공개키 서명 알고리즘 (없으면 `no mutual signature algorithm` 오류)

#### DB 임포트: 호스트 네이티브 MySQL(3306) 사용

가비아 서버의 실제 운영 DB는 Docker 컨테이너가 아니라 **호스트 네이티브 MySQL 서비스(포트 3306)**이다.

```bash
# 호스트 쉘에서 바로 임포트 (비밀번호 불필요, auth_socket)
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS {DB명} CHARACTER SET utf8;"
zcat ~/{덤프}.sql.gz | sudo mysql -u root {DB명}

# 컨테이너(172.18.%) 접근 권한은 이미 *.* 에 열려 있으므로 추가 작업 불필요
```

| 접속 위치 | 비밀번호 | 접속 대상 |
|---|---|---|
| 호스트 쉘 `sudo mysql -u root` | 비밀번호 없음 (auth_socket) | 로컬 소켓 |
| PHP 컨테이너 (XE db.config.php) | KeePass `KT_MYSQL_PW` (`hyanglin-homepage .env.production`) | `172.18.0.1:3306` |

#### 다중 사이트 이관: 사이트별 별도 PHP 컨테이너

각 사이트마다 독립적인 PHP 컨테이너를 추가하고 포트를 순차 할당한다.

```yaml
# /var/www/hyanglin-home-src/docker-compose.yml 에 추가
services:
  {사이트명}:
    build: .
    ports:
      - '{포트}:80'  # 8081, 8082, 8083 ...
    volumes:
      - /data/www/{사이트명}:/var/www/html
      - ./php.ini:/usr/local/etc/php/php.ini
    environment:
      - TZ=Asia/Seoul
    extra_hosts:
      - "host.docker.internal:host-gateway"
    restart: always
```

**포트 할당 현황:**
| 포트 | 사이트 | 이슈 |
|---|---|---|
| 8080 | hyanglin (메인) | — |
| 8081 | ongallery | #26 |
| 8082 | parkhk (parkhyungkyu.org) | #58 |
| 8083 | haerangart (haerangart.com) | #59 |
| 8084 | educrit (educrit.org) | #60 |
| 8085 | gilmok (gilmok.org) | #61 |
| 8086 | simwon (simwon.org) | #62 |

**Nginx vhost에서 해당 포트로 프록시:**
```nginx
# /var/www/hyanglin-home-infra/nginx/conf.d/{사이트}.conf
location / {
    proxy_pass http://host.docker.internal:{포트};
}
```

#### XE db.config.php 필수 수정 항목
```bash
# db_hostname: KT 서버 127.0.0.1 → 가비아 Docker bridge
sed -i "s/'db_hostname' => 'localhost'/'db_hostname' => '172.18.0.1'/g" db.config.php
sed -i "s/'db_hostname' => '127.0.0.1'/'db_hostname' => '172.18.0.1'/g" db.config.php

# default_url: http → https
sed -i "s|'default_url' => 'http://|'default_url' => 'https://|g" db.config.php

# 캐시 삭제 필수
rm -rf home/files/cache/*
```

---

### 10. 🛡️ HTTPS 이전 후 이미지 엑박(Mixed Content) 및 SSL 캐시 방지 수칙 (실전 교훈)

HTTPS로 전환할 때 레거시 데이터(DB 본문 및 템플릿)의 `http://` 하드코딩 URL로 인해 최신 브라우저에서 이미지가 차단(엑박)되거나 'Not secure' 경고가 발생하는 문제를 방지하기 위한 표준 수칙이다.

#### ① DB 내부 `http://` ➔ `https://` 일괄 치환 (DNS 변경 전 의무 실행)
DB 임포트 직후 반드시 본문 테이블(`xe_documents`)의 도메인 URL을 `https://`로 치환한다.
```bash
sudo mysql -u root {DB명} -e "
  UPDATE xe_documents SET content = REPLACE(content, 'http://www.{도메인}', 'https://www.{도메인}') WHERE content LIKE '%http://www.{도메인}%';
  UPDATE xe_documents SET content = REPLACE(content, 'http://{도메인}', 'https://www.{도메인}') WHERE content LIKE '%http://{도메인}%';
"
# XE 템플릿 컴파일 캐시 삭제 (필수)
sudo rm -rf /data/www/{사이트}/home/files/cache/*
```

#### ② Nginx vhost 표준 템플릿 (`sub_filter` 기본 탑재)
혹시 DB에 치환되지 않은 `http://` 링크가 남아있더라도 브라우저로 전송될 때 실시간으로 `https://`로 변환하도록 `sub_filter`를 필수로 구성한다.
```nginx
# /var/www/hyanglin-home-infra/nginx/conf.d/{사이트}.conf 예시
server {
    listen 80;
    server_name {도메인} www.{도메인};

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

server {
    listen 443 ssl;
    server_name {도메인} www.{도메인};

    ssl_certificate     /etc/letsencrypt/live/{도메인}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{도메인}/privkey.pem;

    client_max_body_size 50M;
    add_header Content-Security-Policy "upgrade-insecure-requests;";
    server_tokens off;

    location / {
        proxy_pass         http://host.docker.internal:{포트};
        proxy_set_header   Host $host;
        proxy_set_header   X-Real-IP $remote_addr;
        proxy_set_header   X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto https;

        # 혼합 콘텐츠(Mixed Content) 방지 실시간 치환
        proxy_set_header   Accept-Encoding "";
        sub_filter "http://www.{도메인}" "https://www.{도메인}";
        sub_filter "http://{도메인}" "https://www.{도메인}";
        sub_filter_once off;
        sub_filter_types text/html text/css text/xml application/javascript;
    }
}
```

#### ③ Certbot 발급 시 `--cert-name` 명시
임시 더미 인증서 디렉터리와의 충돌(`live directory exists`)을 방지하기 위해 정식 발급 시 항상 `--cert-name`을 명시한다.
```bash
sudo docker compose run --rm --entrypoint certbot certbot certonly \
  --webroot --webroot-path=/var/www/certbot \
  --cert-name {도메인} \
  -d {도메인} -d www.{도메인} \
  --email myLoveSarah@gmail.com --agree-tos --no-eff-email
```

#### ④ 사용자 검증 안내 수칙 (브라우저 캐시 주의)
더미 인증서 상태에서 사용자가 브라우저로 접속한 이력이 있다면, 크롬 브라우저가 해당 탭에 '보안 경고'를 캐시해 둔다.
따라서 정식 SSL 적용 후 사용자에게 검증을 요청할 때는 단순 새로고침이 아니라 **"현재 탭을 닫고 새 탭을 열거나, 시크릿 창(Ctrl+Shift+N)으로 확인"**하도록 반드시 안내한다.

