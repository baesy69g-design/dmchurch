#!/usr/bin/env python3
"""Clear Rhymix default mobile welcome widget from index mcontent."""
import os
import pymysql

conn = pymysql.connect(
    host='127.0.0.1', user='root',
    password=os.environ.get('RMX_DB_PASSWORD', 'm2m1234!'),
    database='rmx_db', charset='utf8mb4',
)
cur = conn.cursor()
cur.execute("UPDATE rx_modules SET mcontent='' WHERE mid='index'")
print('affected', cur.rowcount)
conn.commit()
conn.close()
