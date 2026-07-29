# 🏗️ 신규 서버 환경 및 아키텍처 가이드 (Legacy Developer Guide)

본 문서는 기존 레거시 서버(CentOS 5.8 / Apache / PHP 5.6 호스트 직접 구동)에서 신규 가비아 프로덕션 서버 환경(Ubuntu / Docker / Nginx 리버스 프록시 / SSL)으로 전환되면서 변경된 기술 스택, 서버 아키텍처, 그리고 이전 레거시 개발자 및 유지보수 담당자가 반드시 알아야 할 운영 지침을 정리한 가이드 문서입니다.

---

## 1. 🔄 레거시 vs 신규 서버 환경 비교

| 구분 | 레거시 서버 (구 환경) | 신규 프로덕션 서버 (새 환경) |
|---|---|---|
| **OS** | CentOS 5.8 (2017년 EOL) | Ubuntu 22.04 LTS |
| **웹 서버 (Web Server)** | Apache 2.x (호스트 OS 직접 설치) | Nginx (Docker 컨테이너, Port 80/443 SSL 서빙) |
| **애플리케이션 런타임** | PHP 5.6 (호스트 직접 설치) | Docker 샌드박스 (PHP 5.6 + XE 컨테이너 격리) |
| **재정 관리 시스템** | (독립 시스템 미구비) | Node.js / Next.js (Host PM2, Port 3000) |
| **SSL / HTTPS** | 미적용 (HTTP 80) | Let's Encrypt SSL + Certbot 자동 갱신 + CSP Upgrade |
| **포트 구조** | 80번 포트 (단일 Apache) | Nginx(80/443) ➔ 메인 홈페이지(8080) / 재정시스템(3000) 역프록시 |

---

## 2. 🐳 Docker를 사용하는 이유 (왜 도커인가?)

### 2.1 EOL(End of Life) PHP 5.6 런타임의 안전한 격리
레거시 향린교회 메인 홈페이지(XpressEngine XE 기반)는 **PHP 5.6** 환경에서만 정상 작동합니다. 최신 서버 OS(Ubuntu 22.04)는 기본적으로 최신 PHP 8.x 버전을 지원하며, EOL된 PHP 5.6을 OS 호스트에 직접 패키지로 설치하는 것은 의존성 충돌과 심각한 보안 위험을 초래합니다.
Docker를 사용하면 **최신 호스트 OS 환경의 안정성을 유지하면서, 컨테이너 내부 샌드박스에 PHP 5.6 런타임 환경을 완벽하게 격리하여 실행**할 수 있습니다.

### 2.2 보안 샌드박싱 (Security Sandboxing)
오래된 XE CMS 및 PHP 5.6 패키지에는 해결되지 않은 여러 보안 취약점이 존재할 수 있습니다.
- **권한 제한**: 컨테이너 내부 실행 계정을 비루트 사용자(`www-data`)로 제한하여 웹셸이나 RCE(원격 코드 실행) 공격을 받더라도 호스트 OS 권한 취득을 차단합니다.
- **시스템 보호**: 호스트 OS 및 인접 서비스(예: 향린 재정 관리 시스템)와 완전히 분리되어 한 영역이 공격받더라도 전체 시스템으로 피해가 확산되는 것을 막습니다.

### 2.3 표준화된 환경 구축 및 이동성 (Portability)
과거에는 Apache 컴파일, PHP 모듈 설정, GD 라이브러리 연동 등 수동 설정 과정이 복잡했으나, Docker 환경에서는 [docker-compose.yml](file:///home/hyuk/prj/hyanglin-legacy/docker-compose.yml) 파일 한 장으로 어디서나 동일한 환경을 수초 내에 즉시 구축하고 실행할 수 있습니다.

### 2.4 자원 사용량 제어 (Resource Constraints)
레거시 코드의 메모리 누수나 특정 게시판 스크립트의 무한 루프로 인해 호스트 서버 전체의 CPU/메모리가 고갈되는 것을 방지하도록 컨테이너별 자원 한계치를 설정할 수 있습니다.

---

## 3. 🌐 Nginx의 역할 및 리버스 프록시 아키텍처

신규 서버 환경에서 Nginx는 호스트의 **Port 80(HTTP)**과 **Port 443(HTTPS)**을 독점 서빙하며, 외부 요청을 수신하여 내부 서비스로 전달해 주는 **중앙 리버스 프록시(Reverse Proxy) 게이트웨이** 역할을 수행합니다.

### 3.1 Nginx의 4대 핵심 역할

1. **SSL 터미네이션 (HTTPS 암호화 처리)**
   - 외부 사용자와 Nginx 구간은 TLS 1.2/1.3 암호화를 적용합니다.
   - SSL 암호화/복호화 부담을 Nginx 전면에서 모두 처리하므로 백엔드 컨테이너(PHP 5.6)는 오직 웹 로직에만 집중할 수 있습니다.

2. **Certbot 연동 자동 SSL 인증서 갱신**
   - Nginx 설정 [nginx/conf.d/default.conf](file:///home/hyuk/prj/hyanglin-legacy/nginx/conf.d/default.conf) 내 `/.well-known/acme-challenge/` 경로가 Certbot 볼륨과 공유됩니다.
   - Certbot 컨테이너가 12시간 주기로 인증서를 자동으로 검증 및 갱신합니다.

3. **서비스 라우팅 (Service Routing)**
   - `www.hyanglin.org` / `hyanglin.org` ➔ 메인 홈페이지 컨테이너 (`http://host.docker.internal:8080`)
   - 재정 관리 웹 요청 ➔ Host PM2 기반 Next.js 서비스 (`http://localhost:3000`)

4. **Mixed Content (혼합 콘텐츠) 방지 및 URL 자동 변환**
   - **CSP 헤더 주입**: `Content-Security-Policy "upgrade-insecure-requests;";` 헤더를 통해 브라우저가 차단할 수 있는 옛 `http://` 이미지를 `https://`로 자동 승격합니다.
   - **Response body 변환 (`sub_filter`)**: 레거시 DB 본문에 하드코딩된 `http://localhost:8080/` 또는 `http://www.hyanglin.org/` 주소를 Nginx가 응답을 내보낼 때 `https://www.hyanglin.org/`로 자동 전환하여 렌더링합니다.

---

## 4. 🧠 이전 레거시 개발자가 알아야 할 주요 사항

> ⚠️ **경고 — 서버 아키텍처 및 포트 수칙**
> 1. **메인 홈페이지 (`www.hyanglin.org`)**: `/var/www/hyanglin-legacy` (Port `8080` / Docker PHP 5.6 XE)
> 2. **재정 관리 시스템 (지출결의서)**: `/var/www/hyanglin-finance/web` (Port `3000` / Host PM2 Next.js)
> 
> *3000번 포트는 재정 시스템 전용 포트이므로, Nginx 설정에서 메인 홈페이지 프록시 대상을 3000번으로 잘못 변경해서는 안 됩니다.*

### 4.1 소스 코드 수정 및 템플릿 캐시 관리
- **웹 루트 경로**: `/var/www/hyanglin-legacy` 디렉터리가 Docker 컨테이너 내부 웹 디렉터리로 마운트되어 있습니다.
- **XE 템플릿 캐시 초기화**: XE 템플릿이나 PHP 소스를 변경한 후 변경 사항이 즉시 보이지 않을 경우, XE 내부 캐시 디렉터리(`files/cache/`)를 삭제하거나 백오피스(관리자 페이지)에서 캐시를 재생성해야 합니다.

### 4.2 데이터베이스(MySQL) 접속 방식
- 호스트 외부에서 직접 접근하는 기본 MySQL 포트는 `3307`로 바인딩되어 있으며, Docker 내부 컨테이너 통신 시 서비스명 `legacy-mysql`을 사용합니다.
- 캐릭터셋은 `utf8mb4`로 통합 관리됩니다.

### 4.3 서버 명령어 체계 변경
| 작업 내용 | 과거 (CentOS / Apache) | 현재 (Ubuntu / Docker) |
|---|---|---|
| **웹 서버 재구동** | `sudo service httpd restart` | `cd ~/hyanglin-legacy && sudo docker compose restart nginx` |
| **메인 홈페이지 재구동** | `sudo service httpd restart` | `cd /var/www/hyanglin-legacy && sudo docker compose restart` |
| **SSL 인증서 수동 갱신** | 수동 스크립트 실행 | `sudo ./init-letsencrypt.sh` |
| **로그 확인** | `tail -f /var/log/httpd/error_log` | `sudo docker compose logs -f nginx` |

---

## 5. 📂 전체 폴더 구조 및 시스템 배치

현재 서버에 구축된 전체 프로젝트 디렉터리 계층 및 역할입니다.

```
/var/www/
├── hyanglin-legacy/                 # [메인 홈페이지 & Docker 인프라] (Port 8080, 80, 443)
│   ├── Dockerfile                   # 웹 애플리케이션 컨테이너 빌드 설정
│   ├── docker-compose.yml           # Docker 서비스 정의 (Nginx, Certbot, MySQL 5.7 등)
│   ├── init-letsencrypt.sh          # SSL (Let's Encrypt) 부트스트랩 및 갱신 스크립트
│   ├── AGENTS.md                    # 프로젝트 아키텍처 규칙 및 AI 에이전트 수칙
│   ├── README.md                    # 저장소 메인 안내 문서
│   ├── nginx/                       # Nginx 전면 프록시 설정
│   │   └── conf.d/
│   │       └── default.conf         # Nginx Reverse Proxy, SSL, CSP 및 sub_filter 설정
│   ├── docs/                        # 시스템 아키텍처 및 서버 문서
│   │   ├── new-server-architecture.md # (본 문서) 신규 서버 환경 안내 가이드
│   │   ├── legacy-homepage.md       # 구 서버(CentOS 5.8) 구조 및 백업 분석서
│   │   ├── legacy-documents-list.md # 레거시 문서 및 자산 목록
│   │   └── migration-verify.md      # 이관 검증 절차서
│   └── backups/                     # 레거시 데이터 및 DB 백업 파일 디렉터리
│
└── hyanglin-finance/                # [향린 재정 관리 시스템] (Port 3000)
    └── web/                         # Host PM2 기반 Next.js 지출결의서 웹 서비스
```

---

## 6. 📌 레퍼런스 문서
- [README.md](file:///home/hyuk/prj/hyanglin-legacy/README.md) — 프로젝트 메인 개요
- [AGENTS.md](file:///home/hyuk/prj/hyanglin-legacy/AGENTS.md) — 서버 아키텍처 수칙 및 스킬 규정
- [docs/legacy-homepage.md](file:///home/hyuk/prj/hyanglin-legacy/docs/legacy-homepage.md) — 구 원격 서버(`14.63.198.35`) 분석서
