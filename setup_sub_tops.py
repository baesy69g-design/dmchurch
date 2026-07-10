#!/usr/bin/env python3
"""Import legacy sub-page TOP banners and activate church_sub_top addon."""
import os
import shutil
import pymysql
from datetime import datetime

LEGACY = {
    'info': 'top.15014945664036.jpg',
    'news': 'top.15014945609550.jpg',
    'mission': 'top.15014945691236.jpg',
    'school': 'top.15022460688328.jpg',
    'broadcast': 'top.15022460663461.jpg',
    'community': 'top.15014945708645.jpg',
}

SRC_DIRS = [
    '/root/church-web/rankup_backup/design/page',
    '/var/www/vhosts/localhost/html/../../rankup_backup/design/page',
]
DEST_DIR = '/root/church-web/html/files/church/sub_top'
LEGACY_DIR = '/root/church-web/html/files/church/sub_top_legacy'

conn = pymysql.connect(
    host='127.0.0.1', user='root',
    password=os.environ.get('RMX_DB_PASSWORD', 'm2m1234!'),
    database='rmx_db', charset='utf8mb4',
)
cur = conn.cursor()

os.makedirs(DEST_DIR, exist_ok=True)
os.makedirs(LEGACY_DIR, exist_ok=True)

for filename in LEGACY.values():
    for d in SRC_DIRS:
        p = os.path.join(d, filename)
        if os.path.isfile(p):
            legacy_copy = os.path.join(LEGACY_DIR, filename)
            if not os.path.isfile(legacy_copy):
                shutil.copy2(p, legacy_copy)
                os.chmod(legacy_copy, 0o644)
            break

cur.execute(
    "SELECT config FROM rx_module_config WHERE module='church_write' AND site_srl=0"
)
row = cur.fetchone()
import json
config = json.loads(row[0]) if row and row[0] else {}
urls = config.get('sub_top_banner_urls') or {}
if isinstance(urls, str):
    urls = json.loads(urls)

imported = []
for key, filename in LEGACY.items():
    if urls.get(key):
        continue
    src = None
    for d in SRC_DIRS:
        p = os.path.join(d, filename)
        if os.path.isfile(p):
            src = p
            break
    if not src:
        print('missing', key, filename)
        continue
    ext = os.path.splitext(filename)[1].lstrip('.') or 'jpg'
    dest = os.path.join(DEST_DIR, f'{key}.{ext}')
    shutil.copy2(src, dest)
    os.chmod(dest, 0o644)
    urls[key] = f'./files/church/sub_top/{key}.{ext}'
    imported.append(key)
    print('imported', key, 'from', src)

if imported:
    config['sub_top_banner_urls'] = urls
    payload = json.dumps(config, ensure_ascii=False)
    if row:
        cur.execute(
            "UPDATE rx_module_config SET config=%s WHERE module='church_write' AND site_srl=0",
            (payload,),
        )
    else:
        cur.execute(
            "INSERT INTO rx_module_config (site_srl, module, config) VALUES (0,'church_write',%s)",
            (payload,),
        )
    conn.commit()
    print('saved config for', imported)
else:
    print('nothing to import (already set or files missing)')

now = datetime.now().strftime('%Y%m%d%H%M%S')
addon = 'church_sub_top'
for table, _ in [('rx_addons', ''), ('rx_addons_site', '')]:
    cur.execute(f'DELETE FROM {table} WHERE addon=%s', (addon,))
    if table == 'rx_addons_site':
        cur.execute(
            "INSERT INTO rx_addons_site (site_srl, addon, is_used, is_used_m, extra_vars, regdate) VALUES (0,%s,'Y','Y','',%s)",
            (addon, now),
        )
    else:
        cur.execute(
            "INSERT INTO rx_addons (addon, is_used, is_used_m, is_fixed, extra_vars, regdate) VALUES (%s,'Y','Y','N','',%s)",
            (addon, now),
        )
conn.commit()
print('activated', addon)
conn.close()
