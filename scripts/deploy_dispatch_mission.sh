#!/bin/bash
# 파송선교(p27) A+ 배포: 페이지·admin·응원 API·CSS
set -euo pipefail
HOST="${HOST:-root@49.247.205.159}"
CONTAINER="${CONTAINER:-church-rhymix}"
WEB="${WEB:-/var/www/vhosts/localhost/html}"
BASE="$(cd "$(dirname "$0")/.." && pwd)"

FILES=(
  "modules/dmcadmin/dmcadmin.dispatch_mission.trait.php"
  "modules/dmcadmin/dmcadmin.overseas_mission.trait.php"
  "modules/dmcadmin/dmcadmin.model.php"
  "modules/dmcadmin/dmcadmin.view.php"
  "modules/dmcadmin/dmcadmin.controller.php"
  "modules/dmcadmin/dmcadmin.labels.php"
  "modules/dmcadmin/conf/module.xml"
  "modules/dmcadmin/tpl/dispatch_mission_edit.html"
  "modules/church_write/church_write.controller.php"
  "modules/church_write/church_write.model.php"
  "modules/church_write/conf/module.xml"
  "addons/church_theme/church_theme.css"
  "addons/church_dispatch_cheer/church_dispatch_cheer.addon.php"
  "addons/church_dispatch_cheer/church_dispatch_cheer.js"
  "addons/church_dispatch_cheer/conf/info.xml"
  "scripts/setup_dispatch_mission_page.php"
  "scripts/publish_dispatch_mission.php"
)

echo "== scp + install =="
for f in "${FILES[@]}"; do
  echo "  $f"
  scp -q "$BASE/$f" "$HOST:/tmp/cd_deploy_$(basename "$f")"
  ssh "$HOST" "mkdir -p '$WEB/$(dirname "$f")' && docker cp '/tmp/cd_deploy_$(basename "$f")' '$CONTAINER:$WEB/$f' && rm -f '/tmp/cd_deploy_$(basename "$f")'"
done

echo "== setup + publish =="
ssh "$HOST" "docker exec $CONTAINER php $WEB/scripts/setup_dispatch_mission_page.php"
ssh "$HOST" "docker exec $CONTAINER sh -c 'rm -rf $WEB/files/cache/module_info $WEB/files/cache/template_compiled $WEB/files/cache/page 2>/dev/null; true'"
ssh "$HOST" "docker exec $CONTAINER php -r 'if(function_exists(\"opcache_reset\")){opcache_reset(); echo \"opcache_ok\\n\";}'"
if ssh "$HOST" "docker exec $CONTAINER test -f $WEB/scripts/clear_cache.php"; then
  ssh "$HOST" "docker exec $CONTAINER php $WEB/scripts/clear_cache.php" || true
fi

echo "DONE"
echo "page: https://dmchurch.kr/index.php?mid=p27"
echo "admin: https://dmchurch.kr/index.php?mid=dmcadmin&act=dispDmcMgrDispatchMissionEdit"
