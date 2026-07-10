#!/bin/bash
# VPS 최초 1회: GitHub clone + 배포 스크립트 설치
set -euo pipefail

REPO_URL="${REPO_URL:-https://github.com/baesy69g-design/dmchurch.git}"
REPO_DIR="${REPO_DIR:-/root/dmchurch-git}"
CHURCH_SCRIPTS="/root/church-web/scripts"

echo "=== dmchurch Git 초기 설정 ==="

if ! command -v git >/dev/null 2>&1; then
	apt-get update -qq
	apt-get install -y -qq git rsync
fi

if [[ ! -d "$REPO_DIR/.git" ]]; then
	git clone "$REPO_URL" "$REPO_DIR"
else
	cd "$REPO_DIR"
	git pull --ff-only origin main || true
fi

mkdir -p "$CHURCH_SCRIPTS"
install -m 755 "$REPO_DIR/scripts/vps_pull_deploy.sh" "$CHURCH_SCRIPTS/vps_pull_deploy.sh"
install -m 755 "$REPO_DIR/scripts/vps_git_setup.sh" "$CHURCH_SCRIPTS/vps_git_setup.sh"

echo "=== 최초 배포 실행 ==="
bash "$CHURCH_SCRIPTS/vps_pull_deploy.sh"

echo "=== 설정 완료 ==="
echo "  이후 PC: syncup.bat → pulldeploy.bat"
echo "  또는 VPS: bash $CHURCH_SCRIPTS/vps_pull_deploy.sh"
