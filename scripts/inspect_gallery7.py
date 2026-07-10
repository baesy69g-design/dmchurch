#!/usr/bin/env python3
import pymysql
c = pymysql.connect(host="127.0.0.1", user="root", password="m2m1234!", database="rankup_src", charset="utf8mb4")
cur = c.cursor()
cur.execute("SELECT settings FROM rankup_gallery WHERE no=7")
row = cur.fetchone()
print(row[0][:2000] if row and row[0] else "empty")
c.close()
