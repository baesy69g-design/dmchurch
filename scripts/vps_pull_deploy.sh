#!/bin/bash
# GitHub dmchurch 저장소 → /root/church-web/html 반영 + 캐시 삭제
set -euo pipefail

REPO_DIR="${REPO_DIR:-/root/dmchurch-git}"
WEB="${WEB:-/root/church-web/html}"
CONTAINER="${CONTAINER:-church-rhymix}"
BRANCH="${BRANCH:-main}"

if [[ ! -d "$REPO_DIR/.git" ]]; then
	echo "[오류] Git 저장소 없음: $REPO_DIR"
	echo "  bash $REPO_DIR/scripts/vps_git_setup.sh 먼저 실행"
	exit 1
fi

echo "=== git pull ($REPO_DIR) ==="
cd "$REPO_DIR"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

sync_dir() {
	local src="$1"
	local dst="$2"
	if [[ -d "$src" ]]; then
		mkdir -p "$dst"
		rsync -a --delete "$src/" "$dst/"
		echo "  synced: $src -> $dst"
	fi
}

overlay_dir() {
	local src="$1"
	local dst="$2"
	if [[ -d "$src" ]]; then
		mkdir -p "$dst"
		rsync -a "$src/" "$dst/"
		echo "  overlaid: $src -> $dst"
	fi
}

echo "=== 파일 동기화 ==="
sync_dir "$REPO_DIR/modules/dmcadmin"              "$WEB/modules/dmcadmin"
sync_dir "$REPO_DIR/modules/church_member"         "$WEB/modules/church_member"
sync_dir "$REPO_DIR/modules/church_write"          "$WEB/modules/church_write"
sync_dir "$REPO_DIR/modules/page/tpl"              "$WEB/modules/page/tpl"
sync_dir "$REPO_DIR/layouts"                       "$WEB/layouts"
sync_dir "$REPO_DIR/m.layouts"                     "$WEB/m.layouts"
sync_dir "$REPO_DIR/addons"                        "$WEB/addons"
sync_dir "$REPO_DIR/board_skins"                   "$WEB/board_skins"
sync_dir "$REPO_DIR/scripts"                       "$WEB/scripts"

# patches → 기존 Rhymix 파일 위에 덮어쓰기만 (--delete 사용 금지)
if [[ -d "$REPO_DIR/patches/member/skins/default" ]]; then
	overlay_dir "$REPO_DIR/patches/member/skins/default" "$WEB/modules/member/skins/default"
fi
if [[ -d "$REPO_DIR/patches/member/m.skins/default" ]]; then
	overlay_dir "$REPO_DIR/patches/member/m.skins/default" "$WEB/modules/member/m.skins/default"
fi
if [[ -d "$REPO_DIR/patches/layouts/xedition" ]]; then
	overlay_dir "$REPO_DIR/patches/layouts/xedition" "$WEB/layouts/xedition"
fi

echo "=== 캐시 삭제 ==="
docker exec "$CONTAINER" rm -rf /var/www/vhosts/localhost/html/files/cache/template/* 2>/dev/null || true
docker exec "$CONTAINER" php /var/www/vhosts/localhost/html/scripts/clear_cache.php 2>/dev/null || true

echo "=== 배포 완료 ==="
echo "  사이트: https://dmchurch.kr"
echo "  관리자: https://dmchurch.kr/index.php?mid=dmcadmin"
