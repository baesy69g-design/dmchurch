#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import pymysql

try:
    from PIL import Image
except ImportError:
    Image = None

BACKUP = "/root/church-web/rankup_backup"
PAGES = [8, 78, 92, 91, 12]

c = pymysql.connect(
    host="127.0.0.1", user="root", password="m2m1234!",
    database="rankup_src", charset="utf8mb4",
)
cur = c.cursor()

def resolve_img(src):
    src = src.split("?")[0]
    if src.startswith("/"):
        return os.path.join(BACKUP, src.lstrip("/"))
    return None

for no in PAGES:
    cur.execute("SELECT base_name, page_body_content FROM rankup_frame WHERE no=%s", (no,))
    name, body = cur.fetchone()
    print(f"\n=== p{no} {name} ===")
    imgs = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', body or "", re.I)
    for src in imgs:
        path = resolve_img(src)
        if path and os.path.isfile(path) and Image:
            im = Image.open(path)
            w, h = im.size
            ratio = w / h if h else 0
            print(f"  {os.path.basename(src)}: {w}x{h} ratio={ratio:.2f}")
        else:
            print(f"  {src}: missing or no PIL")
    # strip scripts for text preview
    text = re.sub(r"<script[^>]*>.*?</script>", "", body or "", flags=re.S | re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    print(f"  text preview: {text[:200]}")

c.close()
