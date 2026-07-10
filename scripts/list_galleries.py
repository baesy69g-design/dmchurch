#!/usr/bin/env python3
import pymysql
c = pymysql.connect(host="127.0.0.1", user="root", password="m2m1234!", database="rankup_src", charset="utf8mb4")
cur = c.cursor()
cur.execute("SELECT no, name, type, qty FROM rankup_gallery")
for g in cur.fetchall():
    cur.execute("SELECT COUNT(*) FROM rankup_gallery_extend WHERE pno=%s", (g[0],))
    ec = cur.fetchone()[0]
    cur.execute("SELECT COUNT(*) FROM rankup_gallery_webzine WHERE pno=%s", (g[0],))
    wc = cur.fetchone()[0]
    print(g, "ext", ec, "web", wc)
c.close()
