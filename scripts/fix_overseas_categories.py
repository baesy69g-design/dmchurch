#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import re

p = Path(__file__).resolve().parents[1] / "modules" / "dmcadmin" / "dmcadmin.model.php"
t = p.read_text(encoding="utf-8", errors="replace")
new_block = """\tpublic const OVERSEAS_MISSION_CATEGORIES = [
\t\t'dispatch' => '우리교회 파송선교사',
\t\t'support' => '우리교회가 돕는 선교지',
\t];"""
t = re.sub(
    r"\tpublic const OVERSEAS_MISSION_CATEGORIES = \[[\s\S]*?\];",
    new_block,
    t,
    count=1,
)
p.write_text(t, encoding="utf-8")
print("fixed")
