#!/bin/bash

set -euo pipefail

BACKUP_DIR="/var/backups/entropiq"
CONTAINER_NAME="${1:-entropiq-prod-pgsql}"
DB_NAME="${DB_DATABASE:-entropiq}"
DB_USER="${DB_USERNAME:-entropiq}"
RETENTION_DAYS=7
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/entropiq_${TIMESTAMP}.sql.gz"

mkdir -p "${BACKUP_DIR}"

docker exec "${CONTAINER_NAME}" pg_dump -U "${DB_USER}" "${DB_NAME}" | gzip > "${BACKUP_FILE}"

if [ -f "${BACKUP_FILE}" ] && [ -s "${BACKUP_FILE}" ]; then
    echo "[$(date)] Backup created: ${BACKUP_FILE} ($(du -h "${BACKUP_FILE}" | cut -f1))"
else
    echo "[$(date)] ERROR: Backup failed or empty" >&2
    exit 1
fi

find "${BACKUP_DIR}" -name "entropiq_*.sql.gz" -mtime +${RETENTION_DAYS} -delete

echo "[$(date)] Old backups cleaned (retention: ${RETENTION_DAYS} days)"
