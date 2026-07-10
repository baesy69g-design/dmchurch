#!/usr/bin/env bash
# =============================================================================
# 동명교회 Rhymix VPS 통합 백업 (church-rhymix + church-mariadb + PostgreSQL)
#
# 매일 cron 실행 권장. 10일 초과분 자동 삭제.
#
#   sudo bash /root/church-web/scripts/church_backup.sh
#   0 2 * * * /bin/bash /root/church-web/scripts/church_backup.sh >> /var/log/church_backup.log 2>&1
# =============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CHURCH_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

# 서버 전용 (Git 제외): /root/church-web/backup.env
if [[ -f "$CHURCH_ROOT/backup.env" ]]; then
  # shellcheck disable=SC1090
  source "$CHURCH_ROOT/backup.env"
fi

BACKUP_ROOT="${BACKUP_ROOT:-/root/backups/church}"
RETENTION_DAYS="${RETENTION_DAYS:-10}"
MARIADB_CONTAINER="${MARIADB_CONTAINER:-church-mariadb}"
MARIADB_ROOT_PASSWORD="${MARIADB_ROOT_PASSWORD:-}"
MARIADB_DATABASE="${MARIADB_DATABASE:-rmx_db}"
PG_DATABASES="${PG_DATABASES:-portfolio_v25}"
HTML="${HTML:-$CHURCH_ROOT/html}"

STAMP="$(date +%Y%m%d_%H%M%S)"
WORK="$(mktemp -d)"
DEST="$WORK/church_backup_$STAMP"
ARCHIVE="$BACKUP_ROOT/church_backup_$STAMP.tar.gz"

mkdir -p "$BACKUP_ROOT" "$DEST"/{mariadb,postgresql,files,source,system}

echo "[church_backup] 시작: $STAMP"

# ---- 1) Docker MariaDB (Rhymix DB) ------------------------------------------
if docker ps --format '{{.Names}}' | grep -qx "$MARIADB_CONTAINER"; then
  if [[ -z "$MARIADB_ROOT_PASSWORD" ]]; then
    echo "[church_backup] 경고: MARIADB_ROOT_PASSWORD 없음 → MariaDB 건너뜀" >&2
  else
    docker exec "$MARIADB_CONTAINER" \
      mysqldump -uroot -p"$MARIADB_ROOT_PASSWORD" \
      --single-transaction --routines --triggers --events \
      "$MARIADB_DATABASE" \
      | gzip -9 > "$DEST/mariadb/${MARIADB_DATABASE}.sql.gz"
    echo "[church_backup] MariaDB dump 완료: ${MARIADB_DATABASE}.sql.gz"
  fi
else
  echo "[church_backup] 경고: 컨테이너 없음: $MARIADB_CONTAINER" >&2
fi

# ---- 2) PostgreSQL (호스트, Docker 밖) --------------------------------------
if command -v pg_dump >/dev/null 2>&1 && systemctl is-active --quiet postgresql 2>/dev/null; then
  IFS=',' read -ra PG_DBS <<< "$PG_DATABASES"
  for db in "${PG_DBS[@]}"; do
    db="$(echo "$db" | xargs)"
    [[ -n "$db" ]] || continue
    if sudo -u postgres psql -Atqc "SELECT 1 FROM pg_database WHERE datname='$db'" | grep -qx 1; then
      sudo -u postgres pg_dump "$db" | gzip -9 > "$DEST/postgresql/${db}.sql.gz"
      echo "[church_backup] PostgreSQL dump 완료: ${db}.sql.gz"
    else
      echo "[church_backup] (건너뜀) PostgreSQL DB 없음: $db"
    fi
  done
else
  echo "[church_backup] PostgreSQL 미실행 → 건너뜀"
fi

# ---- 3) Rhymix 업로드·교회 JSON (사진·첨부) ---------------------------------
if [[ -d "$HTML/files" ]]; then
  tar -czf "$DEST/files/rhymix_files.tar.gz" -C "$HTML" \
    files/church \
    files/attach \
    files/member \
    2>/dev/null || true
  echo "[church_backup] files/ 압축 완료"
fi

# ---- 4) 커스텀 소스 (Git에 없는 VPS 수정분 포함) ----------------------------
SOURCE_PATHS=(
  modules/dmcadmin
  modules/church_member
  modules/church_write
  modules/page/tpl
  addons
  layouts/xedition
  board_skins
  modules/member/skins/default
  modules/member/m.skins/default
)

tar_args=()
for rel in "${SOURCE_PATHS[@]}"; do
  [[ -e "$HTML/$rel" ]] && tar_args+=("$rel")
done
if ((${#tar_args[@]} > 0)); then
  tar -czf "$DEST/source/custom_source.tar.gz" -C "$HTML" "${tar_args[@]}"
  echo "[church_backup] 커스텀 소스 압축 완료"
fi

# ---- 5) 시스템 설정 ---------------------------------------------------------
[[ -f "$CHURCH_ROOT/docker-compose.yml" ]] && \
  cp -a "$CHURCH_ROOT/docker-compose.yml" "$DEST/system/"
[[ -f /etc/systemd/system/church-web.service ]] && \
  cp -a /etc/systemd/system/church-web.service "$DEST/system/"
[[ -f "$HTML/config/config.inc.php" ]] && \
  cp -a "$HTML/config/config.inc.php" "$DEST/system/config.inc.php"

echo "[church_backup] 시스템 설정 백업 완료"

# ---- 6) 아카이브 + 보관 기간 정리 -------------------------------------------
tar -czf "$ARCHIVE" -C "$WORK" "church_backup_$STAMP"
chmod 600 "$ARCHIVE"
rm -rf "$WORK"

echo "[church_backup] 아카이브: $ARCHIVE"
echo "[church_backup] 크기: $(du -h "$ARCHIVE" | cut -f1)"

find "$BACKUP_ROOT" -name 'church_backup_*.tar.gz' -type f -mtime +"$RETENTION_DAYS" -print -delete
echo "[church_backup] ${RETENTION_DAYS}일 초과 백업 정리 완료"
echo "[church_backup] 완료"
