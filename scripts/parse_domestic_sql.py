#!/usr/bin/env python3
import re
from pathlib import Path

sql = Path(r"d:\교회사진\교회홈피자료\20260610랭크업백업\dmchurch.kr_260610\db260610.sql").read_text(encoding="utf-8", errors="replace")

m = re.search(r"INSERT INTO `rankup_gallery_webzine` VALUES (.+?);", sql, re.DOTALL)
if m:
    for part in m.group(1).split("),("):
        if ",9," in part[:30] or part.startswith("(9,") or ",9,'" in part:
            print(part[:500])
            print("---")

m2 = re.search(r"INSERT INTO `rankup_frame` VALUES (.+?);", sql, re.DOTALL)
if m2:
    for part in m2.group(1).split("),("):
        if "국내" in part or "CCC" in part or "강북" in part or "일산" in part or ",25," in part:
            print("FRAME:", part[:400])
            print("---")
