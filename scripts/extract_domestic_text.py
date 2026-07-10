#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""구홈피 rankup_gallery_webzine(pno=9) → domestic_mission.json 텍스트 추출."""
import json
import re
from pathlib import Path

SQL = Path(__file__).resolve().parents[3] / "dmchurch.kr_260610" / "db260610.sql"
OUT = Path(__file__).resolve().parent / "domestic_mission_seed_text.json"

sql = SQL.read_text(encoding="utf-8", errors="replace")
entries = {}
for m in re.finditer(r"\((\d+),9,'((?:[^'\\]|\\.)*)','((?:[^'\\]|\\.)*)','([^']+)'\)", sql):
    no, title, body, img = m.groups()
    title = title.encode("utf-8").decode("unicode_escape") if "\\" in title else title
    body = body.replace("\\n", "\n")
    entries[int(no)] = {"title": title, "body": body, "image": img}

# unescape simple
for v in entries.values():
    v["title"] = bytes(v["title"], "utf-8").decode("unicode_escape") if "\\u" in v["title"] else v["title"]

OUT.write_text(json.dumps(entries, ensure_ascii=False, indent=2), encoding="utf-8")
print("written", OUT)
for k in sorted(entries):
    print(k, entries[k]["title"][:40])
