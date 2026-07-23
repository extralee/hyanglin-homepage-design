#!/bin/bash
# ──────────────────────────────────────────────────────────────────────
# local-security-guard.sh — 로컬 서버 기동 전 악성코드 탐지 가드
#
# 🔧 [CUSTOMIZE] 이 파일은 범용 템플릿이다.
#    프로젝트에 복사한 후 FILES 배열을 프로젝트의 감시 대상 파일로 수정하라.
#
# 목적: 개발 서버나 빌드를 실행하기 전에 주요 설정 파일이
#       공급망 공격으로 오염되지 않았는지 사전 검증한다.
#
# 사용법:
#   1. pre-commit 훅에서 자동 호출 (권장)
#   2. 개발 서버 시작 스크립트에서 사전 호출
#   3. 수동 실행: bash .bin/local-security-guard.sh
# ──────────────────────────────────────────────────────────────────────

echo "🔍 [Local Security Guard] 설정 파일 보안 무결성 검사 중..."

# ── 1. 감시 대상 파일 정의 ──
# 🔧 [CUSTOMIZE] 프로젝트에 맞게 대상 파일을 수정하세요.
# 공급망 공격의 주요 타겟: 빌드 설정(postcss, webpack, vite 등), lint 설정 파일
FILES=(
  "postcss.config.mjs"
  "postcss.config.js"
  ".eslintrc.js"
  "eslint.config.mjs"
  "vite.config.ts"
  "webpack.config.js"
  "next.config.mjs"
)

# ── 2. IoC(Indicator of Compromise) 스캔 ──
# IoC 집합은 CI(malware-scan.yml), pre-push, post-merge와 동일하게 유지해야 한다.
for f in "${FILES[@]}"; do
  if [ -f "$f" ]; then
    # A. 악성코드 워터마크·난독화 마커 존재 여부 검사
    # - 난독화 변수 마커: _$_1e42, _$af163278, _$_ccfc
    # - 워터마크 패밀리: global['!']='9-xxxx(-x)'
    if grep -qE "_\$_1e42|_\$af163278|_\$_ccfc|global\['!'\]='9-[0-9]+" "$f"; then
      echo ""
      echo "🚨 ========================================================"
      echo "🚨 [SECURITY ERROR] $f 파일에서 악성코드 서명이 발견되었습니다!"
      echo "🚨 -> 실행을 즉시 차단합니다. 브랜치를 확인하고 복구 가이드를 따르십시오."
      echo "🚨 ========================================================"
      exit 1
    fi

    # B. 한 줄의 길이가 4,000자를 초과하는지 검사
    # 난독화 페이로드는 수천 자의 코드를 한 줄에 숨기는 특성이 있다.
    MAX_LINE=$(awk '{ if (length>m) m=length } END{ print m+0 }' "$f")
    if [ "$MAX_LINE" -gt 4000 ]; then
      echo ""
      echo "🚨 ========================================================"
      echo "🚨 [SECURITY ERROR] $f 파일에 비정상적으로 긴 코드 라인(${MAX_LINE}자)이 존재합니다!"
      echo "🚨 -> 악성 난독화 페이로드 삽입이 의심되어 즉시 차단을 적용합니다."
      echo "🚨 ========================================================"
      exit 1
    fi

    # C. ESM 설정 파일에 createRequire 트릭이 끼어든 경우
    case "$f" in
      *.mjs)
        if grep -q 'createRequire(import.meta.url)' "$f"; then
          echo ""
          echo "🚨 [SECURITY ERROR] $f 에 createRequire(import.meta.url) 패턴이 존재합니다."
          echo "   ESM 빌드 설정에서 require()/child_process를 되살리는 페이로드 수법입니다."
          exit 1
        fi
        ;;
    esac
  fi
done

echo "✅ [Local Security Guard] 무결성 검사 통과. 안전하게 기동합니다."
exit 0
