#!/bin/bash
# =============================================================================
# setup_cron.sh
# 향린교회 서버 모니터링 및 일일 보고 Crontab 등록 스크립트
# 실행 방법: ./setup_cron.sh
# =============================================================================

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
LOG_DIR="/home/wonhyukc/logs"

mkdir -p "$LOG_DIR"

# 기존 crontab 가져오기
crontab -l > /tmp/current_cron 2>/dev/null || true

# 이미 등록되어 있는지 확인
if grep -q "daily_report.sh" /tmp/current_cron; then
    echo "⚠️ 이미 Crontab에 향린 서버 일일 보고 스크립트가 등록되어 있습니다."
    echo "현재 Crontab을 확인하려면 'crontab -l' 명령어를 사용하세요."
    rm -f /tmp/current_cron
    exit 0
fi

# 새로운 cron 작업 추가
echo "" >> /tmp/current_cron
echo "# ==========================================================" >> /tmp/current_cron
echo "# 향린교회 서버 자동 모니터링 & 일일 보고 시스템" >> /tmp/current_cron
echo "# ==========================================================" >> /tmp/current_cron
echo "# 1. 서버 CPU/메모리 1시간 단위 정기 측정 및 위험(90%) 경보 (매시 23분)" >> /tmp/current_cron
echo "23 * * * * $DIR/cpu_monitor.sh >> $LOG_DIR/cpu_monitor.log 2>&1" >> /tmp/current_cron
echo "# 2. 디스크 파티션 사용량 30분 단위 정기 점검 및 경보 (매 30분)" >> /tmp/current_cron
echo "*/30 * * * * $DIR/disk_alert.sh >> $LOG_DIR/disk_alert.log 2>&1" >> /tmp/current_cron
echo "# 3. 매일 06:05 종합 운영 보고 메일 발송" >> /tmp/current_cron
echo "5 6 * * * $DIR/daily_report.sh >> $LOG_DIR/daily_report.log 2>&1" >> /tmp/current_cron

# 새로운 crontab 적용
crontab /tmp/current_cron
rm -f /tmp/current_cron

echo "✅ Crontab 등록 완료!"
echo "등록된 전체 작업 목록 (crontab -l):"
crontab -l
