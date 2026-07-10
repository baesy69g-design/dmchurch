#!/usr/bin/env python3
import pymysql
c = pymysql.connect(host="127.0.0.1", user="root", password="m2m1234!", database="rankup_src", charset="utf8mb4")
cur = c.cursor()
cur.execute("SHOW COLUMNS FROM rankup_gallery_webzine")
print([r[0] for r in cur.fetchall()])
cur.execute("SELECT * FROM rankup_gallery_webzine WHERE pno=9 LIMIT 1")
cols = [d[0] for d in cur.description]
row = cur.fetchone()
for k, v in zip(cols, row):
    print(k, ":", (str(v)[:200] if v else v))
c.close()
