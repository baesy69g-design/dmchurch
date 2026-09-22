#!/bin/bash
curl -sL 'http://127.0.0.1:8000/index.php?mid=p27' > /tmp/p27.html
echo "kicker=$(grep -c cd-kicker /tmp/p27.html)"
echo "title=$(grep -c 'class=\"cd-title\"' /tmp/p27.html)"
echo "lead=$(grep -c cd-aside-line--lead /tmp/p27.html)"
echo "prayer=$(grep -c 'id=\"cd-prayer\"' /tmp/p27.html)"
python3 <<'PY'
from pathlib import Path
h = Path('/tmp/p27.html').read_text(encoding='utf-8', errors='ignore')
hs = h.find('cd-hero-stage')
pr = h.find('id="cd-prayer"')
print('prayer_below_photos', hs >= 0 and pr > hs)
print('no_kicker', 'cd-kicker' not in h)
print('no_title_block', 'class="cd-title"' not in h)
PY
docker exec church-rhymix grep -n 'cd-hero-stage' -A4 /var/www/vhosts/localhost/html/addons/church_theme/church_theme.css | head -8
