#!/usr/bin/env bash
# ─────────────────────────────────────────────
# 가비아 프로덕션 서버 통합 헬스 체크 스크립트
# 위치: .agents/skills/health-check/scripts/health-check.sh
#
# 사용법:
#   bash .agents/skills/health-check/scripts/health-check.sh [1|2]
#   - 1단계 (기본): 향린 재정 + 향린 홈 + 기본 인프라
#   - 2단계 (all):  이관된 전체 13개 웹 사이트 + 전체 인프라
# ─────────────────────────────────────────────

# ── 사용법 출력 ────────────────────────────────
usage() {
  cat <<'EOF'
사용법: health-check.sh [옵션] [단계]

가비아 프로덕션 서버(45.115.154.229)의 서비스 가용성과 인프라 상태를 검증합니다.

단계:
  1         1단계 — 핵심 서비스 (기본값)
              • 향린 재정 관리 시스템 (finance.hyanglin.org, 포트 3000)
              • 향린교회 메인 홈페이지 (hyanglin.org, 포트 8080)
              • 출석체크 인증/비인증 차단 검증
              • 기본 인프라 (디스크, Docker 4+개, fail2ban)
              • 데이터베이스 (MySQL 호스트 3306, PostgreSQL Docker 5432)
  2, all    2단계 — 전체 서비스
              • 1단계 전체 항목 포함
              • 이관된 13개 웹 사이트 HTTPS 응답 및 SSL 인증서 전수 검사
              • Docker 컨테이너 13+개 구동 여부 검증

옵션:
  -h, --help    이 도움말을 출력하고 종료

환경변수 / 설정:
  SSH_CMD              직접 지정할 SSH 접속 명령 (기본값: sshy 별칭 또는 ssh sshy 자동 감지)
  TELEGRAM_BOT_TOKEN   텔레그램 장애 알림 봇 토큰
  TELEGRAM_CHAT_ID     텔레그램 알림 대상 채팅 ID
  ~/.config/hyanglin/telegram.conf   위 환경변수를 파일로 설정 가능

종료 코드:
  0   모든 항목 PASS
  1   WARN 항목 존재 (FAIL 없음)
  2   FAIL 항목 존재

예시:
  bash health-check.sh          # 1단계 핵심 서비스 점검
  bash health-check.sh 2        # 2단계 전체 서비스 점검
  bash health-check.sh --help   # 도움말 출력
EOF
}

# ── 실행 단계 결정 ─────────────────────────────
if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
  usage
  exit 0
fi

STAGE="${1:-1}"
if [[ "$STAGE" != "1" && "$STAGE" != "2" && "$STAGE" != "all" ]]; then
  echo "오류: 알 수 없는 인자 '$STAGE'"
  echo ""
  usage
  exit 1
fi

# ── SSH 접속 명령 해석 (sshy 별칭 및 config 자동 지원) ──────
resolve_ssh_cmd() {
  # 1. 환경변수 SSH_CMD 또는 SSHY_CMD가 명시된 경우 최우선 사용
  if [[ -n "${SSH_CMD:-}" ]]; then
    echo "$SSH_CMD"
    return 0
  fi
  if [[ -n "${SSHY_CMD:-}" ]]; then
    echo "$SSHY_CMD"
    return 0
  fi

  # 2. ~/.ssh/config에 Host sshy가 등록되어 있거나 ssh sshy로 직접 접속 가능한 경우
  if ssh -o BatchMode=yes -o ConnectTimeout=3 -o StrictHostKeyChecking=accept-new sshy "echo ok" >/dev/null 2>&1; then
    echo "ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new sshy"
    return 0
  fi

  # 3. 사용자의 대화형 셸(zsh 또는 bash)에 정의된 sshy 별칭(alias) 추출 시도
  local detected=""
  if command -v zsh >/dev/null 2>&1; then
    detected=$(zsh -i -c 'alias sshy' 2>/dev/null | sed -e "s/^sshy=//" -e "s/^'//" -e "s/'$//" -e 's/^"//' -e 's/"$//')
  fi
  if [[ -z "$detected" ]] && command -v bash >/dev/null 2>&1; then
    detected=$(bash -i -c 'alias sshy' 2>/dev/null | sed -e "s/^alias sshy=//" -e "s/^'//" -e "s/'$//" -e 's/^"//' -e 's/"$//')
  fi

  if [[ -n "$detected" ]]; then
    echo "$detected -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new"
    return 0
  fi

  # 4. 기본 폴백: sshy 호스트 시도 후 실패 시 현재 사용자 또는 wonhyukc 계정으로 서버 IP 직접 접속
  local default_user="${SSH_USER:-${USER:-wonhyukc}}"
  echo "ssh -p 2222 -o ConnectTimeout=10 -o StrictHostKeyChecking=accept-new ${default_user}@45.115.154.229"
}

# ── 설정 ──────────────────────────────────────
SSH_CMD=$(resolve_ssh_cmd)
DISK_WARN_THRESHOLD=80
SSL_WARN_DAYS=14
SSL_FAIL_DAYS=7

# ── 카운터 및 실패 목록 ────────────────────────
PASS=0; WARN=0; FAIL=0
FAIL_ITEMS=()

# ── 텔레그램 설정 로드 ─────────────────────────
# 환경변수 우선, 없으면 설정 파일 조회
TELEGRAM_CONF="${HOME}/.config/hyanglin/telegram.conf"
if [[ -f "$TELEGRAM_CONF" ]]; then
  # shellcheck source=/dev/null
  source "$TELEGRAM_CONF"
fi

# ── 헬퍼 ──────────────────────────────────────
now() { date '+%Y-%m-%d %H:%M KST'; }

result() {
  local status="$1" label="$2" detail="$3"
  case "$status" in
    PASS) printf "  [\e[32mPASS\e[0m] %-28s %s\n" "$label" "$detail"; PASS=$((PASS+1)) ;;
    WARN) printf "  [\e[33mWARN\e[0m] %-28s %s\n" "$label" "$detail"; WARN=$((WARN+1)) ;;
    FAIL) 
      printf "  [\e[31mFAIL\e[0m] %-28s %s\n" "$label" "$detail"
      FAIL=$((FAIL+1))
      FAIL_ITEMS+=("• ${label}: ${detail}")
      ;;
  esac
}

send_telegram_alert() {
  if [[ -z "$TELEGRAM_BOT_TOKEN" || -z "$TELEGRAM_CHAT_ID" ]]; then
    return 0
  fi

  if [[ ${#FAIL_ITEMS[@]} -eq 0 ]]; then
    return 0
  fi

  local text="🚨 *[향린 프로덕션 서버 경보]*%0A"
  text+="📅 $(now)%0A"
  text+="⚠️ *장애 항목 감지 (${#FAIL_ITEMS[@]}건)*:%0A"
  for item in "${FAIL_ITEMS[@]}"; do
    text+="${item}%0A"
  done
  text+="%0A확인 및 조치가 필요합니다."

  curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
    -d "chat_id=${TELEGRAM_CHAT_ID}" \
    -d "text=${text}" \
    -d "parse_mode=Markdown" >/dev/null 2>&1 || true
}

check_ssl() {
  local domain="$1" label="$2"
  local expiry_date
  expiry_date=$(echo | timeout 10 openssl s_client -servername "${domain}" -connect "${domain}:443" 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2 || echo "")

  if [[ -n "$expiry_date" ]]; then
    local expiry_epoch now_epoch days_left display_date
    expiry_epoch=$(date -d "$expiry_date" +%s 2>/dev/null || echo 0)
    now_epoch=$(date +%s)
    if [[ "$expiry_epoch" -gt 0 ]]; then
      days_left=$(( (expiry_epoch - now_epoch) / 86400 ))
      display_date=$(date -d "$expiry_date" '+%m/%d' 2>/dev/null || echo "$expiry_date")
      if [ "$days_left" -ge "$SSL_WARN_DAYS" ]; then
        result PASS "$label" "${days_left}일 후 만료 (${display_date})"
      elif [ "$days_left" -ge "$SSL_FAIL_DAYS" ]; then
        result WARN "$label" "${days_left}일 후 만료 — 갱신 권장 (${display_date})"
      else
        result FAIL "$label" "${days_left}일 후 만료 — 즉시 갱신 필요! (${display_date})"
      fi
    else
      result FAIL "$label" "만료일 파싱 실패"
    fi
  else
    result FAIL "$label" "인증서 정보 조회 실패"
  fi
}

check_web_endpoint() {
  local label="$1" domain="$2" path="$3" internal_port="$4"
  local url="https://${domain}${path}"
  local external_raw ext_code ext_len

  # 외부 HTTPS 요청 (리다이렉트 추적)
  external_raw=$(curl -s -k -L -o /dev/null -w '%{http_code} %{size_download}' --max-time 15 "$url" 2>/dev/null || echo "000 0")
  ext_code=$(echo "$external_raw" | awk '{print $1}')
  ext_len=$(echo "$external_raw" | awk '{print $2}')

  if [[ "$ext_code" == "200" ]]; then
    local size_kb=$((ext_len / 1024))
    result PASS "$label" "HTTP ${ext_code}, ${size_kb}KB (HTTPS)"
  else
    # 내부 로컬 포트 폴백 점검
    if [[ -n "$internal_port" ]]; then
      local int_code
      int_code=$($SSH_CMD "curl -s -L -o /dev/null -w '%{http_code}' --max-time 10 'http://localhost:${internal_port}${path}'" 2>/dev/null || echo "000")
      if [[ "$int_code" == "200" ]]; then
        result WARN "$label" "외부 HTTP ${ext_code}, 내부 ${internal_port} 정상(200)"
      else
        result FAIL "$label" "외부 HTTP ${ext_code}, 내부 ${internal_port} HTTP ${int_code}"
      fi
    else
      result FAIL "$label" "HTTP ${ext_code}"
    fi
  fi
}

# ── 헤더 출력 ─────────────────────────────────
echo ""
echo "  ══════════════════════════════════════════════"
if [[ "$STAGE" == "1" ]]; then
  echo "    프로덕션 헬스 체크 리포트 [1단계: 핵심 서비스]"
else
  echo "    프로덕션 헬스 체크 리포트 [2단계: 전체 서비스]"
fi
echo "    $(now)"
echo "  ══════════════════════════════════════════════"
echo ""

# ── SSH 접속 확인 ─────────────────────────────
if ! $SSH_CMD "echo ok" >/dev/null 2>&1; then
  result FAIL "SSH 접속" "접속 실패 — 방화벽, 키 인증 또는 sshy 설정 확인 필요"
  echo ""
  echo "  💡 SSH 접속 안내:"
  echo "     현재 시도한 명령: $SSH_CMD"
  echo "     • ~/.ssh/config 에 'Host sshy' 설정을 등록하거나,"
  echo "     • 셸에 'alias sshy=...' 별칭을 등록하거나,"
  echo "     • SSH_CMD='ssh -p 2222 사용자명@45.115.154.229' 환경변수를 전달하여 실행할 수 있습니다."
  echo ""
  exit 1
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# [1단계 공통] 향린 메인 홈페이지 + 향린 재정
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo "  [핵심 1] 향린교회 메인 홈페이지 (hyanglin.org)"
echo "  ──────────────────────────────────────────────"

# 1. 향린 메인 페이지
homepage_raw=$($SSH_CMD "curl -s -o /dev/null -w '%{http_code} %{size_download}' --max-time 15 'http://localhost:8080/'" 2>/dev/null || echo "000 0")
http_code=$(echo "$homepage_raw" | awk '{print $1}')
content_length=$(echo "$homepage_raw" | awk '{print $2}')

if [[ "$http_code" == "200" ]]; then
  size_kb=$((content_length / 1024))
  if [ "$size_kb" -ge 10 ]; then
    result PASS "향린 메인 페이지" "HTTP ${http_code}, ${size_kb}KB (내부 8080)"
  else
    result WARN "향린 메인 페이지" "HTTP ${http_code}, ${size_kb}KB (콘텐츠 부족)"
  fi
else
  result FAIL "향린 메인 페이지" "HTTP ${http_code}"
fi

# 2. 출석체크 — 비인증 차단 (403)
unauth_code=$($SSH_CMD "curl -s -o /dev/null -w '%{http_code}' --max-time 10 'http://localhost:8080/contents/member-list.php'" 2>/dev/null || echo "000")
if [[ "$unauth_code" == "403" ]]; then
  result PASS "향린 출석체크 (비인증)" "HTTP 403 차단 정상"
else
  result FAIL "향린 출석체크 (비인증)" "HTTP ${unauth_code} (403 기대)"
fi

# 3. 출석체크 — 인증 접근 (200)
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
    result PASS "향린 출석체크 (인증)" "HTTP 200 접근 정상"
  else
    result FAIL "향린 출석체크 (인증)" "HTTP ${auth_code} (200 기대)"
  fi
else
  result WARN "향린 출석체크 (인증)" "유효한 관리자 세션 없음 (미검증)"
fi

# 4. 향린 SSL 인증서
check_ssl "www.hyanglin.org" "향린 SSL 인증서"

echo ""
echo "  [핵심 2] 향린 재정 관리 시스템 (finance.hyanglin.org)"
echo "  ──────────────────────────────────────────────"

# 5. 재정 시스템 웹 서빙 (포트 3000)
check_web_endpoint "향린 재정 웹" "finance.hyanglin.org" "/" "3000"

# 6. PM2 프로세스 상태
pm2_status=$($SSH_CMD "pm2 jlist 2>/dev/null" | grep -o '"name":"hyanglin-finance"[^}]*"status":"online"' 2>/dev/null || echo "")
if [[ -n "$pm2_status" ]]; then
  result PASS "재정 PM2 프로세스" "online 구동 중"
else
  # pm2 list로 재확인
  pm2_raw=$($SSH_CMD "pm2 list 2>/dev/null" || echo "")
  if echo "$pm2_raw" | grep -q "online"; then
    result PASS "재정 PM2 프로세스" "online 구동 중"
  else
    result FAIL "재정 PM2 프로세스" "프로세스 offline 또는 중단됨"
  fi
fi

# 7. 재정 SSL 인증서
check_ssl "finance.hyanglin.org" "재정 SSL 인증서"

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# [2단계] 이관된 전체 웹 사이트 점검
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
if [[ "$STAGE" == "2" || "$STAGE" == "all" ]]; then
  echo ""
  echo "  [확장] 이관된 전체 웹 서비스 (11개 추가 사이트)"
  echo "  ──────────────────────────────────────────────"

  # 사이트 목록 정의: 이름 | 도메인 | 기본 경로 | 내부 포트 | SSL 검사 도메인
  SITES=(
    "안병무도서관|www.ahn-library.org|/|8087|www.ahn-library.org"
    "이양노 갤러리|www.ongallery.co.kr|/home/|8081|ongallery.co.kr"
    "법무법인오늘|www.xn--wh1b76ni4aba943jj2b.com|/home/|8091|www.xn--wh1b76ni4aba943jj2b.com"
    "박형규 기념사업회|www.parkhyungkyu.org|/|8082|www.parkhyungkyu.org"
    "해랑|www.haerangart.com|/|8083|www.haerangart.com"
    "교육비평|www.educrit.org|/|8084|www.educrit.org"
    "길목|www.gilmok.org|/|8085|www.gilmok.org"
    "심원 아카이브|www.simwon.org|/|8086|www.simwon.org"
    "로로브레인|www.rorobrain.com|/|8088|www.rorobrain.com"
    "비북|www.b-book.co.kr|/|8089|www.b-book.co.kr"
    "시네마 버킷리스트|www.cinemabucketlist.com|/|8090|www.cinemabucketlist.com"
  )

  for site_entry in "${SITES[@]}"; do
    IFS="|" read -r s_name s_domain s_path s_port s_ssl <<< "$site_entry"
    check_web_endpoint "$s_name" "$s_domain" "$s_path" "$s_port"
    check_ssl "$s_ssl" "${s_name} SSL"
  done
fi

# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# [인프라] 서버 리소스 및 컨테이너
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
echo ""
echo "  [인프라] 서버 리소스 및 컨테이너"
echo "  ──────────────────────────────────────────────"

# 1. 루트 디스크 사용량 (vda)
disk_pct=$($SSH_CMD "df / --output=pcent | tail -1 | tr -dc '0-9'" 2>/dev/null || echo "0")
if [ "$disk_pct" -lt "$DISK_WARN_THRESHOLD" ] 2>/dev/null; then
  result PASS "루트 디스크 사용량" "${disk_pct}% (/ 파티션)"
else
  result FAIL "루트 디스크 사용량" "${disk_pct}% — ${DISK_WARN_THRESHOLD}% 초과!"
fi

# 2. 데이터 디스크 사용량 (/data, vdb)
data_pct=$($SSH_CMD "df /data --output=pcent 2>/dev/null | tail -1 | tr -dc '0-9'" 2>/dev/null || echo "")
if [[ -n "$data_pct" ]]; then
  if [ "$data_pct" -lt "$DISK_WARN_THRESHOLD" ] 2>/dev/null; then
    result PASS "데이터 디스크 사용량" "${data_pct}% (/data 파티션)"
  else
    result FAIL "데이터 디스크 사용량" "${data_pct}% — ${DISK_WARN_THRESHOLD}% 초과!"
  fi
fi

# 3. Docker 컨테이너 수
container_count=$($SSH_CMD "sudo docker ps --format '{{.Names}}' 2>/dev/null | wc -l" 2>/dev/null || echo "0")
if [[ "$STAGE" == "2" || "$STAGE" == "all" ]]; then
  EXPECTED_CONTAINERS=13
else
  EXPECTED_CONTAINERS=4
fi

if [ "$container_count" -ge "$EXPECTED_CONTAINERS" ] 2>/dev/null; then
  result PASS "Docker 컨테이너" "${container_count}개 구동 중 (기준: ${EXPECTED_CONTAINERS}개 이상)"
else
  running=$($SSH_CMD "sudo docker ps --format '{{.Names}}' 2>/dev/null | tr '\n' ', '" 2>/dev/null || echo "(조회 실패)")
  result WARN "Docker 컨테이너" "${container_count}/${EXPECTED_CONTAINERS}개 구동 — ${running}"
fi

# 4. fail2ban 서비스 상태
f2b_status=$($SSH_CMD "sudo fail2ban-client status 2>/dev/null" || echo "")
if echo "$f2b_status" | grep -q "Number of jail"; then
  jail_count=$(echo "$f2b_status" | grep "Number of jail" | awk '{print $NF}')
  result PASS "fail2ban 서비스" "${jail_count}개 jail 활성"
  # nginx-flood jail 상세 확인
  f2b_nginx=$($SSH_CMD "sudo fail2ban-client status nginx-flood 2>/dev/null" || echo "")
  if echo "$f2b_nginx" | grep -q "Currently banned"; then
    banned=$(echo "$f2b_nginx" | grep "Currently banned" | awk '{print $NF}')
    result PASS "fail2ban nginx-flood" "현재 ${banned}개 IP 차단 중"
  else
    result WARN "fail2ban nginx-flood" "nginx-flood jail이 비활성 상태"
  fi
else
  result FAIL "fail2ban 서비스" "fail2ban이 동작하지 않음"
fi

# 5. MySQL 서비스 상태 (호스트 네이티브, 포트 3306)
mysql_check=$($SSH_CMD "sudo mysql -u root -e 'SELECT 1' 2>&1" || echo "ERROR")
if echo "$mysql_check" | grep -q "^1$\|1\b"; then
  # DB 수 조회
  mysql_dbcount=$($SSH_CMD "sudo mysql -u root -N -e 'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name NOT IN (\"information_schema\",\"performance_schema\",\"mysql\",\"sys\")'" 2>/dev/null || echo "?")
  result PASS "MySQL 서비스" "정상 (호스트 3306, DB ${mysql_dbcount}개)"
else
  result FAIL "MySQL 서비스" "접속 실패 — $(echo "$mysql_check" | head -1)"
fi

# 6. PostgreSQL 서비스 상태 (Docker pgsql, 포트 5432)
pg_check=$($SSH_CMD "sudo docker exec pgsql pg_isready -U postgres 2>&1" || echo "ERROR")
if echo "$pg_check" | grep -q "accepting connections"; then
  # DB 수 조회
  pg_dbcount=$($SSH_CMD "sudo docker exec pgsql psql -U postgres -t -c \"SELECT COUNT(*) FROM pg_database WHERE datistemplate = false AND datname != 'postgres'\"" 2>/dev/null | tr -dc '0-9' || echo "?")
  result PASS "PostgreSQL 서비스" "정상 (Docker 5432, DB ${pg_dbcount}개)"
else
  result FAIL "PostgreSQL 서비스" "접속 실패 — $(echo "$pg_check" | head -1)"
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
echo "     향린 하단 여백 → https://www.hyanglin.org/"
echo "     향린 재정 홈   → https://finance.hyanglin.org/"
if [[ "$STAGE" == "2" || "$STAGE" == "all" ]]; then
  echo "     온갤러리 메인  → https://www.ongallery.co.kr/home/"
  echo "     법무법인오늘   → https://www.xn--wh1b76ni4aba943jj2b.com/home/"
fi
echo ""

# ── 텔레그램 알림 발송 (장애 시) ───────────────
send_telegram_alert

# ── 종료 코드 ─────────────────────────────────
if [ "$FAIL" -gt 0 ]; then
  exit 2
elif [ "$WARN" -gt 0 ]; then
  exit 1
else
  exit 0
fi
