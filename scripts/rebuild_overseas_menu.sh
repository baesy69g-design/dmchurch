#!/bin/bash
set -e
# Properly rebuild menu XML after DB sync
docker exec church-rhymix php -r '
chdir("/var/www/vhosts/localhost/html");
require "common/autoload.php";
Context::init();
$menu_srl = 48;
$oMenuAdminController = getController("menu");
if (!$oMenuAdminController) {
  $oMenuAdminController = getAdminController("menu");
}
if (method_exists($oMenuAdminController, "makeXmlFile")) {
  $oMenuAdminController->makeXmlFile($menu_srl);
  echo "makeXmlFile ok\n";
} else {
  echo "no makeXmlFile, methods: ";
  print_r(get_class_methods($oMenuAdminController));
}
'
rm -rf /root/church-web/html/files/cache/template/* 2>/dev/null || true
docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/publish_overseas_mission.php
docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/clear_cache.php

echo '=== lnb on p26 ==='
curl -s http://127.0.0.1:8000/p26 > /tmp/p26.html
python3 - <<'PY'
from pathlib import Path
import re
h=Path('/tmp/p26.html').read_text(encoding='utf-8',errors='ignore')
i=h.find('class="lnb"')
chunk=h[i:i+1800] if i>=0 else ''
print(chunk)
print('---')
print('p265 in page', 'p265' in h)
print('허수성 count', h.count('허수성'))
# extract overseas nested links
m=re.search(r'해외선교</a>\s*<ul>(.*?)</ul>', chunk, re.S)
if m:
  print('nested:', re.findall(r'href="[^"]+">(.*?)</a>', m.group(1)))
PY
