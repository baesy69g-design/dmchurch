#!/usr/bin/env python3
"""xedition layout 패치: XEDITION 기본 footer → 동명교회 footer."""
import re
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parent
LAYOUT = ROOT / 'layouts' / 'xedition' / 'layout.html'
INC_SRC = ROOT / 'patches' / 'layouts' / 'xedition' / 'church_footer.inc.html'
INC_DST = ROOT / 'layouts' / 'xedition' / 'church_footer.inc.html'
FOOTER_MARKER = 'church_footer.inc.html'
CSS_MARKER = 'church_footer/church_footer.css'
CSS_LINE = '<load target="../../addons/church_footer/church_footer.css" />\n'
CSS_ANCHOR = '<load target="./css/layout.css" />'


def main() -> None:
    if INC_SRC.is_file():
        INC_DST.write_text(INC_SRC.read_text(encoding='utf-8'), encoding='utf-8')
        print('copied', INC_DST.name)

    if not LAYOUT.is_file():
        print('layout not found:', LAYOUT)
        return

    text = LAYOUT.read_text(encoding='utf-8')
    changed = False

    if CSS_MARKER not in text and CSS_ANCHOR in text:
        text = text.replace(CSS_ANCHOR, CSS_ANCHOR + '\n' + CSS_LINE.rstrip(), 1)
        changed = True
        print('added church footer css load')

    if FOOTER_MARKER not in text:
        new_text, n = re.subn(
            r'\t<footer class="footer"[^>]*>.*?\t</footer>',
            '\t<include target="./church_footer.inc.html" />',
            text,
            count=1,
            flags=re.DOTALL,
        )
        if n:
            text = new_text
            changed = True
            print('replaced xedition footer with church footer include')
        else:
            print('footer block not found')
    else:
        print('footer include already present')

    if changed:
        LAYOUT.write_text(text, encoding='utf-8')
        print('layout.html updated')
    else:
        print('no layout changes')


if __name__ == '__main__':
    main()
