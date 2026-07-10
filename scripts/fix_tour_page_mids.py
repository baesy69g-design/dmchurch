#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import re

p = Path(__file__).resolve().parents[1] / "modules" / "dmcadmin" / "dmcadmin.model.php"
t = p.read_text(encoding="utf-8", errors="replace")
new_block = """\tpublic const TOUR_PAGE_MIDS = [
\t\t'p147' => '교회둘러보기',
\t\t'p92' => '사랑의 쌀나누기',
\t\t'p146' => '장학사업',
\t];"""
t = re.sub(
    r"\tpublic const TOUR_PAGE_MIDS = \[[\s\S]*?\];",
    new_block,
    t,
    count=1,
)
p.write_text(t, encoding="utf-8")
print("fixed")
