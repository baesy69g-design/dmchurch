#!/bin/bash
set -e
curl -s -A 'iPhone' http://127.0.0.1:8000/p108 > /tmp/p108m.html
echo '=== PC hrefs ==='
grep -o 'href="[^"]*"' /tmp/p108m.html | grep -i pc | head
echo '=== view-switch blocks ==='
grep -n 'church-view-switch\|PC 화면\|church_view_pc' /tmp/p108m.html | head -20
echo '=== script src ==='
grep -o 'src="[^"]*church_view_pc[^"]*"' /tmp/p108m.html || true
echo '=== viewport ==='
grep -i viewport /tmp/p108m.html | head -5
echo '=== getUrl test via php ==='
docker exec church-rhymix php -r 'chdir("/var/www/vhosts/localhost/html"); require "common/autoload.php"; Context::init(); echo getUrl("pc","1"), "\n"; echo getUrl("","mid","p108","pc","1"), "\n";'
