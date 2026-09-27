---
name: health-check
description: 향린교회 프로덕션 서버(45.115.154.229)의 주요 서비스 및 이관된 전체 웹사이트, 인프라 상태를 1단계(핵심)/2단계(전체)로 자동 검증하고 결과를 PASS/FAIL/WARN으로 리포트하는 헬스 체크 스킬.
---

# 🏥 프로덕션 서버 헬스 체크 스킬

가비아 프로덕션 서버(45.115.154.229)의 서비스 가용성과 인프라 상태를 체계적으로 검증한다.
상황에 따라 **1단계(핵심 서비스)**와 **2단계(이관 전체 웹사이트)**로 나누어 실행할 수 있다.

## 📌 트리거 조건

- 사용자가 "헬스 체크", "서버 상태 확인", "health check" 등을 요청했을 때
- "1단계 헬스체크", "2단계 헬스체크", "전체 헬스체크" 요청 시
- 서버 장애 조치 후 복구 확인이 필요할 때
- 정기 점검 시 (수동 또는 `/schedule` 명령)

---

## 🛠️ 실행 방법

프로젝트 루트에서 스크립트를 실행한다:

```bash
# 1단계: 향린 메인 홈페이지 + 향린 재정 + 기본 인프라 (기본값)
bash .agents/skills/health-check/scripts/health-check.sh 1

# 2단계: 이관된 전체 13개 웹 사이트 + 전체 인프라
bash .agents/skills/health-check/scripts/health-check.sh 2
# 또는
bash .agents/skills/health-check/scripts/health-check.sh all
```

스크립트는 로컬 네트워크(HTTPS/SSL)와 SSH를 통해 프로덕션 서버에 접속하여 웹 응답, SSL 만료일, 프로세스 및 인프라를 순차 검증하고 PASS/FAIL/WARN 리포트를 출력한다.

---

## 📋 단계별 검증 범위

### [1단계] 핵심 서비스 (향린 홈 + 향린 재정)
주요 서비스의 연속성과 가용성을 빠르게 점검할 때 사용합니다.

1. **향린교회 메인 홈페이지 (`www.hyanglin.org`, 내부 8080)**
   - 메인 페이지 응답 (내부 8080 HTTP 200, 본문 크기 > 10KB)
   - 출석체크 비인증 차단 (HTTP 403)
   - 출석체크 인증 접근 (유효 세션키 접근 시 HTTP 200)
   - 향린 SSL 인증서 만료일 점검
2. **향린 재정 시스템 (`finance.hyanglin.org`, 내부 3000)**
   - 외부 HTTPS 서빙 응답 (HTTP 200) 및 내부 포트 3000 폴백
   - Host PM2 프로세스 상태 (`hyanglin-finance` online 여부)
   - 재정 SSL 인증서 만료일 점검 (≥ 14일 PASS, 7-13일 WARN, < 7일 FAIL)
3. **기본 인프라**
   - 루트(`/`) 및 데이터(`/data`) 파티션 디스크 사용률 (< 80%)
   - Docker 컨테이너 구동 수 점검

---

### [2단계] 전체 서비스 (이관된 전체 13개 웹 사이트)
1단계 항목 전체를 포함하며, 가비아 서버로 이관된 모든 웹 사이트의 가용성과 SSL을 전수 검사합니다.

| # | 사이트명 | 도메인 | 내부 포트 | 컨테이너 |
|---|---|---|---|---|
| 1 | **향린교회 메인** | `www.hyanglin.org` | 8080 | web |
| 2 | **향린 재정 관리** | `finance.hyanglin.org` | 3000 | Host PM2 |
| 3 | **안병무도서관** | `www.ahn-library.org` | 8087 | ahn-library |
| 4 | **이양노 갤러리** | `www.ongallery.co.kr` | 8081 | ongallery |
| 5 | **법무법인오늘** | `www.xn--wh1b76ni4aba943jj2b.com` | 8091 | cnblaw |
| 6 | **박형규 기념사업회** | `www.parkhyungkyu.org` | 8082 | parkhk |
| 7 | **해랑** | `www.haerangart.com` | 8083 | haerangart |
| 8 | **교육비평** | `www.educrit.org` | 8084 | educrit |
| 9 | **길목** | `www.gilmok.org` | 8085 | gilmok |
| 10 | **심원 아카이브** | `www.simwon.org` | 8086 | simwon |
| 11 | **로로브레인** | `www.rorobrain.com` | 8088 | rorobrain |
| 12 | **비북** | `www.b-book.co.kr` | 8089 | bbook |
| 13 | **시네마 버킷리스트** | `www.cinemabucketlist.com` | 8090 | moviediary |

- 각 사이트별 외부 HTTPS 정상 응답(200) 확인 및 내부 포트 폴백 점검
- 각 도메인별 Let's Encrypt SSL 인증서 잔여 기간 검사
- 전체 13개 이상 Docker 컨테이너 정상 구동 여부 검증

---

## 📢 텔레그램 장애 알림 연동

헬스체크 검증 중 장애(FAIL)가 감지되었을 때, 텔레그램 채널이나 그룹으로 즉시 실시간 경보 메시지를 자동 발송할 수 있다.

### 설정 방법
`~/.config/hyanglin/telegram.conf` 파일에 봇 토큰과 채널 ID를 설정하거나 환경변수로 주입한다:

```bash
# ~/.config/hyanglin/telegram.conf
TELEGRAM_BOT_TOKEN="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
TELEGRAM_CHAT_ID="-1001234567890"  # 채널 ID 또는 @채널사용자명
```

- 평소(정상 검증 완료)에는 알림을 보내지 않고 콘솔 및 로그에만 출력한다.
- 1개 이상의 항목이 **FAIL** 판정되면 즉시 텔레그램 채널로 마크다운 형식의 장애 내역과 시각을 자동 송출한다.

---

## ⚠️ 전제 조건 및 보안

- SSH 키 인증 및 `sshy` 접속 설정이 되어 있어야 한다 (`sshy` 별칭, `~/.ssh/config`의 `Host sshy`, 또는 `SSH_CMD` 환경변수)
- mountain 머신 IP에서만 SSH 접속 가능 (방화벽 제약)
- 이 스킬은 **READ-ONLY** 작업만 수행한다 (서버 변경 없음)
- SSH 명령 실행 시 `prod-server-guard`의 사전 승인 절차를 준수한다.

