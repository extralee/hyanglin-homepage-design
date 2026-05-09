#!/usr/bin/env bash

echo "[Harness Check] 검증을 시작합니다..."

# 1. SSOT (1prd.md) 존재 여부 확인
if [ ! -f "1prd.md" ]; then
    echo "❌ Error: 1prd.md (SSOT) 파일을 찾을 수 없습니다."
    exit 1
fi
echo "✅ 1prd.md 확인 완료."

# 2. .bin 내 스크립트 문법 검사 (Syntax Check)
for script in .bin/*.sh; do
    if [ -f "$script" ]; then
        bash -n "$script"
        if [ $? -ne 0 ]; then
            echo "❌ Error: $script 스크립트에 문법 오류가 있습니다."
            exit 1
        fi
    fi
done
echo "✅ 쉘 스크립트 문법 확인 완료."

# 3. 추가적인 검증 로직이 필요하다면 이 아래에 작성합니다.

echo "[Harness Check] 모든 검증을 성공적으로 통과했습니다!"
exit 0
