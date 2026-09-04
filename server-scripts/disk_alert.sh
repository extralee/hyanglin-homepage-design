#!/bin/bash
# =============================================================================
# disk_alert.sh
# 모든 파티션 디스크 사용량을 30분마다 체크하여 임계치 초과 시 경고 메일 발송
# =============================================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
if [ -f "$DIR/settings.conf" ]; then
    source "$DIR/settings.conf"
fi

WARN_THRESHOLD="${DISK_WARN_THRESHOLD:-85}"
CRIT_THRESHOLD="${DISK_CRIT_THRESHOLD:-93}"
EVENT_LOG="${DISK_EVENT_LOG:-/home/wonhyukc/logs/disk_events.log}"
SERVER_NAME="${SERVER_NAME:-향린교회 서버}"
ALERT_SENT=0

mkdir -p "$(dirname "$EVENT_LOG")"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Checking disk usage on all partitions..."

# 모든 실제 파티션 순회 (tmpfs, devtmpfs 등 가상 파일시스템 제외)
while IFS= read -r line; do
    PARTITION=$(echo "$line" | awk '{print $1}')
    MOUNT=$(echo "$line"    | awk '{print $6}')
    USED=$(echo "$line"     | awk '{print $3}')
    TOTAL=$(echo "$line"    | awk '{print $2}')
    PERCENT=$(echo "$line"  | awk '{print $5}' | tr -d '%')

    # 숫자가 아닌 경우 건너뜀
    [[ ! "$PERCENT" =~ ^[0-9]+$ ]] && continue

    if [ "$PERCENT" -ge "$CRIT_THRESHOLD" ]; then
        LEVEL="CRITICAL"
        SUBJECT="[향린 CRITICAL] 디스크 사용량 ${PERCENT}% 위험 경고 (${MOUNT})"
        HEADER_COLOR="#b71c1c"
        BADGE_COLOR="#d32f2f"
        ALERT_SENT=1
    elif [ "$PERCENT" -ge "$WARN_THRESHOLD" ]; then
        LEVEL="WARNING"
        SUBJECT="[향린 WARNING] 디스크 사용량 ${PERCENT}% 경고 (${MOUNT})"
        HEADER_COLOR="#e65100"
        BADGE_COLOR="#f57c00"
        ALERT_SENT=1
    else
        continue
    fi

    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ${LEVEL}: ${MOUNT} at ${PERCENT}% (${USED}/${TOTAL})"

    # 이벤트 로그 기록 (daily_report.sh 가 읽음)
    echo "$(date '+%Y-%m-%d %H:%M:%S') | ${LEVEL} | ${MOUNT} | ${PERCENT}% used (${USED} / ${TOTAL}) | ${PARTITION}" >> "$EVENT_LOG"

    # HTML 메일 생성 및 발송
    REPORT_FILE="/tmp/hyanglin_disk_alert_$(date +%Y%m%d_%H%M%S)_$(echo "$MOUNT" | tr '/' '_').html"
    cat <<EOF > "$REPORT_FILE"
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>향린교회 — 디스크 ${LEVEL}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f4f6f8; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow: hidden; border: 2px solid ${BADGE_COLOR}; }
        .header { background-color: ${HEADER_COLOR}; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .content { padding: 25px; }
        .section-title { font-size: 14px; font-weight: bold; border-left: 4px solid ${BADGE_COLOR}; padding-left: 8px; margin: 15px 0 10px; color: ${BADGE_COLOR}; text-transform: uppercase; }
        .metric-card { background: #fff8f0; border: 1px solid #ffe0b2; border-radius: 6px; padding: 12px; margin-bottom: 15px; }
        .metric-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #ffe0b2; font-size: 13px; }
        .metric-row:last-child { border-bottom: none; }
        .metric-label { font-weight: 500; color: #4a5568; }
        .metric-value { font-weight: 600; color: #2d3748; }
        .gauge-bar { height: 16px; background: #e2e8f0; border-radius: 8px; overflow: hidden; margin: 4px 0; }
        .gauge-fill { height: 100%; background: ${BADGE_COLOR}; border-radius: 8px; width: ${PERCENT}%; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #fff; background: ${BADGE_COLOR}; }
        .footer { background: #f7fafc; padding: 12px; text-align: center; font-size: 11px; color: #a0aec0; border-top: 1px solid #edf2f7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>$([ "$LEVEL" = "CRITICAL" ] && echo "🚨" || echo "⚠️") 디스크 ${LEVEL}: ${PERCENT}% 사용 중</h1>
        </div>
        <div class="content">
            <div class="section-title">디스크 경고 상세 / Alert Details</div>
            <div class="metric-card">
                <div class="metric-row">
                    <span class="metric-label">경고 등급</span>
                    <span class="status-badge">${LEVEL}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">마운트 경로</span>
                    <span class="metric-value">${MOUNT}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">장치 파일</span>
                    <span class="metric-value">${PARTITION}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">사용량</span>
                    <span class="metric-value">${USED} / ${TOTAL} (${PERCENT}%)</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">사용량 바</span>
                    <span style="flex:1; margin-left:16px;">
                        <div class="gauge-bar"><div class="gauge-fill"></div></div>
                    </span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">감지 시각</span>
                    <span class="metric-value">$(date +'%Y-%m-%d %H:%M:%S KST')</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">경고 기준치</span>
                    <span class="metric-value">WARNING: ${WARN_THRESHOLD}% / CRITICAL: ${CRIT_THRESHOLD}%</span>
                </div>
            </div>
            <div class="section-title">전체 파티션 현황 (Snapshot)</div>
            <div class="metric-card">
$(df -h --output=source,size,used,avail,pcent,target -x tmpfs -x devtmpfs -x squashfs 2>/dev/null | tail -n +2 | while read -r src sz us av pc mt; do
    PCT=$(echo "$pc" | tr -d '%')
    COLOR=$([ "${PCT:-0}" -ge "$CRIT_THRESHOLD" ] 2>/dev/null && echo "#d32f2f" || ([ "${PCT:-0}" -ge "$WARN_THRESHOLD" ] 2>/dev/null && echo "#f57c00" || echo "#167237"))
    echo "                <div class=\"metric-row\"><span class=\"metric-label\">$mt</span><span class=\"metric-value\" style=\"color:$COLOR;\">$us / $sz ($pc)</span></div>"
done)
            </div>
        </div>
        <div class="footer">
            향린교회 서버 자동 디스크 모니터링 경고<br>
            &copy; $(date +'%Y') 향린교회. All rights reserved.
        </div>
    </div>
</body>
</html>
EOF

    python3 "$DIR/send_email.py" \
        --to "$ADMIN_EMAIL" \
        --cc "${ADMIN_EMAIL_CC:-}" \
        --subject "$SUBJECT" \
        --body "$(cat "$REPORT_FILE")" \
        --html

    rm -f "$REPORT_FILE"

done < <(df -h --output=source,size,used,avail,pcent,target -x tmpfs -x devtmpfs -x squashfs 2>/dev/null | tail -n +2)

if [ "$ALERT_SENT" -eq 0 ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] All partitions OK (below ${WARN_THRESHOLD}% threshold)."
fi
