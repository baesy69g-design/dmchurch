#!/usr/bin/env python3
import urllib.request, re
html = urllib.request.urlopen("http://127.0.0.1:8000/", timeout=20).read().decode("utf-8", "replace")
i = html.find("church-main-pastor")
print("idx", i)
print(html[max(0,i-300):i+900] if i >= 0 else "missing")
# find compiled css refs
for m in re.finditer(r'church_main_tiles[^"\']*', html):
    print("asset", m.group(0)[:120])
