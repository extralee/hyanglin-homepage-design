# 향린교회 레거시 마이그레이션 프로젝트 (`hyanglin-legacy`)

이 프로젝트는 향린교회 레거시 웹 서비스 및 홈페이지 데이터 자산화, 서버 마이그레이션(KT 클라우드 → 가비아), 그리고 Docker / HTTPS 보안 통신 환경 구축을 담당하는 저장소입니다.

---

## 🏗️ 서비스 및 시스템 아키텍처 (Architecture)

신규 가비아 프로덕션 서버(`45.115.154.229`) 환경에서는 구형 PHP 메인 홈페이지와 신규 재정 웹 시스템이 명확하게 구분되어 서빙됩니다.

```
                  [사용자 브라우저 (HTTPS)]
                             │
                             ▼
             [Docker Nginx (Port 80 / 443)]
           (Let's Encrypt SSL / CSP Upgrade)
                             │
              ┌──────────────┴──────────────┐
              ▼                             ▼
   [향린교회 메인 홈페이지]        [향린 재정 관리 시스템]
  - 위치: /var/www/hyanglin-legacy - 위치: /var/www/hyanglin-finance/web
  - 연동: Docker (PHP 5.6 / XE)    - 연동: Host PM2 (Next.js)
  - 포트: 8080                      - 포트: 3000
  - 도메인: www.hyanglin.org        - 용도: 지출결의서 및 재정 관리
```

### 1. 서비스별 역할 구분

| 서비스명 | 구동 방식 / 위치 | 포트 | 바인딩 도메인 / 역할 |
|---|---|---|---|
| **향린교회 메인 홈페이지** | Docker (PHP 5.6 + XE) <br> `/var/www/hyanglin-legacy` | `8080` | `www.hyanglin.org`, `hyanglin.org`<br>성도 및 일반 방문자용 공식 메인 홈페이지 |
| **향린 재정 시스템** | Host PM2 (Next.js) <br> `/var/www/hyanglin-finance/web` | `3000` | 교인 재정 관리 및 지출결의서 전용 웹 시스템 |
| **Nginx & Certbot** | Docker Compose <br> `~/hyanglin-legacy` | `80`, `443` | SSL 암호화 종단, ACME 자동 갱신, CSP 보안 헤더 처리 |

---

## 🔒 보안 및 SSL (HTTPS) 설정

- **인증서**: Let's Encrypt 정식 SSL 인증서 적용 (`www.hyanglin.org`, `hyanglin.org`)
- **자동 갱신**: `certbot` 컨테이너가 12시간 주기 체크 후 자동 갱신
- **Mixed Content 방지**: Nginx 보안 헤더에 `Content-Security-Policy "upgrade-insecure-requests;";`가 탑재되어 본문 내 옛 `http://` 이미지 자원을 브라우저에서 `https://`로 자동 승격하여 로드합니다.

---

## 🛠️ 운영 및 배포 가이드

### 1. SSL 및 Nginx 서비스 재구동
```bash
cd ~/hyanglin-legacy
sudo docker compose down
sudo ./init-letsencrypt.sh
```

### 2. 메인 홈페이지(PHP/XE) 컨테이너 재구동
```bash
cd /var/www/hyanglin-legacy
sudo docker compose up -d
```

---

## 📌 주요 문서 레퍼런스
- [1prd.md](file:///home/hyuk/prj/hyanglin-legacy/1prd.md) — 프로젝트 단일 진실 원천 (SSOT)
- [AGENTS.md](file:///home/hyuk/prj/hyanglin-legacy/AGENTS.md) — AI 에이전트 지침 및 스킬 인벤토리
- [docs/new-server-architecture.md](file:///home/hyuk/prj/hyanglin-legacy/docs/new-server-architecture.md) — 신규 서버 환경 및 아키텍처 가이드 (레거시 개발자용)
- [docs/legacy-homepage.md](file:///home/hyuk/prj/hyanglin-legacy/docs/legacy-homepage.md) — 레거시 서버 구조 상세 문서

