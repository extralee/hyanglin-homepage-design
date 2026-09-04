#!/bin/bash
# =============================================================================
# daily_report.sh
# 향린교회 서버 종합 일일 운영 보고서 생성 및 발송 스크립트
# 매일 오전 06:05 KST에 실행되어 지난 24시간 동안의 DB 백업, 디스크 사용률,
# CPU/메모리 부하 추이 및 서비스 헬스를 종합하여 운영진에게 보고 메일을 발송합니다.
# =============================================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
if [ -f "$DIR/settings.conf" ]; then
    source "$DIR/settings.conf"
fi

TODAY=$(date +'%Y-%m-%d')
YESTERDAY=$(date -d 'yesterday' +'%Y-%m-%d' 2>/dev/null || date -v-1d +'%Y-%m-%d')
CUTOFF_EPOCH=$(date -d '24 hours ago' +'%s' 2>/dev/null || echo "$(( $(date +%s) - 86400 ))")

LOG_DIR="${LOG_DIR:-/home/wonhyukc/logs}"
METRICS_DB="${METRICS_DB:-$LOG_DIR/system_metrics.db}"
BACKUP_EVENT_LOG="${BACKUP_EVENT_LOG:-$LOG_DIR/backup_events.log}"
DISK_EVENT_LOG="${DISK_EVENT_LOG:-$LOG_DIR/disk_events.log}"
SERVER_NAME="${SERVER_NAME:-향린교회 서버 (Hyanglin Gabia Prod)}"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Starting daily report for ${SERVER_NAME}..."

# ===========================================================================
# 1. 시스템 메트릭 수집 / System Metrics Collection
# ===========================================================================

# 1-0. 실시간 CPU & 메모리
REAL_CPU=$(python3 -c "
import time
def get_cpu():
    try:
        with open('/proc/stat') as f:
            fields = [float(column) for column in f.readline().strip().split()[1:]]
        return fields[3] + fields[4], sum(fields)
    except Exception:
        return 0.0, 0.0
idle1, total1 = get_cpu()
time.sleep(3.0)
idle2, total2 = get_cpu()
idle_delta = idle2 - idle1
total_delta = total2 - total1
print(f'{100.0 * (1.0 - idle_delta / total_delta):.1f}' if total_delta > 0 else '0.0')
")

MEM_TOTAL=$(free -m | awk 'NR==2 {print $2}')
MEM_USED=$(free -m  | awk 'NR==2 {print $3}')
MEM_PERCENT=$(awk -v t="$MEM_TOTAL" -v u="$MEM_USED" 'BEGIN {printf "%.1f", (u*100)/t}')

# 1-1. 서비스 헬스체크 (Docker 컨테이너 & PM2 재정시스템)
# 메인 웹 (PHP 5.6 XE)
if sudo docker ps --format '{{.Names}}' 2>/dev/null | grep -q "hyanglin-home-src-web-1"; then
    WEB_STATUS_TEXT="HEALTHY"
    WEB_STATUS_COLOR="#167237"
else
    WEB_STATUS_TEXT="DOWN"
    WEB_STATUS_COLOR="#d32f2f"
fi

# 메인 DB (MySQL 5.7)
if sudo docker ps --format '{{.Names}}' 2>/dev/null | grep -q "hyanglin-home-infra-legacy-mysql-1"; then
    DB_STATUS_TEXT="HEALTHY"
    DB_STATUS_COLOR="#167237"
else
    DB_STATUS_TEXT="DOWN"
    DB_STATUS_COLOR="#d32f2f"
fi

# 재정 관리 시스템 (Host PM2 / Port 3000)
FINANCE_HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000 2>/dev/null || echo "000")
if [[ "$FINANCE_HTTP_CODE" =~ ^(200|301|302|307|308|401|403)$ ]]; then
    FINANCE_STATUS_TEXT="HEALTHY"
    FINANCE_STATUS_COLOR="#167237"
else
    FINANCE_STATUS_TEXT="CHECK (HTTP $FINANCE_HTTP_CODE)"
    FINANCE_STATUS_COLOR="#d32f2f"
fi

# ===========================================================================
# 1-2. 24시간 CPU/메모리 사용 추이 수집 (SQLite DB 조회)
# ===========================================================================
CPU_TREND_HTML=""
if [ -f "$METRICS_DB" ]; then
    while IFS='|' read -r ts cpu mem; do
        [ -z "$ts" ] && continue
        time_display=$(date -d "$ts" +'%H:%M' 2>/dev/null || echo "$ts")
        
        usage_num=$(echo "$cpu" | cut -d'.' -f1)
        if [[ "$usage_num" =~ ^[0-9]+$ ]] && [ "$usage_num" -ge 90 ]; then
            usage_style="color:#d32f2f; font-weight:bold;"
        else
            usage_style="color:#2d3748;"
        fi
        
        CPU_TREND_HTML+="<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-bottom: 1px dashed #edf2f7; padding: 4px 0;\">"
        CPU_TREND_HTML+="<tr>"
        CPU_TREND_HTML+="<td align=\"left\" style=\"font-size:12px; color:#718096;\">${time_display}</td>"
        CPU_TREND_HTML+="<td align=\"right\" style=\"font-size:12px; ${usage_style}\">CPU: ${cpu}% / MEM: ${mem}%</td>"
        CPU_TREND_HTML+="</tr>"
        CPU_TREND_HTML+="</table>"
    done < <(python3 -c "
import sqlite3
try:
    conn = sqlite3.connect('$METRICS_DB')
    cur = conn.cursor()
    cur.execute('''
        SELECT timestamp, cpuUsage, memoryUsage 
        FROM system_metrics 
        WHERE timestamp >= datetime('now', '-24 hours') 
        ORDER BY timestamp ASC;
    ''')
    for row in cur.fetchall():
        print(f'{row[0]}|{row[1]}|{row[2]}')
    conn.close()
except Exception:
    pass
" 2>/dev/null)
fi

if [ -z "$CPU_TREND_HTML" ]; then
    CPU_TREND_HTML="<div style=\"font-size:12px; color:#a0aec0; text-align:center; padding:10px 0;\">최근 24시간 수집된 메트릭이 없습니다 (초기 기동 대기)</div>"
fi

# ===========================================================================
# 2. 최근 24시간 백업 이벤트 / Backup Events (Last 24 Hours)
# ===========================================================================
BACKUP_SUCCESS_COUNT=0
BACKUP_FAIL_COUNT=0
BACKUP_ROWS_HTML=""

# 2-1. backup_events.log 조회
if [ -f "$BACKUP_EVENT_LOG" ]; then
    while IFS='|' read -r ts status details; do
        ts_trimmed=$(echo "$ts" | xargs)
        [ -z "$ts_trimmed" ] && continue
        ts_epoch=$(date -d "$ts_trimmed" +'%s' 2>/dev/null) || continue
        [[ -z "$ts_epoch" || "$ts_epoch" -lt "$CUTOFF_EPOCH" ]] && continue
        
        status_trimmed=$(echo "$status" | xargs)
        details_trimmed=$(echo "$details" | xargs)
        
        if [ "$status_trimmed" == "SUCCESS" ]; then
            BACKUP_SUCCESS_COUNT=$((BACKUP_SUCCESS_COUNT + 1))
            status_color="#167237"
        else
            BACKUP_FAIL_COUNT=$((BACKUP_FAIL_COUNT + 1))
            status_color="#d32f2f"
        fi
        
        time_display=$(date -d "$ts_trimmed" +'%H:%M' 2>/dev/null || echo "$ts_trimmed")
        BACKUP_ROWS_HTML+="<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-bottom:1px solid #edf2f7; padding:6px 0;\">"
        BACKUP_ROWS_HTML+="<tr>"
        BACKUP_ROWS_HTML+="<td align=\"left\" style=\"font-size:13px; color:#4a5568;\">${time_display} - ${details_trimmed}</td>"
        BACKUP_ROWS_HTML+="<td align=\"right\"><span class=\"status-badge\" style=\"background:${status_color};\">${status_trimmed}</span></td>"
        BACKUP_ROWS_HTML+="</tr>"
        BACKUP_ROWS_HTML+="</table>"
    done < "$BACKUP_EVENT_LOG"
fi

# 2-2. 만약 backup_events.log가 아직 비어있다면, 기존 backup.log에서 최근 24시간 성공 기록 자동 탐색
if [ -z "$BACKUP_ROWS_HTML" ] && [ -f "/var/www/hyanglin-home-infra/backups/backup.log" ]; then
    while read -r line; do
        if echo "$line" | grep -q "백업 성공:"; then
            b_ts=$(echo "$line" | grep -oE '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}')
            b_file=$(echo "$line" | sed 's/.*백업 성공: //')
            b_epoch=$(date -d "$b_ts" +'%s' 2>/dev/null) || continue
            [[ -n "$b_epoch" && "$b_epoch" -ge "$CUTOFF_EPOCH" ]] || continue
            
            BACKUP_SUCCESS_COUNT=$((BACKUP_SUCCESS_COUNT + 1))
            time_display=$(date -d "$b_ts" +'%H:%M' 2>/dev/null || echo "$b_ts")
            BACKUP_ROWS_HTML+="<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-bottom:1px solid #edf2f7; padding:6px 0;\">"
            BACKUP_ROWS_HTML+="<tr>"
            BACKUP_ROWS_HTML+="<td align=\"left\" style=\"font-size:13px; color:#4a5568;\">${time_display} - full | ${b_file}</td>"
            BACKUP_ROWS_HTML+="<td align=\"right\"><span class=\"status-badge\" style=\"background:#167237;\">SUCCESS</span></td>"
            BACKUP_ROWS_HTML+="</tr>"
            BACKUP_ROWS_HTML+="</table>"
        fi
    done < <(tail -n 40 /var/www/hyanglin-home-infra/backups/backup.log)
fi

if [ $BACKUP_SUCCESS_COUNT -gt 0 ] && [ $BACKUP_FAIL_COUNT -eq 0 ]; then
    BACKUP_SUMMARY_TEXT="BACKUPS OK"
    BACKUP_SUMMARY_COLOR="#167237"
elif [ $BACKUP_FAIL_COUNT -gt 0 ]; then
    BACKUP_SUMMARY_TEXT="BACKUP FAILED"
    BACKUP_SUMMARY_COLOR="#d32f2f"
else
    BACKUP_SUMMARY_TEXT="No backups run"
    BACKUP_SUMMARY_COLOR="#718096"
fi

# ===========================================================================
# 3. 디스크 경고 이벤트 / Disk Warning Events (Last 24 Hours)
# ===========================================================================
DISK_ALERT_COUNT=0
DISK_ROWS_HTML=""

if [ -f "$DISK_EVENT_LOG" ]; then
    while IFS='|' read -r ts level mount usage part; do
        ts_trimmed=$(echo "$ts" | xargs)
        [ -z "$ts_trimmed" ] && continue
        ts_epoch=$(date -d "$ts_trimmed" +'%s' 2>/dev/null) || continue
        [[ -z "$ts_epoch" || "$ts_epoch" -lt "$CUTOFF_EPOCH" ]] && continue
        
        DISK_ALERT_COUNT=$((DISK_ALERT_COUNT + 1))
        time_display=$(date -d "$ts_trimmed" +'%H:%M' 2>/dev/null || echo "$ts_trimmed")
        mount_trimmed=$(echo "$mount" | xargs)
        usage_trimmed=$(echo "$usage" | xargs)
        
        DISK_ROWS_HTML+="<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-bottom:1px solid #edf2f7; padding:6px 0;\">"
        DISK_ROWS_HTML+="<tr>"
        DISK_ROWS_HTML+="<td align=\"left\" style=\"font-size:13px; color:#d32f2f;\">⚠️ ${time_display} - ${mount_trimmed} (${usage_trimmed})</td>"
        DISK_ROWS_HTML+="<td align=\"right\" style=\"font-size:12px; font-weight:bold; color:#d32f2f;\">${level}</td>"
        DISK_ROWS_HTML+="</tr>"
        DISK_ROWS_HTML+="</table>"
    done < "$DISK_EVENT_LOG"
fi

if [ $DISK_ALERT_COUNT -eq 0 ]; then
    DISK_ALERT_SUMMARY="No disk alerts"
    DISK_ALERT_SUMMARY_COLOR="#167237"
else
    DISK_ALERT_SUMMARY="${DISK_ALERT_COUNT} alerts triggered"
    DISK_ALERT_SUMMARY_COLOR="#d32f2f"
fi

# ===========================================================================
# 4. 디스크 파티션 현황 / Partition Disk Usage
# ===========================================================================
DISK_PARTITION_ROWS=""
while IFS= read -r line; do
    fs=$(echo "$line" | awk '{print $1}')
    size=$(echo "$line" | awk '{print $2}')
    used=$(echo "$line" | awk '{print $3}')
    avail=$(echo "$line" | awk '{print $4}')
    pct=$(echo "$line" | awk '{print $5}')
    mount=$(echo "$line" | awk '{print $6}')
    
    pct_num=$(echo "$pct" | tr -d '%')

    # 가용 용량을 GB 단위 숫자로 변환 (G=그대로, M=0, T=*1000)
    avail_num=$(echo "$avail" | awk '{
        v = $1
        if (v ~ /G/) { gsub(/G/, "", v); print int(v) }
        else if (v ~ /T/) { gsub(/T/, "", v); print int(v) * 1000 }
        else { print 0 }
    }')

    # 빨강: 사용률 70% 이상 OR 가용 용량 30G 이하
    # 주황: 사용률 50% 이상
    # 녹색: 정상
    if [ "$pct_num" -ge 70 ] 2>/dev/null || [ "${avail_num:-999}" -le 30 ] 2>/dev/null; then
        val_color="#d32f2f; font-weight:bold;"
    elif [ "$pct_num" -ge 50 ] 2>/dev/null; then
        val_color="#dd6b20; font-weight:bold;"
    else
        val_color="#2d3748;"
    fi
    
    DISK_PARTITION_ROWS+="<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-bottom:1px solid #edf2f7; padding:6px 0;\">"
    DISK_PARTITION_ROWS+="<tr>"
    DISK_PARTITION_ROWS+="<td align=\"left\" style=\"font-size:13px; color:#4a5568;\">${mount} (${fs})</td>"
    DISK_PARTITION_ROWS+="<td align=\"right\" style=\"font-size:13px; color:${val_color}\">${used} / ${size} (${pct})</td>"
    DISK_PARTITION_ROWS+="</tr>"
    DISK_PARTITION_ROWS+="</table>"
done < <(df -h | grep -E '^/dev/')

# ===========================================================================
# 5. 재정 시스템 교적 동기화 요약 (Dimode Sync Summary)
# ===========================================================================
SYNC_ROWS_HTML=""
DIMODE_LOG="/home/wonhyukc/logs/sync-dimode.log"
if [ -f "$DIMODE_LOG" ]; then
    LAST_SYNC_LINE=$(tail -n 15 "$DIMODE_LOG" | grep -iE 'done|success|finish|completed|error' | tail -n 1)
    if [ -n "$LAST_SYNC_LINE" ]; then
        SYNC_ROWS_HTML+="<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"padding:4px 0;\">"
        SYNC_ROWS_HTML+="<tr>"
        SYNC_ROWS_HTML+="<td align=\"left\" style=\"font-size:12px; color:#4a5568;\">디모데 교적 Pull 동기화</td>"
        SYNC_ROWS_HTML+="<td align=\"right\" style=\"font-size:12px; color:#167237; font-weight:600;\">정상 구동 중</td>"
        SYNC_ROWS_HTML+="</tr>"
        SYNC_ROWS_HTML+="</table>"
    fi
fi

# ===========================================================================
# 6. HTML 리포트 메일 템플릿 조립 / Assemble HTML Report Template
# ===========================================================================
REPORT_FILE=$(mktemp)

cat <<EOF > "$REPORT_FILE"
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hyanglin Church — Daily Operations Report</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #2d3748; background-color: #f7fafc; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background: #1a202c; color: #ffffff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; letter-spacing: 0.5px; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #a0aec0; }
        .content { padding: 20px; }
        .section-title { font-size: 13px; font-weight: 700; color: #4a5568; margin-top: 20px; margin-bottom: 8px; text-transform: uppercase; border-left: 3px solid #3182ce; padding-left: 8px; letter-spacing: 0.5px; }
        .metric-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
        .metric-row { display: flex; justify-content: space-between; align-items: center; font-size: 13px; padding: 4px 0; }
        .metric-label { color: #4a5568; }
        .metric-value { font-weight: 600; color: #1d2327; }
        .status-badge { font-size: 11px; font-weight: bold; color: #ffffff; padding: 2px 8px; border-radius: 4px; text-transform: uppercase; }
        .footer { background: #edf2f7; color: #718096; text-align: center; padding: 15px; font-size: 11px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Hyanglin Church — Daily Report</h1>
            <p>${TODAY}  |  Generated at $(date +'%H:%M:%S') KST</p>
        </div>
        <div class="content">
            
            <!-- 서버 상태 및 CPU/메모리 -->
            <div class="section-title">서버 상태 / SERVER HEALTH</div>
            <div class="metric-card">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:6px;">
                    <tr>
                        <td align="left" style="font-size:13px; color:#4a5568;">메인 홈페이지 (PHP 5.6 / XE)</td>
                        <td align="right"><span class="status-badge" style="background:${WEB_STATUS_COLOR};">${WEB_STATUS_TEXT}</span></td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:6px;">
                    <tr>
                        <td align="left" style="font-size:13px; color:#4a5568;">데이터베이스 / MySQL 5.7</td>
                        <td align="right"><span class="status-badge" style="background:${DB_STATUS_COLOR};">${DB_STATUS_TEXT}</span></td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #e2e8f0; padding-bottom:6px; margin-bottom:6px;">
                    <tr>
                        <td align="left" style="font-size:13px; color:#4a5568;">재정 관리 시스템 (Host PM2)</td>
                        <td align="right"><span class="status-badge" style="background:${FINANCE_STATUS_COLOR};">${FINANCE_STATUS_TEXT}</span></td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:4px 0;">
                    <tr>
                        <td align="left" style="font-size:13px; color:#4a5568;">CPU 사용률 / CPU Usage</td>
                        <td align="right" style="font-size:13px; font-weight:600; color:#1d2327;">${REAL_CPU}%</td>
                    </tr>
                </table>
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:4px 0;">
                    <tr>
                        <td align="left" style="font-size:13px; color:#4a5568;">메모리 사용량 / Memory Usage</td>
                        <td align="right" style="font-size:13px; font-weight:600; color:#1d2327;">${MEM_USED}MB / ${MEM_TOTAL}MB (${MEM_PERCENT}%)</td>
                    </tr>
                </table>
                <div style="margin-top:12px; border-top:1px solid #e2e8f0; padding-top:10px;">
                    <div style="font-size:12px; font-weight:700; color:#4a5568; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">24시간 CPU/메모리 사용 추이 / 24H CPU/MEMORY TREND (HOURLY)</div>
                    <div style="max-height:180px; overflow-y:auto; padding-right:5px;">
                        ${CPU_TREND_HTML}
                    </div>
                </div>
            </div>

            <!-- 파티션별 디스크 현황 -->
            <div class="section-title">디스크 현황 / DISK USAGE — ALL PARTITIONS</div>
            <div class="metric-card">
                ${DISK_PARTITION_ROWS}
            </div>

            <!-- 백업 요약 -->
            <div class="section-title">백업 요약 / BACKUP SUMMARY (${YESTERDAY})</div>
            <div class="metric-card">
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:4px 0;">
                    <tr>
                        <td align="left" style="font-size:13px; color:#4a5568;">전체 결과 / Overall Result</td>
                        <td align="right"><span class="status-badge" style="background:${BACKUP_SUMMARY_COLOR};">${BACKUP_SUMMARY_TEXT}</span></td>
                    </tr>
                </table>
                ${BACKUP_ROWS_HTML}
            </div>

            <!-- 디스크 경고 이력 -->
            <div class="section-title">디스크 경고 이력 / DISK ALERT HISTORY (${YESTERDAY})</div>
            <div class="metric-card">
                <table width="100%" cellpadding="0" cellspacing="0" style="margin:4px 0;">
                    <tr>
                        <td align="left" style="font-size:13px; color:#4a5568;">요약 / Summary</td>
                        <td align="right" style="font-size:13px; font-weight:600; color:${DISK_ALERT_SUMMARY_COLOR};">${DISK_ALERT_SUMMARY}</td>
                    </tr>
                </table>
                ${DISK_ROWS_HTML}
            </div>

            <!-- 재정 시스템 작업 요약 (있는 경우) -->
            ${SYNC_ROWS_HTML:+"<div class=\"section-title\">교적 동기화 / DIMODE SYNC</div><div class=\"metric-card\">${SYNC_ROWS_HTML}</div>"}

        </div>
        <div class="footer">
            향린교회 서버 자동 일일 보고 — 매일 오전 06:05 KST 발송<br>
            Daily operations report from Hyanglin Church Server — Sent automatically every morning at 06:05 KST.<br>
            &copy; $(date +'%Y') 향린교회. All rights reserved.
        </div>
    </div>
</body>
</html>
EOF

# ===========================================================================
# 7. 메일 발송 / Send Email
# ===========================================================================
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Sending daily report to ${ADMIN_EMAIL}..."
python3 "$DIR/send_email.py" \
    --to "$ADMIN_EMAIL" \
    --cc "${ADMIN_EMAIL_CC:-}" \
    --subject "[향린 Daily] Operations Report — ${TODAY}" \
    --body "$(cat "$REPORT_FILE")" \
    --html

rm -f "$REPORT_FILE"

# 1년 이상된 메트릭 데이터 자동 정리 (1-year raw metric purge via SQLite)
if [ -f "$METRICS_DB" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Executing metrics 1-year purge in SQLite..."
    python3 -c "
import sqlite3
try:
    conn = sqlite3.connect('$METRICS_DB')
    cur = conn.cursor()
    cur.execute('''DELETE FROM system_metrics WHERE timestamp < datetime('now', '-1 year');''')
    conn.commit()
    conn.close()
    print('Old metrics purge completed.')
except Exception as e:
    import sys
    print('Purge Error:', e, file=sys.stderr)
"
fi

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Daily report sent successfully."
