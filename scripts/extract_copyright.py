#!/usr/bin/env python3
import re
import sys

path = sys.argv[1] if len(sys.argv) > 1 else '/root/church-web/rankup_backup/db260610.sql'
text = open(path, 'r', errors='ignore').read()

keywords = ('동명', '교회', 'Copyright', '02-', '주소', '사업자', '2241', 'dmchurch', '장로회')

for m in re.finditer(r"copyright','((?:\\.|[^'\\])*)'", text):
    val = bytes(m.group(1), 'utf-8').decode('unicode_escape')
    print('--- copyright field ---')
    print(val[:4000])

for needle in ('답십리로 136', 'DongMyeong Presbyterian', 'Copyright 1972'):
    idx = text.find(needle)
    if idx >= 0:
        print(f'--- around {needle} ---')
        chunk = text[max(0, idx - 400):idx + 500]
        print(chunk)
