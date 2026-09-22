#!/bin/bash
docker exec church-mariadb mariadb -urmx_user -prmx!!4321 rmx_db -e "
SELECT menu_item_srl, parent_srl, name, url, listorder
FROM rx_menu_item
WHERE parent_srl = (SELECT menu_item_srl FROM rx_menu_item WHERE url='p26' ORDER BY menu_item_srl LIMIT 1)
   OR url='p27'
ORDER BY listorder, menu_item_srl;
"
curl -sL 'http://127.0.0.1:8000/index.php?mid=p27' > /tmp/p27.html
echo "lnb_count=$(grep -c 'class=\"lnb\"' /tmp/p27.html)"
python3 - <<'PY'
from pathlib import Path
import re
h=Path('/tmp/p27.html').read_text(encoding='utf-8',errors='ignore')
i=h.find('class="lnb"')
print('HAS_LNB' if i>=0 else 'NO_LNB')
if i>=0:
    chunk=h[i:i+2500]
    # extract overseas submenu links
    for m in re.finditer(r'<a href="[^"]*(p2\d+)[^"]*">([^<]+)</a>', chunk):
        print(m.group(1), m.group(2))
print('visual.sub', 'YES' if 'visual sub' in h or 'class="visual sub"' in h else 'NO')
print('on_p27', 'YES' if re.search(r'li class="on"[^>]*>\s*<a[^>]*>박미경', h) or re.search(r'p27[^"]*"[^>]*>박미경', h) else 'check')
if '박미경 선교사' in h:
    print('LABEL_OK')
PY
