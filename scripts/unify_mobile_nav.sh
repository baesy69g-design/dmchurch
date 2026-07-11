#!/bin/bash
# Unify mobile navigation: always PC xedition layout + hamburger ≤900px
set -euo pipefail

HTML=/root/church-web/html

echo "=== 1) Disable Rhymix true-mobile layout (config.php) ==="
python3 <<'PY'
from pathlib import Path
import re
p = Path("/root/church-web/html/files/config/config.php")
text = p.read_text(encoding="utf-8")
if re.search(r"'mobile'\s*=>\s*array\s*\(\s*'enabled'\s*=>\s*false\s*,", text):
    print("OK: mobile.enabled already false")
else:
    text2, n = re.subn(
        r"('mobile'\s*=>\s*array\s*\(\s*'enabled'\s*=>\s*)true\s*,",
        r"\1false,",
        text,
        count=1,
    )
    if n != 1:
        raise SystemExit(f"mobile.enabled replace failed n={n}")
    p.write_text(text2, encoding="utf-8")
    print("OK: mobile.enabled => false")
PY

echo "=== 2) Set all modules use_mobile=N ==="
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e \
  "SELECT use_mobile, COUNT(*) c FROM rx_modules GROUP BY use_mobile;
   UPDATE rx_modules SET use_mobile='N' WHERE IFNULL(use_mobile,'') <> 'N';
   SELECT use_mobile, COUNT(*) c FROM rx_modules GROUP BY use_mobile;"

echo "=== 3) Align layout.js pc-gnb breakpoint to 901px ==="
python3 <<'PY'
from pathlib import Path
p = Path("/root/church-web/html/layouts/xedition/js/layout.js")
text = p.read_text(encoding="utf-8")
old = "if($(document).width() > 480){"
new = "if(window.matchMedia('(min-width: 901px)').matches){"
if old not in text:
    if new in text:
        print("Already patched")
    else:
        raise SystemExit("layout.js pattern not found")
else:
    p.write_text(text.replace(old, new, 1), encoding="utf-8")
    print("OK: layout.js matchMedia min-width 901px")
PY

echo "=== 4) Clear cache ==="
docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/clear_cache.php

UA='Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15'
echo "=== 5) Verify mobile UA pages have hamburger, no .hd ==="
for path in / /sermon /jubo /p25 /p26 /p108; do
  html=$(curl -s -A "$UA" "http://127.0.0.1:8000$path")
  btn=$(printf '%s' "$html" | grep -c 'mobile_menu_btn' || true)
  hd=$(printf '%s' "$html" | grep -c 'class="hd"' || true)
  echo "$path  hamburger=$btn  hd=$hd"
done

echo "DONE"
