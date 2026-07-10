#!/usr/bin/env python3
import pymysql
import os
conn = pymysql.connect(host='127.0.0.1', user='root', password=os.environ.get('RMX_DB_PASSWORD', 'm2m1234!'), database='rmx_db', charset='utf8mb4')
cur = conn.cursor()
cur.execute("SELECT mid, layout_srl, mlayout_srl, use_mobile, module, LEFT(content,80), LEFT(mcontent,80) FROM rx_modules WHERE mid='index'")
print(cur.fetchone())
cur.execute("SELECT layout_srl, title, path FROM rx_layouts")
for r in cur.fetchall():
    print(r)
conn.close()
