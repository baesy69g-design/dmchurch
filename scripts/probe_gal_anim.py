#!/usr/bin/env python3
import urllib.request, re
html = urllib.request.urlopen("http://127.0.0.1:8000/p265", timeout=20).read().decode("utf-8", "replace")
print("gal items", html.count("church-mission-gal-item"))
for m in re.finditer(r'href="([^"]*church_theme[^"]*)"', html):
    print("css", m.group(1)[:160])
for m in re.finditer(r'href="([^"]*compiled[^"]*\.css[^"]*)"', html):
    print("compiled", m.group(1)[:160])
print("float in page?", "church-mission-gal-float" in html)
