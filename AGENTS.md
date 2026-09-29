<!-- BEGIN:skills-inventory -->
# 스킬 인벤토리 (Skills Inventory)

이 프로젝트에는 다음 스킬이 설치되어 있습니다. 스킬 파일 경로는 `.agents/skills/` 하위에 위치합니다.

| 스킬 | 용도 | Antigravity |
|---|---|---|
| `legacy-docker-migration` | 지원이 종료된(EOL) 레거시 웹 서비스/홈페이지를 Docker 환경으로 안전하게 마이그레이션하고 격리·보안화하는 베스트 프랙티스 가이드 | 자동 로딩 |
| `migration-verification-expert` | 이전(Migration) 작업 시 스크린샷 등 표면적인 결과에 의존하지 않고, 실제 서버 환경을 기반으로 철저하게 검증(채점)하는 전문가 스킬 | 자동 로딩 |
| `prod-server-guard` | 향린 가비아 프로덕션 서버(45.115.154.229, sshy) 접속, SSH/SCP, Docker, 파일 편집 및 배포 전 경고 배너 표시 및 확인 스킬 | 자동 로딩 |
| `darkmode` | 웹 애플리케이션 및 CSS 개발 시 다크 모드(Dark Mode) 테마 시스템 구축, 다크 모드 고대비 가독성 보장(Contrast Invariance), localStorage 연동 수칙 스킬 | 자동 로딩 |
| `health-check` | 프로덕션 서버(www.hyanglin.org)의 홈페이지, 출석체크, SSL, 디스크, Docker 상태를 자동 검증하고 PASS/FAIL/WARN 리포트를 출력하는 헬스 체크 스킬 | 자동 로딩 |
<!-- END:skills-inventory -->

<!-- BEGIN:nextjs-agent-rules -->
# This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` before writing any code. Heed deprecation notices.
<!-- END:nextjs-agent-rules -->

<!-- BEGIN:anti-strikethrough-rules -->
# Global Rule: Anti-Strikethrough (마크다운 물결표 제약)

## 🎯 목표
옵시디언(Obsidian) 및 기타 마크다운 렌더러에서 두 개의 물결표(`~`)가 등장할 때 그 사이의 텍스트가 의도치 않게 **취소선(Strikethrough)**으로 처리되는 렌더링 버그를 방지합니다.

## ⚠️ 제약 사항 (Constraints)
1. **숫자 범위(Range) 표현 시 `~` 사용 금지:**
   - ❌ 잘못된 예: `20~24px`, `40~45자`
   - ✅ 올바른 예: `20-24px`, `20에서 24px`, `40에서 45자`
2. **부득이한 사용 시 이스케이프 처리:**
   - 반드시 물결표 기호 자체를 출력해야 한다면 백슬래시(`\`)를 사용하여 이스케이프(`\~`) 처리하거나 양옆에 공백(` ~ `)을 두십시오.

## 🤖 AI 행동 지침
모든 AI 봇/에이전트는 사용자에게 답변을 생성하거나 마크다운 문서(`.md`)를 작성할 때, 위 제약 사항을 기본 포맷팅 규칙으로 항상 적용해야 합니다.
<!-- END:anti-strikethrough-rules -->

<!-- BEGIN:architecture-rules -->
# 🏢 가비아 프로덕션 서버 서비스 아키텍처 수칙

모든 에이전트는 서버 구동 서비스와 포트 역할을 절대 혼동해서는 안 됩니다:

1. **향린교회 메인 홈페이지 (`www.hyanglin.org`, `hyanglin.org`)**:
   - **위치**: `/var/www/hyanglin-legacy` (Docker PHP 5.6 / XE)
   - **포트**: `8080` (Docker Nginx가 443 HTTPS로 역프록시)
2. **향린 재정 관리 시스템 (지출결의서 전용)**:
   - **위치**: `/var/www/hyanglin-finance/web` (Host PM2 Next.js)
   - **포트**: `3000`

> ⚠️ **경고**: 3000번 포트(재정 시스템)를 메인 홈페이지로 오판하여 Nginx 프록시를 3000번으로 변경하는 실수를 절대 범하지 말 것!

## 🕐 서버 다운타임 허용 정책

향린교회 레거시 홈페이지(`hyanglin.org`)는 **다운타임이 크게 문제되지 않는 서비스**입니다.

- 교인 소수가 이용하는 정적 성격의 홈페이지로, 24시간 무중단 SLA가 요구되지 않음
- 이관·배포·서버 작업 시 사전 공지 없이 수분에서 수십 분 내외의 중단이 허용됨
- 단, **재정 시스템(포트 3000)** 은 별도 서비스이므로 동일하게 취급하지 말 것
<!-- END:architecture-rules -->
# Project Core Rules & Architecture (SSOT)

이 문서는 `hyanglin-legacy` 프로젝트의 단일 진실 원천(Single Source of Truth, SSOT)입니다.

## 1. 프로젝트 목적 (Main Goal)
- **플랫폼 및 환경 이전**: 기존 레거시 홈페이지 데이터를 추출하여 최신 웹 플랫폼 및 개발 환경에 맞게 새롭게 재구축 (Migration).
- **인프라(하드웨어/OS) 이전**: 기존 CentOS 기반의 구형 서버에서 최신 Linux Ubuntu 서버 환경으로 서버 하드웨어 및 OS 인프라 전체를 마이그레이션.
- **데이터 자산화 (보석화)**: 과거 레거시 데이터를 단순 백업하는 것을 넘어, 향린 역사 자료로 보존하고 자체 LLM 모델 학습 및 Knowledge Base 구축 등 다양한 미래 가치 창출 용도로 활용하기 위함.

## 2. 하네스 엔지니어링 (Harness Engineering) 규칙
- **SSOT 준수**: 프로젝트의 기본 설정, 룰, 아키텍처는 항상 이 문서(`1prd.md`)를 기준으로 합니다.
- **Git Hook 강제**: 모든 커밋 메시지에는 `#이슈번호` 형식의 문자열이 포함되어야 합니다. (`.git/hooks/commit-msg`에 의해 강제됨)
- **Deterministic Gate**: `.bin/` 디렉토리 내의 스크립트나 주요 룰 문서를 수정/추가할 경우, 반드시 `.bin/harness-check.sh`를 실행하여 검증을 통과해야 합니다.
- **TDD 기반**: 작업 시 `1-2-3-tdd-doc-global` 등 설정된 워크플로를 따릅니다.

## 3. 서버 접속 정보 (Server Connection Info)

- **레거시(KT) 서버 접속 방법**: `ssh -p 2222 root@14.63.198.35`
- **새(가비아) 서버 접속 방법**: 터미널에서 `sshy` 알리아스 입력 (명령어: `ssh -p 2222 wonhyukc@45.115.154.229`)

> **[접속 시 주의사항]**
> - **AI 접속 원칙**: 암호 입력 없이 **키(Key)**로만 접속해야 합니다. 키는 KeePass에 등록되어 ssh-agent를 통해 제공됩니다.
> - **방화벽 제약**: 방화벽 설정으로 인해 오직 **mountain 머신 IP**에서만 접속이 허용됩니다. 접속이 안 될 때는 이 방화벽 설정을 먼저 인지하고 대처 메시지를 출력해야 합니다.

## 4. 가비아 프로덕션 서버 아키텍처 및 포트 할당 (SSOT)

| 서비스 구분 | 실구동 위치 | 포트 | 바인딩 도메인 / 설명 |
|---|---|---|---|
| **향린교회 메인 홈페이지** | Docker (PHP 5.6 / XE)<br>`/var/www/hyanglin-legacy` | `8080` | `www.hyanglin.org`, `hyanglin.org`<br>성도 및 방문자용 공식 메인 홈페이지 (Docker Nginx가 443 프록시) |
| **향린 재정 관리 시스템** | Host PM2 (Next.js)<br>`/var/www/hyanglin-finance/web` | `3000` | 교인 재정 관리 및 지출결의서 전용 시스템 |
| **도커 Nginx & Certbot** | Docker Compose<br>`~/hyanglin-legacy` | `80`, `443` | Let's Encrypt SSL 종단 및 HTTPS 프록시, CSP 보안 헤더 탑재 |

> **[AI 에이전트 주의사항]**
> `3000`번 포트(Next.js 재정 앱)와 `8080`번 포트(메인 홈페이지 PHP/XE)를 절대로 혼동하지 말 것! `www.hyanglin.org` 메인 타겟은 반드시 `8080`번 포트(레거시 홈페이지)여야 합니다.

## 5. 운영 레퍼런스

- **전체 사이트 목록**(도메인, 포트, 컨테이너, CMS, 보안 현황)은 [docs/sites.md](docs/sites.md)를 참조한다.
- 서버 아키텍처, Docker 컨테이너 맵, 볼륨 마운트, 설정 파일 위치, 장애 조사 체크리스트 등 상세 운영 정보는 [docs/ops-reference.md](docs/ops-reference.md)를 참조한다.

## 6. 임시 파일 규칙

- 모든 임시 파일(편집용 복사본, 스크래치, 테스트 파일, 이슈 본문 등)은 반드시 `tmp/` 폴더에 생성한다.
- 프로젝트 루트나 다른 디렉터리에 직접 임시 파일을 만들지 않는다.
- `tmp/`는 `.gitignore`에 등록되어 있으므로 커밋 대상에서 자동 제외된다.

## 7. 서버 반영 코드 및 소스 패치의 End-to-End 배포 원칙 (SSOT)

- **로컬 관리 디렉터리의 SSOT 준수**: `patches/`(웹 소스), `server-scripts/`(크론·모니터링·백업), `nginx/`(웹서버 설정) 등 서버에서 실행되는 모든 코드는 로컬 저장소를 SSOT로 관리한다.
- **반영 전 로컬 검증 필수**: 실서버 장애 방지를 위해 로컬에서 문법, 동작 검증, 브라우저 시뮬레이션 등을 마친 후 배포를 준비한다.
- **표준 작업 사이클 (End-to-End 완결 원칙)**:
  `[로컬 소스/스크립트 수정]` ➔ `[로컬 검증]` ➔ `[로컬 Git 커밋]` ➔ `[prod-server-guard 경고 및 사용자 확인]` ➔ `[서버 SCP 전송/배포 및 실서버 검증]`
- **로컬 커밋 단독 종결 금지 (CRITICAL)**: 서버에서 실행되는 스크립트나 설정을 수정한 경우, 로컬 커밋만 하고 작업을 끝내서는 안 되며, 반드시 동일 세션 내에서 `prod-server-guard` 가드를 지켜 실서버 배포 및 검증까지 완결해야 한다.
- **서버 단독 수정 금지**: 서버에서만 파일을 직접 고치고 로컬 Git에 남기지 않는 행위를 금지한다. 서버 장애, 컨테이너 재빌드, 다음 서버 이전 시 수정 내역이 모두 유실되기 때문이다.

## 8. 보안/인프라 변경 전 이력 확인 의무

보안 방어, Rate Limiting, 방화벽 규칙, 서버 설정 변경을 제안하기 전에 반드시 `gh issue list --state all`로 관련 과거 이슈를 조회하여 이전 시도의 부작용을 확인한다. **이전에 롤백된 방법을 다시 제안하지 않는다.**

- 변경 제안 전: `gh issue list --state all --limit 30` 및 관련 키워드 검색
- 과거에 실패하거나 부작용이 발생한 접근법이 있다면, 해당 이슈를 명시하며 대안을 제시한다
- 예시: Nginx Rate Limiting이 이미지 깨짐을 유발했다면 (#44), fail2ban 등 다른 방법을 1순위로 제안

## 9. 사후 유사 이슈 전수 탐색 제안 규칙 (Proactive Sweep Prompt)

버그 수정, 마이그레이션 스크립트 수정, 인프라/설정 변경 등 연쇄 영향이 발생할 수 있는 작업을 완료했을 때, 다짜고짜 다른 파일들을 임의로 수정하여 범위를 넓히지 않는다:

1. **당면 작업 우선 완결**:
   - 사용자가 요청한 대상 파일 및 이슈를 먼저 정확히 해결하고 기본 검증을 마친다.
2. **사후 탐색 능동 제안**:
   - 작업 완료 보고 시점에, 독단적으로 전수 수정을 벌이지 말고 사용자에게 먼저 후속 탐색 여부를 정중히 묻는다.
   - 예시 질문:
     > "해당 수정 작업을 완료했습니다. 혹시 코드베이스 내에 동일한 패턴이나 유사한 잠재 이슈가 있는 다른 곳이 더 있을지 한 번 더 전수 조사해볼까요?"
3. **사용자 승인 후 진행**:
   - 사용자가 "찾아봐", "응", "진행해" 등으로 승인한 경우에만 추가 전수 검색(`git grep` 등) 및 후속 작업을 개시한다.
