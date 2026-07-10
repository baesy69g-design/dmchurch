#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import pymysql

c = pymysql.connect(
    host="127.0.0.1", user="root", password="m2m1234!",
    database="rankup_src", charset="utf8mb4",
)
cur = c.cursor()
for no in (154, 155, 147, 84, 25, 110, 26, 146):
    cur.execute(
        "SELECT no, base_name, page_type, module, component, options, page_body_content "
        "FROM rankup_frame WHERE no=%s",
        (no,),
    )
    r = cur.fetchone()
    print(f"=== {r[0]} {r[1]} ===")
    print(f"type={r[2]} module={r[3]} component={r[4]}")
    print(f"options={r[5]}")
    print(f"body={(r[6] or '')[:300]}")
    print()
c.close()
