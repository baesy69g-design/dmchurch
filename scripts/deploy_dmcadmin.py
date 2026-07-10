#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""dmcadmin 안전 배포: UTF-8 검증 후 scp + docker 반영."""
from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
HOST = "root@49.247.205.159"
CONTAINER = "church-rhymix"
HTML = "/var/www/vhosts/localhost/html"

FILES = [
    ("modules/dmcadmin/dmcadmin.labels.php", f"{HTML}/modules/dmcadmin/dmcadmin.labels.php"),
    ("modules/dmcadmin/dmcadmin.model.php", f"{HTML}/modules/dmcadmin/dmcadmin.model.php"),
    ("modules/dmcadmin/dmcadmin.view.php", f"{HTML}/modules/dmcadmin/dmcadmin.view.php"),
    ("modules/dmcadmin/dmcadmin.controller.php", f"{HTML}/modules/dmcadmin/dmcadmin.controller.php"),
    (
        "modules/dmcadmin/tpl/domestic_mission_list_edit.html",
        f"{HTML}/modules/dmcadmin/tpl/domestic_mission_list_edit.html",
    ),
    ("addons/church_sub_top/church_sub_top.css", f"{HTML}/addons/church_sub_top/church_sub_top.css"),
]


def run(cmd: list[str], check: bool = True) -> subprocess.CompletedProcess:
    print("+", " ".join(cmd))
    return subprocess.run(cmd, check=check)


def main() -> int:
    verify = ROOT / "scripts" / "verify_dmcadmin_encoding.py"
    r = run([sys.executable, str(verify)])
    if r.returncode != 0:
        return 1

    for local_rel, _remote in FILES:
        local = ROOT / local_rel.replace("/", "\\") if False else ROOT / local_rel
        run(["scp", str(local), f"{HOST}:/tmp/{Path(local_rel).name}"])

    fix_script = ROOT / "scripts" / "fix_domestic_mission_json.php"
    run(["scp", str(fix_script), f"{HOST}:/tmp/fix_domestic_mission_json.php"])

    remote_cmds = []
    for local_rel, remote in FILES:
        name = Path(local_rel).name
        remote_cmds.append(f"docker cp /tmp/{name} {CONTAINER}:{remote}")
    remote_cmds.append(
        f"docker cp /tmp/fix_domestic_mission_json.php {CONTAINER}:{HTML}/scripts/fix_domestic_mission_json.php"
    )
    remote_cmds.append(
        f"docker exec {CONTAINER} php {HTML}/scripts/fix_domestic_mission_json.php"
    )
    remote_cmds.append(
        f"docker exec {CONTAINER} php {HTML}/scripts/publish_domestic_mission.php"
    )
    remote_cmds.append(f"docker exec {CONTAINER} php {HTML}/scripts/clear_cache.php")

    run(["ssh", HOST, " && ".join(remote_cmds)])
    print("deploy done")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
