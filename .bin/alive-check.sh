#!/usr/bin/env bash
# ─────────────────────────────────────────────
# 향린교회 홈페이지 외부 생존 감시 (로컬 PC에서 실행)
# 서버 자체가 다운되었을 때를 감지하는 최후 방어선
#
# systemd timer로 5분마다 실행
# 서버가 응답하지 않으면 텔레그램으로 경보 발송
# ─────────────────────────────────────────────

SITE_URL="https://www.hyanglin.org"
MAX_TIMEOUT=20
STATE_FILE="${HOME}/.cache/hyanglin-alive-state"

# ── 텔레그램 설정 로드 ────────────────────────
TELEGRAM_CONF="${HOME}/.config/hyanglin/telegram.conf"
if [[ -f "$TELEGRAM_CONF" ]]; then
  # shellcheck source=/dev/null
  source "$TELEGRAM_CONF"
fi

TELEGRAM_BOT_TOKEN="${TELEGRAM_BOT_TOKEN:-8037347881:AAEXiAPgEC3iEzv-6MwNC0AN2ughbtZqm3E}"
TELEGRAM_CHAT_ID="${TELEGRAM_CHAT_ID:--1004408048565}"

if [[ -z "${TELEGRAM_BOT_TOKEN:-}" || -z "${TELEGRAM_CHAT_ID:-}" ]]; then
  exit 0  # 설정 없으면 조용히 종료
fi

# ── 사이트 응답 확인 ──────────────────────────
http_code=$(curl -s -o /dev/null -w '%{http_code}' --max-time "$MAX_TIMEOUT" "$SITE_URL" 2>/dev/null || echo "000")

mkdir -p "$(dirname "$STATE_FILE")" 2>/dev/null

if [[ "$http_code" == "200" || "$http_code" == "301" || "$http_code" == "302" ]]; then
  # 복구됨 — 이전에 다운 상태였으면 복구 알림 전송
  if [[ -f "$STATE_FILE" ]]; then
    curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
      -d "chat_id=${TELEGRAM_CHAT_ID}" \
      -d "text=✅ *[향린교회 복구 알림]*%0A%0A📅 $(date '+%Y-%m-%d %H:%M KST')%0A서버가 다시 정상 응답합니다.%0A${SITE_URL} → HTTP ${http_code}" \
      -d "parse_mode=Markdown" >/dev/null 2>&1
    rm -f "$STATE_FILE"
  fi
else
  # 다운 — 이전에 정상 상태였으면 장애 알림 전송 (중복 알림 방지)
  if [[ ! -f "$STATE_FILE" ]]; then
    curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
      -d "chat_id=${TELEGRAM_CHAT_ID}" \
      -d "text=🔥 *[향린교회 서버 다운 감지]*%0A%0A📅 $(date '+%Y-%m-%d %H:%M KST')%0A⚠️ 서버가 응답하지 않습니다!%0A${SITE_URL} → HTTP ${http_code}%0A%0A서버(가비아) 접속 및 가동 상태를 확인하세요." \
      -d "parse_mode=Markdown" >/dev/null 2>&1
    echo "$(date '+%Y-%m-%d %H:%M')" > "$STATE_FILE"
  fi
fi
