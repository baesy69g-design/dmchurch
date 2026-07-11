#!/usr/bin/env python3
import re
import sys
import urllib.request

mid = sys.argv[1] if len(sys.argv) > 1 else "p94"
url = f"http://127.0.0.1:8000/index.php?mid={mid}"
html = urllib.request.urlopen(url, timeout=20).read().decode("utf-8", "replace")
idx = html.find('class="lnb"')
if idx < 0:
    idx = html.find("class='lnb'")
print("mid", mid, "lnb idx", idx)
chunk = html[idx : idx + 5000] if idx >= 0 else ""
# compact classes on li
for m in re.finditer(r"<li([^>]*)>(?:\s*<a[^>]*>)([^<]+)", chunk):
    attrs, text = m.group(1), m.group(2).strip()
    cls = ""
    cm = re.search(r'class="([^"]*)"', attrs)
    if cm:
        cls = cm.group(1)
    print(f"LI class=[{cls}] text=[{text}]")
print("--- raw snippet ---")
print(chunk[:2500])
