#!/usr/bin/env bash
# =============================================================================
# setup-hooks.sh
# Git 훅 설치 스크립트 (.bin/hooks/* -> .git/hooks/*)
# =============================================================================

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." >/dev/null 2>&1 && pwd)"
HOOKS_DIR="$REPO_ROOT/.git/hooks"
SOURCE_HOOKS_DIR="$REPO_ROOT/.bin/hooks"

if [ ! -d "$HOOKS_DIR" ]; then
    echo "❌ Error: .git/hooks 디렉터리를 찾을 수 없습니다."
    exit 1
fi

mkdir -p "$HOOKS_DIR"

for hook in "$SOURCE_HOOKS_DIR"/*; do
    if [ -f "$hook" ]; then
        hook_name=$(basename "$hook")
        cp "$hook" "$HOOKS_DIR/$hook_name"
        chmod +x "$HOOKS_DIR/$hook_name"
        echo "✅ Hook 설치 완료: .git/hooks/$hook_name"
    fi
done

echo "🎉 모든 Git Hook 설치가 완료되었습니다."
