#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Apply board write grants for church site (run on VPS via docker python)."""
from __future__ import annotations

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

ADMIN_BOARDS = [110, 114, 116, 118, 120, 122, 124]
PUBLIC_BOARDS = [112, 126]  # community, pray(기도요청)
ADMIN_GROUP = 2
MEMBER_GROUPS = [3, 4]


def main() -> None:
    conn = pymysql.connect(**DB, database=TARGET)
    cur = conn.cursor()

    for mod in ADMIN_BOARDS + PUBLIC_BOARDS:
        cur.execute(
            "DELETE FROM rx_module_grants WHERE module_srl=%s AND name='write_document'",
            (mod,),
        )

    for mod in ADMIN_BOARDS:
        cur.execute(
            "INSERT INTO rx_module_grants (module_srl, name, group_srl) VALUES (%s,'write_document',%s)",
            (mod, ADMIN_GROUP),
        )
        print(f"admin-only write: module {mod}")

    for mod in PUBLIC_BOARDS:
        for g in [ADMIN_GROUP] + MEMBER_GROUPS:
            cur.execute(
                "INSERT INTO rx_module_grants (module_srl, name, group_srl) VALUES (%s,'write_document',%s)",
                (mod, g),
            )
        print(f"member write: module {mod}")

    conn.commit()
    conn.close()
    print("Done.")


if __name__ == "__main__":
    main()
