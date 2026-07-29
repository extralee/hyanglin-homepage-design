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

> 💡 **한눈에 이해하는 쉬운 설명**
> - **옛날 방식**: 오래된 집(CentOS 5) 하나에 웹사이트, 데이터베이스, 옛날 프로그램(PHP 5.6)을 한데 밀어 넣고 입구(Port 80)도 하나만 썼습니다. 집이 낡아도 OS 수리가 불가능한 위험한 상태였습니다.
> - **새로운 방식**: 최신 안전한 빌딩(Ubuntu 22) 안에 **독립된 개별 방(도커 컨테이너)**들을 만들었습니다. 정문 안내원(Nginx)이 방문자를 받아 보안 검사(SSL)를 마친 뒤, 메인 홈페이지 방(8080) 또는 재정 관리 방(3000)으로 연결해 줍니다.

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

> 💡 **한눈에 이해하는 쉬운 설명**
> - **도커(Docker)는 '독립된 안전 밀폐 상자'입니다.**
> - 레거시 홈페이지 프로그램(PHP 5.6)은 10년도 넘은 단종 제품이라, 최신 컴퓨터(서버 OS)에 직접 설치하는 것은 위험하고 잘 설치되지도 않습니다.
> - 하지만 **도커라는 특수 상자** 안에 옛날 프로그램 환경을 쏙 넣어서 실행하면, 최신 컴퓨터 위에서도 보안 걱정 없이 옛날 프로그램을 안전하게 돌릴 수 있습니다. 만에 하나 상자 내부가 공격받아도 상자 밖의 서버나 다른 프로그램(재정 시스템)에는 아무런 영향이 없습니다.

---

## 3. 🐘 PHP 5.6 전용 동작 이유 및 PHP 8.x 마이그레이션 분석

### 3.1 왜 PHP 8.x 환경에서는 레거시 사이트가 동작하지 않는가? (Breaking Changes)
PHP 5.6 ➔ PHP 7.x ➔ PHP 8.x로 진화하면서 과거의 취약하거나 비표준적인 문법들이 전면 제거(**Breaking Changes**)되었습니다. 레거시 XpressEngine(XE 1.7-1.8) 및 구형 서드파티 모듈은 이 변화로 인해 PHP 8.x에서 즉시 `Fatal Error`를 일으킵니다.

1. **`mysql_*` DB 전용 함수 삭제 (PHP 7.0에서 제거)**
   - PHP 5.6 이하: `mysql_connect()`, `mysql_query()` 등 구형 DB API 사용.
   - PHP 8.x: 해당 함수가 엔진 레벨에서 완벽히 제거되어 `Call to undefined function mysql_connect()` 에러와 함께 즉시 중단됨 (PDO 또는 `mysqli_*`로 전부 재작성 필요).
2. **구형 클래스 생성자(Constructor) 지원 중단 (PHP 8.0에서 제거)**
   - PHP 5.6 이하: `function ClassName()` 형태로 클래스명과 동일한 메서드를 생성자로 인식.
   - PHP 8.x: `__construct()` 표준 메서드만 생성자로 인정하며 구형 스타일 메서드는 무시되거나 에러 발생.
3. **엄격해진 타입 및 널(Null)/미정의 객체 검사 (`Fatal Error` 전환)**
   - PHP 5.6 이하: 미정의 변수(`$arr['undefined_key']`)나 `null` 전달 시 `Notice`/`Warning` 경고 후 억지로 실행.
   - PHP 8.x: `TypeError` 또는 `Fatal Error`를 발생시켜 스크립트 실행을 즉시 중단(500 Internal Server Error).
4. **예약어(Reserved Keywords) 충돌 및 동적 코드 실행 제거**
   - `String`, `Object`, `Mixed` 등 레거시 코드의 클래스명이 PHP 7/8 예약어와 충돌.
   - `create_function()` 및 `preg_replace()` `/e` (eval) 옵션 제거로 동적 템플릿 컴파일러 파싱 불가.

### 3.2 PHP 8.x 지원을 위한 소스 코드 수정 여부 및 마이그레이션 전략

PHP 8.x 환경에서 레거시 사이트를 구동하려면 **방대한 양의 소스 코드 수정이 필수적**입니다. 단순 수정을 넘어선 대응 전략 3가지는 다음과 같습니다.

| 대응 방안 | 작업 내용 | 장점 | 단점 및 리스크 |
|---|---|---|---|
| **방안 A: 레거시 소스 직접 수정 (PHP 8.x 리팩토링)** | XE 코어 및 수십 개 모듈/위젯의 구형 PHP 문법을 일일이 디버깅하여 8.x 호환 코드로 수정 | 기존 파일 구조 및 템플릿 유지 | ❌ **비추천**: 수만 줄의 구형 코드를 일일이 고쳐야 하므로 공수가 극도로 크고 버그 위험 높음 |
| **방안 B: 라이믹스(Rhymix)로 엔진 업그레이드** | XE의 오픈소스 후속작인 Rhymix(PHP 7.4 - 8.2+ 공식 지원)로 CMS 엔진 교체 | PHP 8.x 환경 지원 및 XE DB 데이터 100% 호환 | ⚠️ 서드파티 구형 테마/위젯 중 라이믹스 미지원 부품은 호환성 개작 필요 |
| **방안 C: Docker 격리 보존 + 신규 웹(Next.js) 이관 (현재 전략)** ⭐ | - **과거 데이터**: Docker(PHP 5.6) 샌드박스로 안전하게 원본 형태 보존<br>- **신규 웹**: Next.js / PostgreSQL 기술 스택으로 데이터 이전 | ✅ - 최신 OS 보안 확보<br>✅ - 구형 코드 수정 공수 0<br>✅ - 현대적인 UI/UX 구축 | 과거 데이터 이관 스크립트 작성 필요 |

> 💡 **한눈에 이해하는 쉬운 설명**
> - **비유하자면**: 20년 전에 나온 옛날 자동차(PHP 5.6 코드)에 최신 차세대 엔진(PHP 8.x)을 무작정 얹으면 부품 규격이 전혀 맞지 않아 차가 출발도 못 하고 고장납니다.
> - 차 부품 전체를 싹 다 뜯어고쳐서 튜닝(수만 줄 코드 수정)하려면 돈과 시간이 너무 많이 듭니다.
> - 그래서 **옛날 자동차는 도커라는 안전한 박물관에 그대로 보관 서빙**하고, **앞으로 교회가 사용할 웹 앱은 최신 기술(Next.js)로 새로 뽑는 것**이 훨씬 효율적이고 똑똑한 전략입니다.

---

## 4. 🌐 Nginx의 역할 및 리버스 프록시 아키텍처

신규 서버 환경에서 Nginx는 호스트의 **Port 80(HTTP)**과 **Port 443(HTTPS)**을 독점 서빙하며, 외부 요청을 수신하여 내부 서비스로 전달해 주는 **중앙 리버스 프록시(Reverse Proxy) 게이트웨이** 역할을 수행합니다.

### 4.1 Nginx의 4대 핵심 역할

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

> 💡 **한눈에 이해하는 쉬운 설명**
> - **Nginx는 건물 정문의 '스마트 경비원 겸 안내원'입니다.**
> - 1) **보안 검사 (SSL/HTTPS)**: 손님이 오면 안전하게 암호화된 보안 봉투(SSL)로 주고받는지 검사합니다.
> - 2) **자물쇠 자동 갱신**: 자물쇠 유효기간이 지나기 전에 알아서 새 자물쇠(Let's Encrypt)로 바꿔 달아줍니다.
> - 3) **길 안내 (리버스 프록시)**: "교회 메인 홈페이지 보러 왔어요" 하면 8080번 방으로, "지출결의서 작성하러 왔어요" 하면 3000번 방으로 안내해 줍니다.
> - 4) **옛날 주소 자동 교정**: 손님이 옛날 구주소(`http://`)를 적어 내도 알아서 신주소(`https://`)로 고쳐서 연결해 줍니다.

---

## 5. 🧠 이전 레거시 개발자가 알아야 할 주요 사항

> ⚠️ **경고 — 서버 아키텍처 및 포트 수칙**
> 1. **메인 홈페이지 (`www.hyanglin.org`)**: `/var/www/hyanglin-legacy` (Port `8080` / Docker PHP 5.6 XE)
> 2. **재정 관리 시스템 (지출결의서)**: `/var/www/hyanglin-finance/web` (Port `3000` / Host PM2 Next.js)
> 
> *3000번 포트는 재정 시스템 전용 포트이므로, Nginx 설정에서 메인 홈페이지 프록시 대상을 3000번으로 잘못 변경해서는 안 됩니다.*

### 5.1 소스 코드 수정 및 템플릿 캐시 관리
- **웹 루트 경로**: `/var/www/hyanglin-legacy` 디렉터리가 Docker 컨테이너 내부 웹 디렉터리로 마운트되어 있습니다.
- **XE 템플릿 캐시 초기화**: XE 템플릿이나 PHP 소스를 변경한 후 변경 사항이 즉시 보이지 않을 경우, XE 내부 캐시 디렉터리(`files/cache/`)를 삭제하거나 백오피스(관리자 페이지)에서 캐시를 재생성해야 합니다.

### 5.2 데이터베이스(MySQL) 접속 방식
- 호스트 외부에서 직접 접근하는 기본 MySQL 포트는 `3307`로 바인딩되어 있으며, Docker 내부 컨테이너 통신 시 서비스명 `legacy-mysql`을 사용합니다.
- 캐릭터셋은 `utf8mb4`로 통합 관리됩니다.

### 5.3 서버 명령어 체계 변경
| 작업 내용 | 과거 (CentOS / Apache) | 현재 (Ubuntu / Docker) |
|---|---|---|
| **웹 서버 재구동** | `sudo service httpd restart` | `cd ~/hyanglin-legacy && sudo docker compose restart nginx` |
| **메인 홈페이지 재구동** | `sudo service httpd restart` | `cd /var/www/hyanglin-legacy && sudo docker compose restart` |
| **SSL 인증서 수동 갱신** | 수동 스크립트 실행 | `sudo ./init-letsencrypt.sh` |
| **로그 확인** | `tail -f /var/log/httpd/error_log` | `sudo docker compose logs -f nginx` |

> 💡 **한눈에 이해하는 쉬운 설명**
> - **포트 번호 주의**: 3000번 방은 '지출결의서(재정)' 방입니다. 홈페이지(8080번 방)와 방 번호가 다르니 안내원(Nginx)에게 "홈페이지 손님을 3000번 방으로 보내라"고 지시하면 절대 안 됩니다.
> - **화면 미반영 시**: 소스코드를 수정했는데 화면이 바뀌지 않으면 옛날 임시 저장 데이터(`files/cache/`)를 지워주면 바로 반영됩니다.
> - **서버 재부팅 명령어**: 옛날처럼 `service httpd restart` 대신 `docker compose restart` 명령어를 사용합니다.

---

## 6. 📂 전체 폴더 구조 및 시스템 배치

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

> 💡 **한눈에 이해하는 쉬운 설명**
> - 메인 홈페이지 관련 도커 설정, Nginx 프록시 설정, 서버 안내 문서는 모두 `/var/www/hyanglin-legacy` 폴더에 들어있습니다.
> - 지출결의서 전용 재정 앱은 바로 옆 `/var/www/hyanglin-finance` 폴더에 깔끔하게 분리되어 있습니다.

---

## 7. 🚀 마이그레이션 실행 제안 (과거 데이터 보존 & 신규 웹 이관)

본 제안은 레거시 향린교회 웹 자산을 안전하게 보호하면서, 동시에 현대적인 신규 웹 서비스로 안착하기 위한 2원화 마이그레이션 실행 전략입니다.

### 7.1 과거 데이터: Docker(PHP 5.6) 샌드박스로 안전하게 원본 형태 보존

> [!TIP]
> **구 KT 서버 레거시 홈페이지 직접 접속 안내**
> 브라우저의 기존 캐시나 튕김 없이 **http://14.63.198.35/**로 접속하시면 외부로 튕기지 않고 KT 서버의 향린교회 레거시 홈페이지가 바로 선명하게 나타납니다!
> 👉 [http://14.63.198.35/](http://14.63.198.35/) 또는 [http://14.63.198.35/home/](http://14.63.198.35/home/)

1. **보존 목적**: 20년 이상 누적된 교회의 역사적 자산(과거 주보, 하늘뜻펴기, 교우 자유게시판, 앨범 데이터 등)을 단 1건의 손실 없이 원본 그대로 유지합니다.
2. **보존 방식**:
   - 구 서버(`14.63.198.35`)에서 추출한 전체 DB(`hr2`, `hr`) 및 첨부파일(`/home/hr/www/files/`)을 Docker 컨테이너(PHP 5.6 + MySQL 5.7) 내부 샌드박스로 구동합니다.
   - Nginx 리버스 프록시를 통해 `https://www.hyanglin.org` 하위의 아카이브/레거시 경로로 안전하게 조회가 가능하도록 연결합니다.
3. **핵심 이점**:
   - 지원이 종료된 구형 PHP 코드를 현대적 엔진으로 개작하는 데 드는 막대한 개발 공수와 예산을 0으로 절감합니다.
   - 최신 호스트 OS(Ubuntu 22.04) 및 Nginx 리버스 프록시 샌드박싱을 적용하여 과거 취약점으로 인한 호스트 침투 위험을 완벽히 차단합니다.

### 7.2 신규 웹: Next.js / PostgreSQL 기술 스택으로 데이터 이전

1. **구축 목적**: 반응형 모바일 UI/UX, 초고속 검색 기능, 강화된 웹 보안 및 유지보수 편의성을 제공하는 현대적인 교인 웹 서비스를 제공합니다.
2. **신규 기술 스택**:
   - **프론트엔드/백엔드**: Next.js (React 기반 App Router, Server Component & SSR/SSG 지원)
   - **데이터베이스**: PostgreSQL (표준 RDBMS, 텍스트 검색 및 유니코드 인코딩 최적화)
3. **단계별 이관 절차**:
   - **1단계 (ETL 데이터 추출 및 정제)**: XE MySQL의 게시글(`xe_documents`), 댓글(`xe_comments`), 사용자 데이터 중 핵심 게시판 카테고리(하늘뜻펴기, 주보, 공지사항 등)를 추출하여 PostgreSQL 데이터베이스 스키마로 이관합니다.
   - **2단계 (HTML & 에셋 클리닝)**: XE 게시글 내부의 구형 HTML 인라인 스타일 및 상대 경로 이미지 URL을 현대적인 마크다운/Clean HTML 및 HTTPS 에셋으로 자동 변환합니다.
   - **3단계 (서비스 라우팅 전환)**: Nginx 전면 프록시를 통해 신규 Next.js 웹 앱을 기본 메인 서비스로 서빙하고, 과거 미이관 보존 데이터는 레거시 아카이브 메뉴로 자연스럽게 연결합니다.

> 💡 **한눈에 이해하는 쉬운 설명**
> - **과거 데이터 타임캡슐**: 지난 20년 동안 교우분들이 작성해 주신 귀한 게시글, 사진, 주보는 **도커라는 튼튼한 방수 타임캡슐(PHP 5.6 샌드박스)**에 넣어 원본 그대로 안전하게 보존합니다.
> - **새로운 스마트 웹**: 매일 접속하는 메인 홈페이지와 지출결의서는 **최신 스마트폰 지원 현대식 웹(Next.js / PostgreSQL)**으로 새로 제작하여 필수 데이터를 정갈하게 옮겨옵니다.
> - 이렇게 하면 옛날 데이터 유실 걱정 없이, 보안과 반응속도 모두를 잡을 수 있습니다!

---

## 8. 📌 레퍼런스 문서
- [README.md](file:///home/hyuk/prj/hyanglin-legacy/README.md) — 프로젝트 메인 개요
- [AGENTS.md](file:///home/hyuk/prj/hyanglin-legacy/AGENTS.md) — 서버 아키텍처 수칙 및 스킬 규정
- [docs/legacy-homepage.md](file:///home/hyuk/prj/hyanglin-legacy/docs/legacy-homepage.md) — 구 원격 서버(`14.63.198.35`) 분석서
