#!/usr/bin/env python3
"""Activate church addons (church_board_ui, church_main_slide)."""
import os
import pymysql
from datetime import datetime

conn = pymysql.connect(
    host='127.0.0.1', user='root',
    password=os.environ.get('RMX_DB_PASSWORD', 'm2m1234!'),
    database='rmx_db', charset='utf8mb4',
)
cur = conn.cursor()
now = datetime.now().strftime('%Y%m%d%H%M%S')

for addon in ['church_board_ui', 'church_main_slide', 'church_sub_top', 'church_main_tiles']:
    for table, extra in [('rx_addons', ''), ('rx_addons_site', ', site_srl=0')]:
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
    print('activated', addon)

conn.commit()
conn.close()
