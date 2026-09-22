#!/bin/bash
docker exec church-mariadb mariadb -urmx_user -prmx!!4321 rmx_db -e "
SELECT menu_item_srl, parent_srl, name, url, listorder
FROM rx_menu_item
WHERE url IN ('p26','p27')
   OR parent_srl = (SELECT menu_item_srl FROM rx_menu_item WHERE url='p26' ORDER BY menu_item_srl LIMIT 1)
ORDER BY listorder, menu_item_srl;
SELECT mid, browser_title, layout_srl, menu_srl FROM rx_modules WHERE mid IN ('p26','p27','p265');
"
echo '=== lnb on p27 ==='
curl -sL 'http://127.0.0.1:8000/index.php?mid=p27' > /tmp/p27.html
grep -c 'class="lnb"' /tmp/p27.html || true
grep -oE '박미경|파송선교|p27|해외선교' /tmp/p27.html | sort | uniq -c | head
echo '=== lnb on p265 ==='
curl -sL 'http://127.0.0.1:8000/index.php?mid=p265' > /tmp/p265.html
grep -c 'class="lnb"' /tmp/p265.html || true
python3 - <<'PY'
from pathlib import Path
h=Path('/tmp/p265.html').read_text(encoding='utf-8',errors='ignore')
i=h.find('class="lnb"')
print(h[i:i+1500] if i>=0 else 'NO LNB')
print('---p27---')
h2=Path('/tmp/p27.html').read_text(encoding='utf-8',errors='ignore')
i2=h2.find('class="lnb"')
print(h2[i2:i2+1500] if i2>=0 else 'NO LNB')
PY
