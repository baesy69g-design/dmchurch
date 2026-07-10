#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import pymysql

c = pymysql.connect(
    host="127.0.0.1",
    user="root",
    password="m2m1234!",
    database="rankup_src",
    charset="utf8mb4",
)
cur = c.cursor()
cur.execute("SHOW COLUMNS FROM rankup_frame")
print("COLUMNS:", [r[0] for r in cur.fetchall()])
cur.execute("SELECT COUNT(*) FROM rankup_frame")
print("COUNT:", cur.fetchone()[0])
for no in (8, 9, 78, 12, 25, 79, 108):
    cur.execute(
        "SELECT no, base_name, page_type, page_body_content, page_top_content "
        "FROM rankup_frame WHERE no=%s",
        (no,),
    )
    row = cur.fetchone()
    if row:
        print(f"\n=== frame {row[0]}: {row[1]} type={row[2]} ===")
        print("TOP:", (row[4] or "")[:300])
        print("BODY:", (row[3] or "")[:1200])
c.close()
