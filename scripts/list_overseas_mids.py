#!/usr/bin/env python3
import json, glob, os
paths = glob.glob("/root/church-web/html/files/**/*overseas*.json", recursive=True)
paths += glob.glob("/root/church-web/html/files/dmcadmin/**/*.json", recursive=True)
print("paths", paths[:20])
for p in paths:
    try:
        data = json.load(open(p, encoding="utf-8"))
    except Exception as e:
        print(p, e)
        continue
    items = data.get("items") if isinstance(data, dict) else None
    if not items:
        continue
    print("FILE", p)
    for it in items:
        if it.get("has_sub"):
            print(" ", it.get("sub_mid"), it.get("missionary_name") or it.get("name"), it.get("country"))
