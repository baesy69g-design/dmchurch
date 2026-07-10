#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import pymysql

c = pymysql.connect(
    host="127.0.0.1", user="root", password="m2m1234!",
    database="rankup_src", charset="utf8mb4",
)
cur = c.cursor()
cur.execute(
    "SELECT no, base_name, module, component, options, link, url "
    "FROM rankup_frame WHERE module IN ('priest','gallery','schedule','board')"
)
for r in cur.fetchall():
    print(r)
c.close()
