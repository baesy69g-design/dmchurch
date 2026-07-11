#!/bin/bash
set -euo pipefail
# PC layout on phone viewport
curl -sk -A 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36' \
  -H 'Host: dmchurch.kr' 'https://127.0.0.1/?m=0' -o /tmp/pc_mobile.html
echo 'size' $(wc -c </tmp/pc_mobile.html)
python3 - <<'PY'
import re
html=open('/tmp/pc_mobile.html',encoding='utf-8',errors='ignore').read()
print('xe-mobilelayout', 'xe-mobilelayout' in html)
print('header_wrap', html.count('header_wrap'))
print('logo-item', html.count('logo-item'))
print('mobile_menu_btn', html.count('mobile_menu_btn'))
print('church_theme', html.count('church_theme'))
print('detectColorScheme', html.count('detectColorScheme'))
print('color-scheme/dark', html.count('dark'), html.count('color-scheme'))
m=re.search(r'<div class="header_wrap[\s\S]{0,800}', html)
print('HEADER:', m.group(0)[:700] if m else 'NONE')
for href in re.findall(r'href="([^"]+\.css[^"]*)"', html)[:25]:
    print('CSS', href)
PY
echo '==== dark styles in compiled/theme ===='
docker exec church-rhymix sh -c 'grep -oE ".{0,40}prefers-color-scheme.{0,80}|\[data-color-scheme.{0,80}|html\.dark.{0,80}|\.dark .{0,60}header.{0,40}" /var/www/vhosts/localhost/html/files/cache/assets/compiled/*.css 2>/dev/null | head -30'
docker exec church-rhymix sh -c 'grep -n "header_wrap\|prefers-color\|color-scheme\|\.dark" /var/www/vhosts/localhost/html/addons/church_theme/church_theme.css | head -40'
echo '==== logo fixed rule ===='
docker exec church-rhymix sh -c 'sed -n "114,130p" /var/www/vhosts/localhost/html/addons/church_sub_top/church_sub_top.css'
echo '==== check site protect / gnb dark ===='
docker exec church-rhymix sh -c 'grep -n "header\|background\|#0\|#1b\|dark" /var/www/vhosts/localhost/html/addons/church_site_protect/church_site_protect.css /var/www/vhosts/localhost/html/addons/church_gnb/church_gnb.css 2>/dev/null | head -40'
