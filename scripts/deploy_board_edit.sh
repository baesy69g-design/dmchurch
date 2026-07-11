#!/bin/bash
set -euo pipefail
BASE=/root/church-web/html
# clear rhymix caches that may hold old module.xml actions
rm -f "$BASE"/files/cache/module_info/* 2>/dev/null || true
rm -rf "$BASE"/files/cache/template_compiled 2>/dev/null || true
find "$BASE"/files/cache -name '*church_write*' -delete 2>/dev/null || true
docker exec church-rhymix php -r 'if(function_exists("opcache_reset")){opcache_reset(); echo "opcache_ok\n";}'
# touch assets for cache bust
touch "$BASE"/addons/church_board_ui/church_board_ui.js
touch "$BASE"/addons/church_board_ui/church_board_ui.css
touch "$BASE"/board_skins/picturegallery/picture_gallery.css
touch "$BASE"/board_skins/sermongallery/church_sermon.css
echo DONE
grep -n 'procChurchWriteUpdateDocument\|dispChurchWriteGetDocument' "$BASE"/modules/church_write/conf/module.xml
grep -n 'church-pic-edit\|church-sermon-edit' "$BASE"/board_skins/picturegallery/list.html "$BASE"/board_skins/sermongallery/list.html | head
