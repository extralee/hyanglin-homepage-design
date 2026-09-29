#!/usr/bin/env bash

echo "[Harness Check] 검증을 시작합니다..."

# 1. SSOT (AGENTS.md / 1prd.md) 존재 여부 확인
if [ ! -f "AGENTS.md" ] && [ ! -f "1prd.md" ]; then
    echo "❌ Error: AGENTS.md (SSOT) 파일을 찾을 수 없습니다."
    exit 1
fi
echo "✅ SSOT (AGENTS.md) 확인 완료."

# 2. .bin 내 스크립트 및 Git Hook 문법 검사 (Syntax Check)
for script in .bin/*.sh .bin/hooks/*; do
    if [ -f "$script" ]; then
        bash -n "$script"
        if [ $? -ne 0 ]; then
            echo "❌ Error: $script 스크립트에 문법 오류가 있습니다."
            exit 1
        fi
    fi
done
echo "✅ 쉘 스크립트 및 Git Hook 문법 확인 완료."

# 2-1. Git Hooks 설치 상태 검증
if [ -d ".git/hooks" ]; then
    if [ ! -x ".git/hooks/pre-commit" ] || [ ! -x ".git/hooks/commit-msg" ]; then
        echo "⚠️ Git Hooks가 설치되지 않았거나 실행 권한이 없습니다. 자동 설치를 실행합니다..."
        .bin/setup-hooks.sh
    fi
    echo "✅ Git Hooks 설치 상태 확인 완료."
fi

# 3. Docker EOL & Security Check
echo "🔍 Checking Docker configurations for EOL runtimes and security settings..."
ROOT_DIR="."
DOCKER_FILES=$(find "$ROOT_DIR" -type f \( -name "Dockerfile" -o -name "docker-compose.yml" -o -name "docker-compose*.yml" \) -not -path "*/node_modules/*" -not -path "*/.git/*" -not -path "*/venv/*" -not -path "*/.venv/*" || true)

if [ -n "$DOCKER_FILES" ]; then
  for file in $DOCKER_FILES; do
    filename=$(basename "$file")
    # Check for EOL versions without bypass comment
    if grep -qE "(php:[45]\.|mysql:5\.[0-5]|python:2\.|node:(0\.|[468]\.|1[024]\.)|ubuntu:1[024]\.|centos:[567])" "$file"; then
      if ! grep -q "HARNESS-ALLOW-EOL" "$file"; then
        echo -e "❌ Error: EOL (End of Life) base image detected in $file"
        echo "Please upgrade the base image or add '# HARNESS-ALLOW-EOL: <reason>' comment to bypass this check."
        exit 1
      fi
    fi

    # Check for resource limits in docker-compose files
    if [[ "$filename" == docker-compose*.yml ]]; then
      if grep -q "image:" "$file" && ! grep -qE "(limits|cpus|memory)" "$file"; then
        echo "  ⚠ [WARNING] No resource limits configured in $file. It is highly recommended to set CPU/Memory limits for legacy containment."
      fi
    fi
  done
  echo "✅ Docker 환경설정 확인 완료."
else
  echo "✅ Docker 환경설정 파일 없음 (통과)."
fi

echo "[Harness Check] 모든 검증을 성공적으로 통과했습니다!"
exit 0
