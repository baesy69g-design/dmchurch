#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import re
from pathlib import Path

MODEL = Path(__file__).resolve().parents[1] / "modules" / "dmcadmin" / "dmcadmin.model.php"
text = MODEL.read_bytes().decode("utf-8", errors="replace")
new_block = """\tpublic const DOMESTIC_MISSION_CATEGORIES = [
\t\t'church' => '우리가 돕는 교회',
\t\t'org' => '우리가 돕는 기관',
\t];"""
text, n = re.subn(
    r"\tpublic const DOMESTIC_MISSION_CATEGORIES = \[\s*\n\t\t'church' => '[^']*',\s*\n\t\t'org' => '[^']*',\s*\n\t\];",
    new_block,
    text,
    count=1,
)
if n != 1:
    raise SystemExit(f"replace failed: {n}")
MODEL.write_text(text, encoding="utf-8", newline="\n")
print("OK")
