#!/usr/bin/env python3
"""xedition layout 패치: 구홈피 가로 GNB 서브메뉴 + 배경 CSS/JS."""
from pathlib import Path

ROOT = Path(__file__).resolve().parent
LAYOUT = ROOT / 'layouts' / 'xedition' / 'layout.html'
CSS_MARKER = 'church_gnb/church_gnb.css'
CSS_LINE = '<load target="../../addons/church_gnb/church_gnb.css" />'
CSS_ANCHOR = 'church_sub_top/church_sub_top.css'
JS_MARKER = 'church_gnb/church_gnb.js'
JS_LINE = '<load target="../../addons/church_gnb/church_gnb.js" />'
JS_ANCHOR = '<load target="./js/layout.js" />'


def main() -> None:
    if not LAYOUT.is_file():
        print('layout not found:', LAYOUT)
        return
    text = LAYOUT.read_text(encoding='utf-8')
    changed = False

    if CSS_MARKER not in text:
        if CSS_ANCHOR not in text:
            print('css anchor not found')
        else:
            text = text.replace(
                f'<load target="../../addons/{CSS_ANCHOR}" />',
                f'<load target="../../addons/{CSS_ANCHOR}" />\n{CSS_LINE}',
                1,
            )
            changed = True
            print('added church_gnb css load')
    else:
        print('church_gnb css already present')

    if JS_MARKER not in text:
        if JS_ANCHOR not in text:
            print('js anchor not found')
        else:
            text = text.replace(JS_ANCHOR, JS_ANCHOR + '\n' + JS_LINE, 1)
            changed = True
            print('added church_gnb js load')
    else:
        print('church_gnb js already present')

    if changed:
        LAYOUT.write_text(text, encoding='utf-8')
        print('layout.html updated')
    else:
        print('no layout changes')


if __name__ == '__main__':
    main()
