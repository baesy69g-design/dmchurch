#!/bin/bash
set -e
echo "=== p265 ==="
curl -s -o /tmp/p265.html -w "http:%{http_code}\n" http://127.0.0.1:8000/p265
grep -oE '허수성|church-mission-detail|서버 오류|Template not found|피사눌룩' /tmp/p265.html | sort | uniq -c || true
echo "=== p26 has p265 link? ==="
curl -s http://127.0.0.1:8000/p26 > /tmp/p26.html
grep -c 'p265' /tmp/p26.html || true
grep -n '허수성' /tmp/p26.html | head -5 || true
echo "=== lnb snippet ==="
python3 - <<'PY'
from pathlib import Path
h=Path('/tmp/p26.html').read_text(encoding='utf-8',errors='ignore')
i=h.find('class="lnb"')
print(h[i:i+1200] if i>=0 else 'NO LNB')
PY
echo "=== republish ==="
docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/publish_overseas_mission.php
docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/clear_cache.php
# also clear menu cache if any
rm -rf /root/church-web/html/files/cache/menu/* /root/church-web/html/files/cache/template/* 2>/dev/null || true
echo "=== after republish p265 ==="
curl -s -o /tmp/p265b.html -w "http:%{http_code}\n" http://127.0.0.1:8000/p265
grep -oE '허수성|church-mission-detail|서버 오류|우리와 늘' /tmp/p265b.html | sort | uniq -c || true
echo "=== page module content length ==="
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -N -e "SELECT mid, CHAR_LENGTH(content) FROM rx_modules WHERE mid IN ('p264','p265','p261');"
