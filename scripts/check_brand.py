#!/usr/bin/env python3
import urllib.request
html = urllib.request.urlopen("http://127.0.0.1:8000/", timeout=20).read().decode("utf-8", "replace")
for key in ["church-brand", "church-brand-sub-ch", "setLogo", "대한예수", "logo_mark"]:
    print(key, html.count(key))
idx = html.find("setLogo")
print("snippet", html[idx:idx+500] if idx >= 0 else "no setLogo")
idx2 = html.find("church-brand")
print("brand", html[idx2:idx2+400] if idx2 >= 0 else "no brand")
