#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""dmcadmin.labels.php UTF-8 검증. 배포 전 반드시 통과해야 함."""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LABELS = ROOT / "modules" / "dmcadmin" / "dmcadmin.labels.php"
MODEL = ROOT / "modules" / "dmcadmin" / "dmcadmin.model.php"

CORRUPT = re.compile(r"\?{3,}")


def check(path: Path) -> list[str]:
    text = path.read_bytes().decode("utf-8", errors="strict")
    errors = []
    if path == LABELS and CORRUPT.search(text):
        errors.append(f"{path.name}: contains ???? corruption")
    if path == LABELS and "담임목사" not in text:
        errors.append(f"{path.name}: missing expected Korean text")
    return errors


def main() -> int:
    errors: list[str] = []
    for p in (LABELS,):
        if not p.is_file():
            errors.append(f"missing: {p}")
            continue
        try:
            errors.extend(check(p))
        except UnicodeDecodeError:
            errors.append(f"{p.name}: not valid UTF-8")
    if errors:
        for e in errors:
            print("FAIL:", e, file=sys.stderr)
        return 1
    print("OK: labels encoding verified")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
