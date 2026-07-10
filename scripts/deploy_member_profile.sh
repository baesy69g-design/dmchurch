#!/bin/bash
# 회원/레이아웃 관련 변경 배포
set -e
HOST="root@49.247.205.159"
CONTAINER="church-rhymix"
WEB="/var/www/vhosts/localhost/html"

FILES=(
  "layouts/xedition/layout.html"
  "layouts/xedition/css/church_welcome.css"
  "modules/church_member/conf/module.xml"
  "modules/church_member/church_member.model.php"
  "modules/church_member/church_member.view.php"
  "modules/church_member/church_member.controller.php"
  "modules/church_member/church_member.css"
  "modules/church_member/tpl/profile.html"
  "addons/church_member_onboard/church_member_onboard.addon.php"
  "modules/dmcadmin/dmcadmin.model.php"
  "modules/dmcadmin/dmcadmin.view.php"
  "modules/dmcadmin/tpl/members.html"
  "modules/dmcadmin/tpl/member_form.html"
  "modules/dmcadmin/dmcadmin.css"
  "scripts/restore_member_from_rankup.php"
)

BASE="$(cd "$(dirname "$0")/.." && pwd)"
for f in "${FILES[@]}"; do
  scp "$BASE/$f" "$HOST:/tmp/$(basename "$f")"
  docker cp "/tmp/$(basename "$f")" "$CONTAINER:$WEB/$f"
done

docker exec "$CONTAINER" php "$WEB/scripts/install_church_member_actions.php" 2>/dev/null || true
docker exec "$CONTAINER" rm -rf "$WEB/files/cache/template/*"
docker exec "$CONTAINER" php "$WEB/scripts/restore_member_from_rankup.php" baesy69 2>/dev/null || true

echo "Deploy complete."
