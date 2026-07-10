#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import re
import pymysql

PAGES = [
    8, 9, 79, 154, 155, 78, 12, 108, 147, 84, 25, 26, 91, 92, 146,
    109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120,
]

c = pymysql.connect(
    host="127.0.0.1", user="root", password="m2m1234!",
    database="rankup_src", charset="utf8mb4",
)
cur = c.cursor()
for no in PAGES:
    cur.execute(
        "SELECT no, base_name, page_type, page_body_content FROM rankup_frame WHERE no=%s",
        (no,),
    )
    row = cur.fetchone()
    if not row:
        print(f"{no}: MISSING")
        continue
    body = row[3] or ""
    imgs = re.findall(r'src=["\']([^"\']+)["\']', body, re.I)
    has_table = "<table" in body.lower()
    text_len = len(re.sub(r"<[^>]+>", "", body).strip())
    print(
        f"p{no:3d} {row[2]:8s} imgs={len(imgs)} table={has_table} text={text_len:4d} | {row[1]}"
    )
    if imgs:
        print("  ", imgs[:3])
c.close()
