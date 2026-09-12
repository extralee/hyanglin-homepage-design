#!/usr/bin/env bash
# ─────────────────────────────────────────────
# 향린교회 프로덕션 서버 헬스 체크 스크립트
# 이슈: #50
# 위치: .agents/skills/health-check/scripts/health-check.sh
#
# 사용법: bash .agents/skills/health-check/scripts/health-check.sh
# ─────────────────────────────────────────────

# ── 설정 ──────────────────────────────────────
SSH_CMD="ssh -p 2222 -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new wonhyukc@45.115.154.229"
DOMAIN="www.hyanglin.org"
DISK_WARN_THRESHOLD=80
SSL_WARN_DAYS=14
SSL_FAIL_DAYS=7
EXPECTED_CONTAINERS=3  # nginx, web(php), mysql

# ── 카운터 ────────────────────────────────────
PASS=0; WARN=0; FAIL=0

# ── 헬퍼 ──────────────────────────────────────
now() { date '+%Y-%m-%d %H:%M KST'; }

result() {
  local status="$1" label="$2" detail="$3"
  case "$status" in
    PASS) printf "  [\e[32mPASS\e[0m] %-24s %s\n" "$label" "$detail"; PASS=$((PASS+1)) ;;
    WARN) printf "  [\e[33mWARN\e[0m] %-24s %s\n" "$label" "$detail"; WARN=$((WARN+1)) ;;
    FAIL) printf "  [\e[31mFAIL\e[0m] %-24s %s\n" "$label" "$detail"; FAIL=$((FAIL+1)) ;;
  esac
}

# ── 헤더 출력 ─────────────────────────────────
echo ""
echo "  ══════════════════════════════════════════════"
echo "    향린교회 프로덕션 헬스 체크 리포트"
echo "    $(now)"
echo "  ══════════════════════════════════════════════"
echo ""

# ── SSH 접속 확인 ─────────────────────────────
if ! $SSH_CMD "echo ok" >/dev/null 2>&1; then
  result FAIL "SSH 접속" "접속 실패 — 방화벽 또는 키 인증 확인 필요"
  echo ""
  exit 1
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 1. 홈페이지 메인
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
homepage_raw=$($SSH_CMD "curl -s -o /dev/null -w '%{http_code} %{size_download}' --max-time 15 'http://localhost:8080/'" 2>/dev/null || echo "000 0")
http_code=$(echo "$homepage_raw" | awk '{print $1}')
content_length=$(echo "$homepage_raw" | awk '{print $2}')

if [[ "$http_code" == "200" ]]; then
  size_kb=$((content_length / 1024))
  if [ "$size_kb" -ge 10 ]; then
    result PASS "홈페이지 메인" "HTTP ${http_code}, ${size_kb}KB"
  else
    result WARN "홈페이지 메인" "HTTP ${http_code}, ${size_kb}KB (콘텐츠 부족)"
  fi
else
  result FAIL "홈페이지 메인" "HTTP ${http_code}"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 2. 출석체크 — 비인증 차단 (403)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
unauth_code=$($SSH_CMD "curl -s -o /dev/null -w '%{http_code}' --max-time 10 'http://localhost:8080/contents/member-list.php'" 2>/dev/null || echo "000")

if [[ "$unauth_code" == "403" ]]; then
  result PASS "출석체크 (비인증)" "HTTP 403 차단 정상"
else
  result FAIL "출석체크 (비인증)" "HTTP ${unauth_code} (403 기대)"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 3. 출석체크 — 인증 접근 (200)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
session_key=$($SSH_CMD 'sudo docker exec hyanglin-home-src-web-1 php -r '"'"'
define("__XE__", true);
include("/var/www/html/files/config/db.config.php");
$c = @mysql_connect($db_info->master_db["db_hostname"], $db_info->master_db["db_userid"], $db_info->master_db["db_password"]);
@mysql_select_db($db_info->master_db["db_database"], $c);
$r = @mysql_query("SELECT s.session_key FROM xe_session s JOIN xe_member m ON s.member_srl = m.member_srl WHERE (m.is_admin = \"Y\" OR s.member_srl IN (SELECT member_srl FROM xe_member_group_member WHERE group_srl IN (1,629,630))) AND s.member_srl > 0 ORDER BY s.last_update DESC LIMIT 1", $c);
$row = @mysql_fetch_assoc($r);
echo $row ? $row["session_key"] : "";
'"'" 2>/dev/null || echo "")

if [[ -n "$session_key" ]]; then
  auth_code=$($SSH_CMD "curl -s -o /dev/null -w '%{http_code}' --max-time 10 --cookie 'PHPSESSID=${session_key}' 'http://localhost:8080/contents/member-list.php'" 2>/dev/null || echo "000")
  if [[ "$auth_code" == "200" ]]; then
    result PASS "출석체크 (인증)" "HTTP 200 접근 정상"
  else
    result FAIL "출석체크 (인증)" "HTTP ${auth_code} (200 기대)"
  fi
else
  result WARN "출석체크 (인증)" "유효한 관리자 세션 없음 (미검증)"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 4. SSL 인증서 만료일 (로컬에서 직접 도메인 접속 — 서버 내부 DNS 문제 회피)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
expiry_date=$(echo | timeout 10 openssl s_client -servername ${DOMAIN} -connect ${DOMAIN}:443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2 || echo "")

if [[ -n "$expiry_date" ]]; then
  expiry_epoch=$(date -d "$expiry_date" +%s 2>/dev/null || echo 0)
  now_epoch=$(date +%s)
  if [[ "$expiry_epoch" -gt 0 ]]; then
    days_left=$(( (expiry_epoch - now_epoch) / 86400 ))
    display_date=$(date -d "$expiry_date" '+%m/%d' 2>/dev/null || echo "$expiry_date")
    if [ "$days_left" -ge "$SSL_WARN_DAYS" ]; then
      result PASS "SSL 인증서" "${days_left}일 후 만료 (${display_date})"
    elif [ "$days_left" -ge "$SSL_FAIL_DAYS" ]; then
      result WARN "SSL 인증서" "${days_left}일 후 만료 — 갱신 권장"
    else
      result FAIL "SSL 인증서" "${days_left}일 후 만료 — 즉시 갱신 필요!"
    fi
  else
    result FAIL "SSL 인증서" "만료일 파싱 실패"
  fi
else
  result FAIL "SSL 인증서" "인증서 정보 조회 실패"
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 5. 디스크 사용량 + Docker 컨테이너
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
disk_pct=$($SSH_CMD "df / --output=pcent | tail -1 | tr -dc '0-9'" 2>/dev/null || echo "0")

if [ "$disk_pct" -lt "$DISK_WARN_THRESHOLD" ] 2>/dev/null; then
  result PASS "디스크 사용량" "${disk_pct}% (/ 파티션)"
else
  result FAIL "디스크 사용량" "${disk_pct}% — ${DISK_WARN_THRESHOLD}% 초과!"
fi

container_count=$($SSH_CMD "sudo docker ps --format '{{.Names}}' 2>/dev/null | wc -l" 2>/dev/null || echo "0")

if [ "$container_count" -ge "$EXPECTED_CONTAINERS" ] 2>/dev/null; then
  result PASS "Docker 컨테이너" "${container_count}개 구동 중"
else
  running=$($SSH_CMD "sudo docker ps --format '{{.Names}}' 2>/dev/null | tr '\n' ', '" 2>/dev/null || echo "(조회 실패)")
  result FAIL "Docker 컨테이너" "${container_count}/${EXPECTED_CONTAINERS}개만 구동 — ${running}"
fi

# ── 결과 요약 ─────────────────────────────────
echo ""
echo "  ──────────────────────────────────────────────"
total=$((PASS + WARN + FAIL))
printf "    결과: \e[32m%d PASS\e[0m / \e[33m%d WARN\e[0m / \e[31m%d FAIL\e[0m  (총 %d)\n" "$PASS" "$WARN" "$FAIL" "$total"
echo "  ══════════════════════════════════════════════"

# ── 수동 확인 안내 ────────────────────────────
echo ""
echo "  🔧 수동 확인 필요:"
echo "     하단 여백 → https://${DOMAIN}/"
echo "     (브라우저에서 메인 페이지 하단에 과도한 빈 공간이 없는지 확인)"
echo ""

# ── 종료 코드 ─────────────────────────────────
if [ "$FAIL" -gt 0 ]; then
  exit 2
elif [ "$WARN" -gt 0 ]; then
  exit 1
else
  exit 0
fi
