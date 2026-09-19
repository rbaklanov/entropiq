#!/bin/bash

set -euo pipefail

umask 077

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="/var/backups/entropiq"
CONTAINER_NAME="${1:-entropiq-prod-pgsql}"
RETENTION_DAYS=7
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_FILE="${BACKUP_DIR}/entropiq_${TIMESTAMP}.dump"
TMP_FILE="${BACKUP_FILE}.tmp"

env_value() {
    local key="$1"
    local default="${2:-}"

    if [ -n "${!key:-}" ]; then
        echo "${!key}"
        return
    fi

    if [ -f "${PROJECT_DIR}/.env" ]; then
        local value
        value="$(grep -E "^${key}=" "${PROJECT_DIR}/.env" | tail -n 1 | cut -d '=' -f2- | tr -d "\"'")"
        if [ -n "${value}" ]; then
            echo "${value}"
            return
        fi
    fi

    echo "${default}"
}

DB_NAME="$(env_value DB_DATABASE entropiq)"
DB_USER="$(env_value DB_USERNAME entropiq)"

mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

trap 'rm -f "${TMP_FILE}"' EXIT INT TERM

docker exec "${CONTAINER_NAME}" pg_dump -U "${DB_USER}" -Fc --exclude-table-data='pulse_*' "${DB_NAME}" > "${TMP_FILE}"
chmod 600 "${TMP_FILE}"

if [ ! -s "${TMP_FILE}" ]; then
    echo "[$(date)] ERROR: Backup failed or empty" >&2
    exit 1
fi

if ! docker exec -i "${CONTAINER_NAME}" pg_restore -l < "${TMP_FILE}" > /dev/null; then
    echo "[$(date)] ERROR: Backup failed pg_restore list check" >&2
    exit 1
fi

mv "${TMP_FILE}" "${BACKUP_FILE}"
chmod 600 "${BACKUP_FILE}"
trap - EXIT INT TERM

echo "[$(date)] Backup created: ${BACKUP_FILE} ($(du -h "${BACKUP_FILE}" | cut -f1))"

find "${BACKUP_DIR}" -name "entropiq_*.dump" -mtime +${RETENTION_DAYS} -delete
find "${BACKUP_DIR}" -name "entropiq_*.sql.gz" -mtime +${RETENTION_DAYS} -delete

echo "[$(date)] Old backups cleaned (retention: ${RETENTION_DAYS} days)"
