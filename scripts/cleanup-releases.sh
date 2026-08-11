#!/bin/bash
# 릴리스 디렉터리 자동 cleanup 스크립트
# - Test 환경 (/var/www/hyanglin-finance/test/releases): 최신 2개 보존
# - Prod 환경 (/var/www/hyanglin-finance/web/releases): 최신 3개 보존

set -e

# 1. Test 환경 (최신 2개 유지)
if [ -d "/var/www/hyanglin-finance/test/releases" ]; then
    cd /var/www/hyanglin-finance/test/releases && ls -1t | tail -n +3 | xargs -I {} rm -rf "{}"
fi

# 2. Prod 환경 (최신 3개 유지)
if [ -d "/var/www/hyanglin-finance/web/releases" ]; then
    cd /var/www/hyanglin-finance/web/releases && ls -1t | tail -n +4 | xargs -I {} rm -rf "{}"
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Release cleanup completed."
