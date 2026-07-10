#!/usr/bin/env python3
"""xedition layout: 서브 TOP 배너 — Rhymix 템플릿 호환 패치."""
from pathlib import Path
import re

LAYOUT = Path("/root/church-web/html/layouts/xedition/layout.html")

# Remove any broken prior patches between xeicon block and mask span
START = "\t\t<block cond=\"$mid === 'xeicon'\">{@ $_subheader_img = 'sub_banner_xeicon.jpg'}</block>\n"
END = "\t\t<span class=\"mask\"></span>\n"

NEW_MIDDLE = """\t\t{@ getModel('dmcadmin')}
\t\t{@ $church_sub_top_banner_url = dmcadminModel::getSubTopBannerUrlForLayout($mid)}
\t\t<block cond=\"$church_sub_top_banner_url\">
\t\t<span class=\"bg_img\" style=\"background-image:url('{$church_sub_top_banner_url}')\"></span>
\t\t</block>
\t\t<block cond=\"!$church_sub_top_banner_url\">
\t\t<span class=\"bg_img\" style=\"background-image:url('{$layout_info->path}img/{$_subheader_img}')\"></span>
\t\t</block>
"""


def main() -> None:
    if not LAYOUT.is_file():
        print("layout not found")
        return
    text = LAYOUT.read_text(encoding="utf-8")
    i0 = text.find(START)
    i1 = text.find(END)
    if i0 < 0 or i1 < 0 or i1 <= i0:
        print("anchors not found", i0, i1)
        return
    i0 += len(START)
    new_text = text[:i0] + NEW_MIDDLE + text[i1:]
    LAYOUT.write_text(new_text, encoding="utf-8")
    print("layout repaired")


if __name__ == "__main__":
    main()
