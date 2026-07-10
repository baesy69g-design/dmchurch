#!/usr/bin/env python3
import os, pymysql
conn = pymysql.connect(host='127.0.0.1', user='root', password=os.environ.get('RMX_DB_PASSWORD', 'm2m1234!'), database='rmx_db', charset='utf8mb4')
cur = conn.cursor()
cur.execute("SELECT mcontent FROM rx_modules WHERE mid='index'")
m = cur.fetchone()[0]
print('mcontent_len', len(m or ''))
print((m or '')[:500])
cur.execute("UPDATE rx_modules SET mcontent='' WHERE mid='index'")
print('affected', cur.rowcount)
conn.commit()
cur.execute("SELECT LENGTH(mcontent) FROM rx_modules WHERE mid='index'")
print('after_len', cur.fetchone()[0])
conn.close()
