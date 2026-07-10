#!/usr/bin/env python3
# -*- coding: utf-8 -*-
path = '/root/church-web/html/layouts/xedition/layout.html'
anchor = '<load target="../../addons/church_gnb/church_gnb.js" />'
line = '<load target="../../addons/church_theme/church_tour.js" />'
with open(path, encoding='utf-8') as f:
    text = f.read()
if 'church_theme/church_tour.js' in text:
    print('already present')
elif anchor not in text:
    print('anchor not found')
else:
    text = text.replace(anchor, anchor + '\n' + line, 1)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(text)
    print('layout patched')
