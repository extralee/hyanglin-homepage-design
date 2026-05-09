# Project Core Rules & Architecture (SSOT)

이 문서는 `hyanglin-legacy` 프로젝트의 단일 진실 원천(Single Source of Truth, SSOT)입니다.

## 1. 프로젝트 목적 (Main Goal)
- **플랫폼 및 환경 이전**: 기존 레거시 홈페이지 데이터를 추출하여 최신 웹 플랫폼 및 개발 환경에 맞게 새롭게 재구축 (Migration).
- **인프라(하드웨어/OS) 이전**: 기존 CentOS 기반의 구형 서버에서 최신 Linux Ubuntu 서버 환경으로 서버 하드웨어 및 OS 인프라 전체를 마이그레이션.

## 2. 하네스 엔지니어링 (Harness Engineering) 규칙
- **SSOT 준수**: 프로젝트의 기본 설정, 룰, 아키텍처는 항상 이 문서(`1prd.md`)를 기준으로 합니다.
- **Git Hook 강제**: 모든 커밋 메시지에는 `#이슈번호` 형식의 문자열이 포함되어야 합니다. (`.git/hooks/commit-msg`에 의해 강제됨)
- **Deterministic Gate**: `.bin/` 디렉토리 내의 스크립트나 주요 룰 문서를 수정/추가할 경우, 반드시 `.bin/harness-check.sh`를 실행하여 검증을 통과해야 합니다.
- **TDD 기반**: 작업 시 `1-2-3-tdd-doc-global` 등 설정된 워크플로를 따릅니다.
