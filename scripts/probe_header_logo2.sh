#!/bin/bash
set -euo pipefail
echo '==== home head ===='
head -c 5000 /tmp/home.html
echo
echo '==== mobile layout files ===='
docker exec church-rhymix ls -la /var/www/vhosts/localhost/html/m.layouts/default/
echo '==== hd styles in css ===='
docker exec church-rhymix sh -c 'grep -n "\.hd\|background" /var/www/vhosts/localhost/html/m.layouts/default/*.css 2>/dev/null | head -80'
echo '==== church_sub_top hd ===='
docker exec church-rhymix sh -c 'grep -n "xe-mobilelayout\|\.hd\|max-width: 900" /var/www/vhosts/localhost/html/addons/church_sub_top/church_sub_top.css | head -40'
echo '==== css linked in home ===='
grep -oE 'href="[^"]+\.css[^"]*"' /tmp/home.html | head -40
echo '==== larger logos ===='
python3 - <<'PY'
from pathlib import Path
import struct
files=[
'/root/church-web/rankup_backup/RAD/PEG/logo_13607609169879.jpg',
'/root/church-web/rankup_backup/m/design/top/logo.13189141347252.jpg',
'/root/church-web/rankup_backup/RAD/PEG/logo_13462932789322.jpg',
'/root/church-web/rankup_backup/m/design/top/logo.15289440155959.jpg',
'/root/church-web/html/files/church/logo.jpg',
'/root/church-web/rankup_backup/design/skin/img/top_logo.png',
'/root/church-web/rankup_backup/design/skin/img/t_logo2.png',
]
for f in files:
    p=Path(f)
    if not p.exists():
        print('MISSING', f); continue
    data=p.read_bytes()[:64]
    w=h=None
    if data[:3]==b'\xff\xd8\xff':
        # crude jpeg sof scan
        i=2
        b=p.read_bytes()
        while i < len(b)-9:
            if b[i]!=0xff:
                i+=1; continue
            marker=b[i+1]
            if marker in (0xc0,0xc1,0xc2):
                h=int.from_bytes(b[i+5:i+7],'big')
                w=int.from_bytes(b[i+7:i+9],'big')
                break
            if marker==0xd9 or marker==0xda:
                break
            if marker==0x01 or (0xd0<=marker<=0xd9):
                i+=2; continue
            seglen=int.from_bytes(b[i+2:i+4],'big')
            i+=2+seglen
    elif data[:8]==b'\x89PNG\r\n\x1a\n':
        w=int.from_bytes(data[16:20],'big'); h=int.from_bytes(data[20:24],'big')
    print(f'{p.stat().st_size:6d} {w}x{h} {f}')
PY
echo '==== mobile layout html logo ===='
docker exec church-rhymix cat /var/www/vhosts/localhost/html/m.layouts/default/layout.html | head -80
