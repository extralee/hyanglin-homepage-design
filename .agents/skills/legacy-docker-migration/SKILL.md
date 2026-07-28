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
