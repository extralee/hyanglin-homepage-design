#!/bin/bash
# ============================================================
# 향린교회 홈페이지 MySQL DB 자동 백업 스크립트
# ============================================================
# 실행: sudo ./scripts/backup-db.sh
# Cron: 0 3 * * * (매일 새벽 3시)
#
# 대상: 호스트 MySQL (hr, hr2 데이터베이스)
# 주의: sudo 권한으로 실행해야 합니다 (mysqldump 접근용)
# ============================================================

# ── 설정 변수 ──
PROJECT_DIR="/home/wonhyukc/hyanglin-legacy"
BACKUP_DIR="${PROJECT_DIR}/backups/daily"
LOG_FILE="${PROJECT_DIR}/backups/backup.log"
RETENTION_DAYS=7
NOTIFY_EMAIL="williamc@dplus.jeju.kr"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="hyanglin_db_backup_${TIMESTAMP}.sql.gz"

# 백업 대상 DB
TARGET_DATABASES="hr hr2"

# ── 디렉터리 확인 ──
mkdir -p "${BACKUP_DIR}"

# ── 로그 함수 ──
log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "${LOG_FILE}"; }

# ── 백업 실행 ──
log "=== 백업 시작: ${BACKUP_FILE} ==="

# mysqldump를 임시 SQL 파일로 출력
DUMP_TMPFILE="${BACKUP_DIR}/.tmp_${TIMESTAMP}.sql"

mysqldump \
    --databases ${TARGET_DATABASES} \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    > "${DUMP_TMPFILE}" 2>>"${LOG_FILE}"

DUMP_EXIT=$?

if [ ${DUMP_EXIT} -ne 0 ]; then
    log "❌ 백업 실패: mysqldump 종료 코드 ${DUMP_EXIT}"
    rm -f "${DUMP_TMPFILE}"
    STATUS="FAILURE"
    SUBJECT="[향린] ⚠️ DB 백업 실패 - ${TIMESTAMP}"
    BODY="mysqldump 실행에 실패했습니다 (exit: ${DUMP_EXIT}).\n시각: $(date '+%Y-%m-%d %H:%M:%S')\n로그: ${LOG_FILE}"
elif [ ! -s "${DUMP_TMPFILE}" ]; then
    log "❌ 백업 실패: 덤프 파일이 비어 있습니다."
    rm -f "${DUMP_TMPFILE}"
    STATUS="FAILURE"
    SUBJECT="[향린] ⚠️ DB 백업 실패 - ${TIMESTAMP}"
    BODY="mysqldump 출력이 비어 있습니다.\n시각: $(date '+%Y-%m-%d %H:%M:%S')\n로그: ${LOG_FILE}"
else
    DUMP_SIZE=$(du -h "${DUMP_TMPFILE}" | cut -f1)
    log "📦 SQL 덤프 완료: ${DUMP_SIZE}"

    # gzip 압축
    gzip "${DUMP_TMPFILE}"
    if [ $? -eq 0 ] && [ -f "${DUMP_TMPFILE}.gz" ]; then
        mv "${DUMP_TMPFILE}.gz" "${BACKUP_DIR}/${BACKUP_FILE}"
        FILESIZE=$(du -h "${BACKUP_DIR}/${BACKUP_FILE}" | cut -f1)
        log "✅ 백업 성공: ${BACKUP_FILE} (${FILESIZE})"
        STATUS="SUCCESS"
        SUBJECT="[향린] DB 백업 성공 - ${TIMESTAMP}"
        BODY="백업 파일: ${BACKUP_FILE}\n원본 크기: ${DUMP_SIZE} → 압축: ${FILESIZE}\n보존 기간: ${RETENTION_DAYS}일"
    else
        log "❌ 백업 실패: gzip 압축 오류"
        rm -f "${DUMP_TMPFILE}" "${DUMP_TMPFILE}.gz"
        STATUS="FAILURE"
        SUBJECT="[향린] ⚠️ DB 백업 실패 - ${TIMESTAMP}"
        BODY="gzip 압축에 실패했습니다.\n시각: $(date '+%Y-%m-%d %H:%M:%S')\n로그: ${LOG_FILE}"
    fi
fi

# ── 오래된 백업 자동 삭제 ──
DELETED=$(find "${BACKUP_DIR}" -name "hyanglin_db_backup_*.sql.gz" -mtime +${RETENTION_DAYS} -delete -print | wc -l)
if [ "${DELETED}" -gt 0 ]; then
    log "🗑️ ${RETENTION_DAYS}일 이상 된 백업 ${DELETED}개 삭제"
    BODY="${BODY}\n\n정리: ${DELETED}개의 오래된 백업 파일 삭제됨"
fi

# ── 현재 백업 현황 ──
BACKUP_COUNT=$(find "${BACKUP_DIR}" -name "hyanglin_db_backup_*.sql.gz" 2>/dev/null | wc -l)
BACKUP_TOTAL_SIZE=$(du -sh "${BACKUP_DIR}" 2>/dev/null | cut -f1)
BODY="${BODY}\n\n현재 백업 현황: ${BACKUP_COUNT}개 파일, 총 ${BACKUP_TOTAL_SIZE}"

# ── 이메일 알림 전송 ──
MSMTP_BIN=$(command -v msmtp 2>/dev/null || echo "/usr/bin/msmtp")
MSMTPRC="/home/wonhyukc/.msmtprc"

if [ -x "${MSMTP_BIN}" ] && [ -f "${MSMTPRC}" ]; then
    printf "Subject: %s\nTo: %s\nFrom: %s\nContent-Type: text/plain; charset=UTF-8\n\n%b" \
        "${SUBJECT}" "${NOTIFY_EMAIL}" "${NOTIFY_EMAIL}" "${BODY}" \
        | "${MSMTP_BIN}" -C "${MSMTPRC}" "${NOTIFY_EMAIL}" 2>>"${LOG_FILE}" && \
        log "📧 알림 이메일 전송 완료 → ${NOTIFY_EMAIL}" || \
        log "⚠️ 이메일 전송 실패 (msmtp 오류, 로그 확인)"
else
    log "⚠️ msmtp 미설치 또는 설정 파일 없음 — 이메일 알림 건너뜀"
fi

log "=== 백업 완료 ==="
