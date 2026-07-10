#!/usr/bin/env python3
"""Patch xedition layout.html to include church main home tiles on index."""
from pathlib import Path

ROOT = Path(__file__).resolve().parent
LAYOUT = ROOT / 'layouts' / 'xedition' / 'layout.html'
INC = '<include target="./church_main_home.inc.html" />'
MARKER = '\t\t<!-- CONTENT -->\n\t\t\t<div class="content" id="content">'
REPLACEMENT = '\t\t<!-- CONTENT -->\n\t\t\t' + INC + '\n\t\t\t<div class="content" id="content">'

if not LAYOUT.is_file():
    raise SystemExit(f'missing layout: {LAYOUT}')

text = LAYOUT.read_text(encoding='utf-8')
if 'church_main_home.inc.html' in text:
    print('already patched')
elif MARKER not in text:
    raise SystemExit('layout marker not found')
else:
    LAYOUT.write_text(text.replace(MARKER, REPLACEMENT, 1), encoding='utf-8')
    print('patched layout.html')

INC_SRC = ROOT / 'patches' / 'layouts' / 'xedition' / 'church_main_home.inc.html'
INC_DST = ROOT / 'layouts' / 'xedition' / 'church_main_home.inc.html'
if INC_SRC.is_file() and not INC_DST.is_file():
    INC_DST.write_text(INC_SRC.read_text(encoding='utf-8'), encoding='utf-8')
    print('copied', INC_DST.name)
elif INC_DST.is_file():
    print('inc already present')
else:
    print('inc missing (upload church_main_home.inc.html manually)')
