#!/bin/bash
set -e
WEB=/var/www/vhosts/localhost/html
C=church-rhymix
docker cp /tmp/cd_trait.php "$C:$WEB/modules/dmcadmin/dmcadmin.dispatch_mission.trait.php"
docker cp /tmp/cd_css.css "$C:$WEB/addons/church_theme/church_theme.css"
docker exec "$C" php "$WEB/scripts/publish_dispatch_mission.php"
docker exec "$C" sh -c "rm -rf $WEB/files/cache/template_compiled $WEB/files/cache/page 2>/dev/null; true"
curl -sL 'http://127.0.0.1:8000/index.php?mid=p27' > /tmp/p27.html
echo "prayer=$(grep -c 'id=\"cd-prayer\"' /tmp/p27.html || true)"
echo "aside=$(grep -c cd-aside-card /tmp/p27.html || true)"
python3 <<'PY'
from pathlib import Path
h = Path('/tmp/p27.html').read_text(encoding='utf-8', errors='ignore')
a = h.find('cd-aside')
c = h.find('cd-collage')
print('aside_before_photos', a >= 0 and c > a)
print('no_prayer', 'id="cd-prayer"' not in h)
PY
rm -f /tmp/cd_trait.php /tmp/cd_css.css
echo DONE
