#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path

p = Path(__file__).resolve().parents[1] / "modules" / "dmcadmin" / "dmcadmin.model.php"
text = p.read_text(encoding="utf-8")

replacements = [
    (
        "\t\t\t$h .= '<div class=\"cs-theme\"><span class=\"cs-theme-label\">????</span>'",
        "\t\t\t$h .= '<div class=\"cs-theme\"><span class=\"cs-theme-label\">교육주제</span>'",
    ),
    (
        ". ' ?? ' . ($i + 1)",
        ". ' 사진 ' . ($i + 1)",
    ),
    (
        "['goal', '???? ? ??',",
        "['goal', '교육목표 및 방향',",
    ),
    (
        "['worship', '?? ??',",
        "['worship', '예배 안내',",
    ),
    (
        "['staff', '?? ??? ? ??',",
        "['staff', '담당 교역자 및 교사',",
    ),
    ("'p109' => '???',", "'p109' => '유치부',"),
    ("'p112' => '???',", "'p112' => '아동부',"),
    ("'p115' => '????',", "'p115' => '청소년부',"),
    ("'p118' => '???',", "'p118' => '청년부',"),
    ("?? ' ??'", "?? ' 소개'"),
]

for old, new in replacements:
    if old not in text:
        print("MISSING:", old[:70])
    else:
        text = text.replace(old, new)
        print("OK:", new[:40])

p.write_text(text, encoding="utf-8", newline="\n")
print("written:", p)
