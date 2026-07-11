#!/bin/bash
set -euo pipefail
curl -sk -A 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15' \
  -H 'Host: dmchurch.kr' https://127.0.0.1/ -o /tmp/home.html
echo "=== size ==="
wc -c /tmp/home.html
echo "=== classes ==="
grep -oE 'class="[^"]+"' /tmp/home.html | head -5
python3 - <<'PY'
import re
html=open('/tmp/home.html','r',encoding='utf-8',errors='ignore').read()
for pat in ['onepage','magazine','header_wrap','fixed_header','logo.jpg','logo.png','church_sub_top','church_theme.css','container']:
    print(pat, html.count(pat))
# logo img tag
for m in re.finditer(r'<h1[^>]*logo-item[\s\S]*?</h1>', html):
    print('LOGO_BLOCK:', m.group(0)[:500])
    break
# header wrap snippet
i=html.find('header_wrap')
print('around header_wrap:', html[max(0,i-80):i+200])
PY
echo "=== logo files ==="
docker exec church-rhymix php -r 'print_r(@getimagesize("/var/www/vhosts/localhost/html/files/church/logo.jpg")); print_r(@getimagesize("/var/www/vhosts/localhost/html/files/church/logo.png"));'
echo "=== find larger logos ==="
find /root/church-web -iname '*logo*' -type f 2>/dev/null | while read f; do
  sz=$(stat -c%s "$f" 2>/dev/null || echo 0)
  if [ "$sz" -gt 5000 ]; then echo "$sz $f"; fi
done | sort -rn | head -30
echo "=== layout logo settings ==="
docker exec church-rhymix php -r '
require "/var/www/vhosts/localhost/html/common/autoload.php";
Context::init();
$o=ModuleModel::getInstance();
$info=$o->getModuleInfoByMid("index") ?: $o->getModuleInfoByMid("home");
' 2>/dev/null || true
grep -R "logo" /root/church-web/html/files/config/ 2>/dev/null | head -20
ls /root/church-web/html/files/attach/images/ 2>/dev/null | head
find /root/church-web/html/files/attach -iname '*logo*' -o -iname '*동명*' 2>/dev/null | head -20
