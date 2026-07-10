#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import pymysql

c = pymysql.connect(host="127.0.0.1", user="root", password="m2m1234!", database="rmx_db", charset="utf8mb4")
cur = c.cursor()
cur.execute("SELECT mid, browser_title, LEFT(content,80) FROM rx_modules WHERE mid LIKE 'p%' ORDER BY mid")
for r in cur.fetchall():
    print(r)
c.close()
