#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""기도요청(pray, module_srl=126) 권한 및 church_write 설정."""
from __future__ import annotations

import json
import os
import sys

try:
    import pymysql
except ImportError:
    sys.exit(1)

DB = {
    "host": os.environ.get("RMX_DB_HOST", "127.0.0.1"),
    "port": int(os.environ.get("RMX_DB_PORT", "3306")),
    "user": os.environ.get("RMX_DB_USER", "root"),
    "password": os.environ.get("RMX_DB_PASSWORD", "m2m1234!"),
    "charset": "utf8mb4",
}
TARGET = os.environ.get("RMX_TARGET_DB", "rmx_db")

PRAY_MODULE = 126
ADMIN_GROUP = 2
MEMBER_GROUPS = [3, 4]
LOGIN_GROUPS = [ADMIN_GROUP] + MEMBER_GROUPS
ACCESS_GRANTS = ["access", "list", "view", "write_document"]


def main() -> None:
    conn = pymysql.connect(**DB, database=TARGET)
    cur = conn.cursor()

    for grant in ACCESS_GRANTS:
        cur.execute(
            "DELETE FROM rx_module_grants WHERE module_srl=%s AND name=%s",
            (PRAY_MODULE, grant),
        )

    for grant in ACCESS_GRANTS:
        for g in LOGIN_GROUPS:
            cur.execute(
                "INSERT INTO rx_module_grants (module_srl, name, group_srl) VALUES (%s,%s,%s)",
                (PRAY_MODULE, grant, g),
            )
        print(f"pray grant {grant}: groups {LOGIN_GROUPS}")

    config_obj = {"prayer_reader_srls": []}
    cur.execute("SELECT config FROM rx_module_config WHERE module='church_write'")
    row = cur.fetchone()
    if row and row[0]:
        try:
            existing = json.loads(row[0])
            if isinstance(existing, dict):
                existing.setdefault("prayer_reader_srls", [])
                config_obj = existing
        except json.JSONDecodeError:
            pass

    config = json.dumps(config_obj, ensure_ascii=False)
    cur.execute("DELETE FROM rx_module_config WHERE module='church_write'")
    cur.execute(
        "INSERT INTO rx_module_config (module, config) VALUES ('church_write', %s)",
        (config,),
    )
    print("church_write config: prayer_reader_srls=[] (dmcadmin에서 설정)")

    conn.commit()
    conn.close()
    print("Done.")


if __name__ == "__main__":
    main()
