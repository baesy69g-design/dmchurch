#!/usr/bin/env python3
import pymysql
c = pymysql.connect(host="127.0.0.1", user="root", password="m2m1234!", database="rmx_db", charset="utf8mb4")
cur = c.cursor()
for mid in ("p8", "p78", "p154", "p25"):
    cur.execute("SELECT browser_title, LEFT(content, 600) FROM rx_modules WHERE mid=%s", (mid,))
    title, content = cur.fetchone()
    print(f"=== {mid} {title} ===")
    print(content[:600])
    print()
c.close()
