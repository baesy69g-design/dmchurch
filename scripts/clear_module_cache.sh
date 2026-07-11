#!/bin/bash
set -euo pipefail
CACHE=/root/church-web/html/files/cache
echo "=== cache tree ==="
find "$CACHE" -maxdepth 2 -type d 2>/dev/null
# wipe common rhymix caches
rm -rf "$CACHE/module_info" "$CACHE/store" "$CACHE/template" "$CACHE/template_compiled" 2>/dev/null || true
find "$CACHE" -type f -name '*church_write*' -delete 2>/dev/null || true
find "$CACHE" -type f -name 'module.xml*.php' -delete 2>/dev/null || true
find "$CACHE" -type f -name '*module_action*' -delete 2>/dev/null || true
# also check docker path
docker exec church-rhymix sh -c 'rm -rf /var/www/vhosts/localhost/html/files/cache/module_info /var/www/vhosts/localhost/html/files/cache/store /var/www/vhosts/localhost/html/files/cache/template /var/www/vhosts/localhost/html/files/cache/template_compiled 2>/dev/null; find /var/www/vhosts/localhost/html/files/cache -type f -name "*church_write*" -delete 2>/dev/null; true'
docker exec church-rhymix php -r 'if(function_exists("opcache_reset")) opcache_reset();'
docker exec church-rhymix php /tmp/smoke_board_edit.php
