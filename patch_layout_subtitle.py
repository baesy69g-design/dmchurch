#!/usr/bin/env python3
"""xedition layout 패치: 교회 GNB CSS + 서브페이지 제목."""
from pathlib import Path

LAYOUT = Path("/root/church-web/html/layouts/xedition/layout.html")
CSS_MARKER = "church_sub_top/church_sub_top.css"
CSS_LINE = '<load target="../../addons/church_sub_top/church_sub_top.css" />\n'
CSS_ANCHOR = '<load target="./css/layout.css" />'
TITLE_MARKER = "church_sub_title.inc.html"
TITLE_INCLUDE = '\t\t<include target="./church_sub_title.inc.html" />\n'
TITLE_ANCHOR = "\t<!-- END:HEADER -->"


def main() -> None:
    if not LAYOUT.is_file():
        print("layout not found:", LAYOUT)
        return
    text = LAYOUT.read_text(encoding="utf-8")
    changed = False

    if CSS_MARKER not in text:
        if CSS_ANCHOR not in text:
            print("css anchor not found")
        else:
            text = text.replace(CSS_ANCHOR, CSS_ANCHOR + "\n" + CSS_LINE.rstrip(), 1)
            changed = True
            print("added church gnb css load")
    else:
        print("css load already present")

    if TITLE_MARKER not in text:
        if TITLE_ANCHOR not in text:
            print("title anchor not found")
        else:
            text = text.replace(TITLE_ANCHOR, TITLE_INCLUDE + TITLE_ANCHOR, 1)
            changed = True
            print("added church sub title include")
    else:
        print("title include already present")

    if changed:
        LAYOUT.write_text(text, encoding="utf-8")
        print("layout.html updated")
    else:
        print("no changes")


if __name__ == "__main__":
    main()
