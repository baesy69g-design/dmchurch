#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import pymysql

c = pymysql.connect(host="127.0.0.1", user="root", password="m2m1234!", database="rankup_src", charset="utf8mb4")
cur = c.cursor()
for gid in (7, 9, 10):
    cur.execute("SELECT no, name, type FROM rankup_gallery WHERE no=%s", (gid,))
    print("gallery", cur.fetchone())
    cur.execute("SELECT COUNT(*) FROM rankup_gallery_extend WHERE pno=%s", (gid,))
    print("  extend rows:", cur.fetchone()[0])
    cur.execute("SELECT no, attach, LEFT(content,100) FROM rankup_gallery_extend WHERE pno=%s LIMIT 3", (gid,))
    for r in cur.fetchall():
        print(" ", r)
    cur.execute("SELECT COUNT(*) FROM rankup_gallery_webzine WHERE pno=%s", (gid,))
    print("  webzine rows:", cur.fetchone()[0])
    cur.execute("SELECT no, title, LEFT(content,100) FROM rankup_gallery_webzine WHERE pno=%s LIMIT 2", (gid,))
    for r in cur.fetchall():
        print(" ", r)
c.close()
