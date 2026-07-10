#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import re
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding="utf-8")

SQL = Path(r"d:\교회사진\교회홈피자료\20260610랭크업백업\dmchurch.kr_260610\db260610.sql")
data = SQL.read_text(encoding="utf-8", errors="replace")

m = re.search(r"INSERT INTO `rankup_frame` VALUES (.+?);\n", data, re.DOTALL)
chunk = m.group(1)
for fid in ("92",):
    for row in re.finditer(r"\((\d+),'([^']*)'", chunk):
        if row.group(1) != fid:
            continue
        start = row.start()
        end = chunk.find("),(", start + 1)
        if end == -1:
            end = len(chunk)
        snippet = chunk[start:end]
        html_m = re.search(r",'<p>.*?</p>',NULL,'yes'", snippet, re.DOTALL)
        html = html_m.group(0)[2:-12] if html_m else ""
        html = html.replace("\\'", "'").replace("\\n", "\n")
        imgs = re.findall(r"se2_\d+\.jpg", html)
        text = re.sub(r"<[^>]+>", "\n", html)
        text = re.sub(r"\n+", "\n", text).strip()
        out = {"title": row.group(2), "html": html, "text": text, "images": imgs}
        print(json.dumps(out, ensure_ascii=False, indent=2))
