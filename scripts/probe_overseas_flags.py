#!/usr/bin/env python3
import urllib.request, re
html = urllib.request.urlopen("http://127.0.0.1:8000/p26", timeout=20).read().decode("utf-8", "replace")
print("name-flag count", html.count("church-dm-name-flag"))
print("name with link count", len(re.findall(r'church-dm-item-name-row', html)))
for m in re.finditer(r'church-dm-item-name-row.*?>(.*?)</span></strong>', html, re.S):
    chunk = re.sub(r'<[^>]+>', ' ', m.group(1))
    print(" ", chunk.strip()[:60])
