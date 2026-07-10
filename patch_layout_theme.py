#!/usr/bin/env python3
"""xedition layout 패치: church_theme.css 로드."""
from pathlib import Path

ROOT = Path(__file__).resolve().parent
LAYOUT = ROOT / 'layouts' / 'xedition' / 'layout.html'
MARKER = 'church_theme/church_theme.css'
LINE = '<load target="../../addons/church_theme/church_theme.css" />'
ANCHOR = '<load target="./css/layout.css" />'


def main() -> None:
    if not LAYOUT.is_file():
        print('layout not found:', LAYOUT)
        return
    text = LAYOUT.read_text(encoding='utf-8')
    if MARKER in text:
        print('church_theme css already present')
        return
    if ANCHOR not in text:
        print('anchor not found')
        return
    text = text.replace(ANCHOR, ANCHOR + '\n' + LINE, 1)
    LAYOUT.write_text(text, encoding='utf-8')
    print('layout.html updated')


if __name__ == '__main__':
    main()
