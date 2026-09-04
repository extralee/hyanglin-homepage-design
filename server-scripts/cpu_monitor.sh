#!/bin/bash
# =============================================================================
# cpu_monitor.sh
# 매시간 23분에 서버 CPU & 메모리 사용량을 측정하고 SQLite DB에 저장하며, 
# CPU 90% 초과 시 즉시 경고 이메일을 발송합니다.
# =============================================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"

if [ -f "$DIR/settings.conf" ]; then
    source "$DIR/settings.conf"
fi

LOG_DIR="${LOG_DIR:-/home/wonhyukc/logs}"
METRICS_DB="${METRICS_DB:-$LOG_DIR/system_metrics.db}"
CPU_ALERT_THRESHOLD="${CPU_ALERT_THRESHOLD:-90.0}"
SERVER_NAME="${SERVER_NAME:-향린교회 서버}"
SERVER_IP="${SERVER_IP:-45.115.154.229}"

mkdir -p "$LOG_DIR"

TIMESTAMP=$(date +'%Y-%m-%d %H:%M:%S')

# 1. CPU 사용량 계산 (3.0초 윈도우)
CPU_USAGE=$(python3 -c "
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

# 2. 메모리 사용량 계산
MEM_TOTAL=$(free -m | awk 'NR==2 {print $2}')
MEM_USED=$(free -m  | awk 'NR==2 {print $3}')
MEM_PERCENT=$(awk -v t="$MEM_TOTAL" -v u="$MEM_USED" 'BEGIN {printf "%.1f", (u*100)/t}')

# 3. SQLite 데이터베이스에 수집값 삽입
python3 -c "
import sqlite3
try:
    conn = sqlite3.connect('$METRICS_DB')
    cur = conn.cursor()
    cur.execute('''
        CREATE TABLE IF NOT EXISTS system_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp DATETIME,
            cpuUsage REAL,
            memoryUsage REAL
        );
    ''')
    cur.execute('INSERT INTO system_metrics (timestamp, cpuUsage, memoryUsage) VALUES (?, ?, ?);', ('$TIMESTAMP', float('$CPU_USAGE'), float('$MEM_PERCENT')))
    conn.commit()
    conn.close()
except Exception as e:
    import sys
    print('SQLite DB Insertion Error:', e, file=sys.stderr)
"

# 4. CPU 임계치 검사 (임계치 이상 시 즉시 경고 메일 발송)
IS_CRITICAL=$(awk -v usage="$CPU_USAGE" -v limit="$CPU_ALERT_THRESHOLD" 'BEGIN {print (usage >= limit) ? 1 : 0}')

if [ "$IS_CRITICAL" -eq 1 ] && [ -n "$ADMIN_EMAIL" ]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Warning: CPU usage is high ($CPU_USAGE%). Sending alert email..."
    
    SUBJECT="[향린 Alert] High CPU Usage Warning on Server — ${CPU_USAGE}%"
    BODY="🚨 [CPU 사용률 경고 / CPU Usage Warning]
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🖥️  서버 / Server       : ${SERVER_NAME} (${SERVER_IP})
⏱️  감시 시각 / Time     : ${TIMESTAMP} KST
🔴  CPU 사용률 / CPU    : ${CPU_USAGE}%
🔵  메모리 사용률 / MEM  : ${MEM_PERCENT}% (${MEM_USED}MB / ${MEM_TOTAL}MB)
⚠️  경고 임계치 / Limit   : ${CPU_ALERT_THRESHOLD}%
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
서버 부하가 비정상적으로 높습니다. 실행 중인 프로세스 상태를 점검하십시오.
The server load is abnormally high. Please check the running processes immediately.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    python3 "$DIR/send_email.py" \
        --to "$ADMIN_EMAIL" \
        --cc "${ADMIN_EMAIL_CC:-}" \
        --subject "$SUBJECT" \
        --body "$BODY"
fi
