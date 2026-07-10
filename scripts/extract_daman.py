#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import re
from pathlib import Path

path = Path(__file__).resolve().parents[2] / "dmchurch.kr_260610" / "db260610.sql"
data = path.read_text(encoding="utf-8", errors="replace")

idx = data.find("(91,'특수선교'")
if idx >= 0:
    chunk = data[idx : idx + 4000]
    img = re.search(r"se2_(\d+)\.jpg", chunk)
    if img:
        print("frame91_img", f"se2_{img.group(1)}.jpg")
    para = re.search(r"</p><p>([^<]+)</p>", chunk)
    if para:
        print("frame91_text", para.group(1)[:1500])

m = re.search(r"\(125,11,'담안선교회','(.*?)','(gw\.[^']+)'\)", data, re.DOTALL)
if m:
    print("gallery_img", m.group(2))
    print("gallery_text", m.group(1))
