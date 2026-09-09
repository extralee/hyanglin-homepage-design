#!/usr/bin/env bash
# Browser-sync runner script for hyanglin-legacy

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../../../../" && pwd)"

TARGET_INPUT="${1:-}"
PORT="${2:-}"

# 인자 판별: 파일 경로가 주어진 경우
if [ -n "$TARGET_INPUT" ] && [ -f "$TARGET_INPUT" -o -f "$REPO_ROOT/$TARGET_INPUT" ]; then
    if [ -f "$TARGET_INPUT" ]; then
        TARGET_FILE="$(realpath "$TARGET_INPUT")"
    else
        TARGET_FILE="$(realpath "$REPO_ROOT/$TARGET_INPUT")"
    fi
    SERVE_DIR="$(dirname "$TARGET_FILE")"
    START_FILE="$(basename "$TARGET_FILE")"
    PORT="${PORT:-3000}"
elif [[ "$TARGET_INPUT" =~ ^[0-9]+$ ]]; then
    PORT="$TARGET_INPUT"
    SERVE_DIR="$REPO_ROOT/prototype"
    START_FILE="index.html"
else
    SERVE_DIR="$REPO_ROOT/prototype"
    START_FILE="index.html"
    PORT="${PORT:-3000}"
fi

echo "=================================================="
echo " Starting Browser-Sync Server"
echo " Target Directory: $SERVE_DIR"
echo " Start File:       $START_FILE"
echo " Port:             $PORT"
echo "=================================================="

cd "$SERVE_DIR"
exec npx browser-sync start \
    --server "$SERVE_DIR" \
    --files "$SERVE_DIR/**/*" \
    --port "$PORT" \
    --startPath "$START_FILE" \
    --no-notify \
    --reload-delay 300
