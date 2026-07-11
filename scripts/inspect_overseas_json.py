#!/usr/bin/env python3
import json
path = "/root/church-web/html/files/church/overseas_mission.json"
with open(path, encoding="utf-8") as f:
    d = json.load(f)
for i, it in enumerate(d.get("items", [])):
    name = it.get("missionary_name") or it.get("name")
    print(
        f"{i+1}. order={it.get('order')} country={it.get('country')} "
        f"name={name} has_sub={it.get('has_sub')} sub_mid={it.get('sub_mid')} "
        f"photo={bool(it.get('sub_photo') or it.get('thumb'))} "
        f"body_len={len(it.get('sub_body') or '')}"
    )
print("--- heosu ---")
for it in d.get("items", []):
    blob = json.dumps(it, ensure_ascii=False)
    if "허수성" in blob:
        print(json.dumps(it, ensure_ascii=False, indent=2))
