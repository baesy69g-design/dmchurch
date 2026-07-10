#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""구홈피 rankup_gallery_webzine(pno=10) → 해외선교 시드 추출."""
import json
import re
from pathlib import Path

SQL = Path(__file__).resolve().parents[2].parent / "dmchurch.kr_260610" / "db260610.sql"
if not SQL.is_file():
    SQL = Path(r"d:\교회사진\교회홈피자료\20260610랭크업백업\dmchurch.kr_260610\db260610.sql")

data = SQL.read_text(encoding="utf-8", errors="replace")
m = re.search(r"INSERT INTO `rankup_gallery_webzine` VALUES (.+?);\n", data, re.DOTALL)
if not m:
    raise SystemExit("gallery_webzine not found")

chunk = m.group(1)
rows = re.findall(
    r"\((\d+),(\d+),'((?:[^'\\]|\\.)*)','((?:[^'\\]|\\.)*)','((?:[^'\\]|\\.)*)'\)",
    chunk,
)

items = []
for no, pno, title, body, img in rows:
    if pno != "10":
        continue
    title = title.replace("\\'", "'").replace("\\n", "\n")
    body = body.replace("\\'", "'").replace("\\r\\n", "\n").replace("\\n", "\n")
    items.append({"no": int(no), "title": title, "body": body, "img": img})

print(json.dumps(items, ensure_ascii=False, indent=2))
print(f"\n# count: {len(items)}", flush=True)
