#!/usr/bin/env bash
# ─────────────────────────────────────────────
# 향린교회 프로덕션 서버 자동 헬스 체크 (서버 내부 실행용)
# 위치: /home/wonhyukc/scripts/health-check-cron.sh
# Cron: 10분마다 실행, 장애(FAIL) 발생 시 텔레그램 경보 발송
# ─────────────────────────────────────────────

# ── 텔레그램 설정 ─────────────────────────────
TELEGRAM_BOT_TOKEN="8037347881:AAEXiAPgEC3iEzv-6MwNC0AN2ughbtZqm3E"
TELEGRAM_CHAT_ID="-1004408048565"

# ── 검증 대상 ─────────────────────────────────
# 이름|내부포트|경로
SITES=(
  "향린 메인|8080|/"
  "안병무도서관|8087|/"
  "이양노 갤러리|8081|/home/"
  "법무법인오늘|8091|/home/"
  "박형규 기념사업회|8082|/"
  "해랑|8083|/"
  "교육비평|8084|/"
  "길목|8085|/"
  "심원 아카이브|8086|/"
  "로로브레인|8088|/"
  "비북|8089|/"
  "시네마 버킷리스트|8090|/"
)

DISK_WARN=80

# ── 카운터 및 실패 목록 ────────────────────────
PASS=0; FAIL=0; WARN=0
FAIL_ITEMS=()
LOG="/data/log/health-check.log"

now() { date '+%Y-%m-%d %H:%M KST'; }

# ── 웹 엔드포인트 점검 ─────────────────────────
for entry in "${SITES[@]}"; do
  IFS="|" read -r name port path <<< "$entry"
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "http://localhost:${port}${path}" 2>/dev/null || echo "000")
  if [[ "$code" == "200" || "$code" == "301" || "$code" == "302" ]]; then
    PASS=$((PASS+1))
  else
    FAIL=$((FAIL+1))
    FAIL_ITEMS+=("• ${name} (${port}): HTTP ${code}")
  fi
done

# ── 향린 재정 PM2 (포트 3000) ──────────────────
fin_code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "http://localhost:3000/" 2>/dev/null || echo "000")
if [[ "$fin_code" == "200" || "$fin_code" == "301" || "$fin_code" == "302" ]]; then
  PASS=$((PASS+1))
else
  FAIL=$((FAIL+1))
  FAIL_ITEMS+=("• 향린 재정 (3000): HTTP ${fin_code}")
fi

# ── 디스크 사용량 (루트) ───────────────────────
disk_pct=$(df / --output=pcent | tail -1 | tr -dc '0-9')
if [ "$disk_pct" -ge "$DISK_WARN" ] 2>/dev/null; then
  FAIL=$((FAIL+1))
  FAIL_ITEMS+=("• 루트 디스크: ${disk_pct}% (${DISK_WARN}% 초과)")
else
  PASS=$((PASS+1))
fi

# ── 데이터 디스크 (/data) ──────────────────────
data_pct=$(df /data --output=pcent 2>/dev/null | tail -1 | tr -dc '0-9')
if [[ -n "$data_pct" ]] && [ "$data_pct" -ge "$DISK_WARN" ] 2>/dev/null; then
  FAIL=$((FAIL+1))
  FAIL_ITEMS+=("• 데이터 디스크: ${data_pct}% (${DISK_WARN}% 초과)")
else
  PASS=$((PASS+1))
fi

# ── Docker 컨테이너 수 ─────────────────────────
container_count=$( (docker ps --format '{{.Names}}' 2>/dev/null || sudo -n docker ps --format '{{.Names}}' 2>/dev/null) | wc -l)
if [ "$container_count" -lt 13 ] 2>/dev/null; then
  WARN=$((WARN+1))
  FAIL_ITEMS+=("• Docker: ${container_count}/13개만 구동 중")
else
  PASS=$((PASS+1))
fi

# ── 결과 로그 기록 ─────────────────────────────
total=$((PASS + WARN + FAIL))
echo "[$(now)] PASS=${PASS} WARN=${WARN} FAIL=${FAIL} (총 ${total})" >> "$LOG"

# ── 텔레그램 경보 (장애 시에만) ────────────────
if [[ ${#FAIL_ITEMS[@]} -gt 0 ]]; then
  text="🚨 *[향린 프로덕션 서버 경보]*%0A"
  text+="📅 $(now)%0A"
  text+="⚠️ *장애 항목 감지 (${#FAIL_ITEMS[@]}건)*:%0A"
  for item in "${FAIL_ITEMS[@]}"; do
    text+="${item}%0A"
  done
  text+="%0A정상: ${PASS}개 / 경고: ${WARN}개 / 장애: ${FAIL}개"

  curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
    -d "chat_id=${TELEGRAM_CHAT_ID}" \
    -d "text=${text}" \
    -d "parse_mode=Markdown" >/dev/null 2>&1

  echo "[$(now)] 📢 텔레그램 경보 발송 완료" >> "$LOG"
fi
