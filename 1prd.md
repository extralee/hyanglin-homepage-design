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
