#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""구홈피 rankup_frame 기준 Rhymix Main Menu(48) 구성."""
from __future__ import annotations

import os
import sys
from datetime import datetime

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
MAIN_MENU_SRL = 48
REGDATE = datetime.now().strftime("%Y%m%d%H%M%S")

BOARD_MID = {
    "board1": "jubo",
    "board2": "newface",
    "board12": "pray",
    "board13": "picture",
    "freeboard02": "community",
}

BROADCAST_MID = {
    22: "sermon",
    59: "choir",
    57: "peniel",
    23: "eventvideo",
}

STUB_HTML = (
    '<div class="church-page-stub" style="padding:24px;line-height:1.7">'
    "<h2>{title}</h2>"
    "<p>콘텐츠 준비 중입니다. (구홈피 정보 페이지 이관 예정)</p>"
    "</div>"
)

# GNB 순서: 교회안내 → 교회소식 → 선교와봉사 → 교회학교 → 교회방송 → 커뮤니티
# type: folder | mid | page | board | broadcast
MENU_TREE = [
    {
        "name": "홈",
        "mid": "index",
    },
    {
        "name": "교회안내",
        "children": [
            {"name": "담임목사 인사", "page": 8},
            {"name": "교회 연혁", "page": 9},
            {
                "name": "섬기는 분",
                "page": 79,
                "children": [
                    {"name": "교역자", "page": 154},
                    {"name": "장로", "page": 155},
                ],
            },
            {"name": "예배시간", "page": 78},
            {"name": "오시는길", "page": 12},
            {"name": "새가족 안내", "page": 108},
            {"name": "교회둘러보기", "page": 147},
        ],
    },
    {
        "name": "교회소식",
        "children": [
            {"name": "주보", "mid": "jubo"},
            {"name": "목회일정", "page": 84},
        ],
    },
    {
        "name": "선교와 봉사",
        "children": [
            {"name": "국내선교", "page": 25},
            {"name": "해외선교", "page": 26},
            {"name": "특수선교", "page": 91},
            {"name": "사랑의 쌀", "page": 92},
            {"name": "장학위원회", "page": 146},
            {"name": "동키데이", "page": 93},
        ],
    },
    {
        "name": "교회학교",
        "children": [
            {
                "name": "유치부",
                "children": [
                    {"name": "유치부 소개", "page": 109},
                ],
            },
            {
                "name": "아동부",
                "children": [
                    {"name": "아동부 소개", "page": 112},
                ],
            },
            {
                "name": "청소년부",
                "children": [
                    {"name": "청소년부 소개", "page": 115},
                ],
            },
            {
                "name": "청년부",
                "children": [
                    {"name": "청년부 소개", "page": 118},
                ],
            },
        ],
    },
    {
        "name": "교회방송",
        "children": [
            {"name": "주일대예배 설교", "mid": "sermon"},
            {"name": "성가대", "mid": "choir"},
            {"name": "브니엘 찬양팀", "mid": "peniel"},
            {"name": "교회행사", "mid": "eventvideo"},
        ],
    },
    {
        "name": "커뮤니티",
        "children": [
            {"name": "행사사진", "mid": "picture"},
            {"name": "새가족소개", "mid": "newface"},
            {"name": "기도요청", "mid": "pray"},
            {"name": "동명사랑방", "mid": "community"},
        ],
    },
]


def next_srl(cur) -> int:
    cur.execute("INSERT INTO rx_sequence () VALUES ()")
    return int(cur.lastrowid)


def ensure_page(cur, mid: str, title: str) -> str:
    cur.execute("SELECT module_srl FROM rx_modules WHERE mid=%s", (mid,))
    row = cur.fetchone()
    if row:
        cur.execute(
            "UPDATE rx_modules SET menu_srl=%s, browser_title=%s WHERE mid=%s",
            (MAIN_MENU_SRL, title, mid),
        )
        return mid

    module_srl = next_srl(cur)
    content = STUB_HTML.format(title=title)
    cur.execute(
        """
        INSERT INTO rx_modules
        (module_srl, module, module_category_srl, menu_srl, site_srl, domain_srl, mid,
         layout_srl, mlayout_srl, use_mobile, skin, is_skin_fix, mskin, is_mskin_fix,
         browser_title, description, content, mcontent, is_default, open_rss, regdate)
        VALUES (%s,'page',0,%s,0,-1,%s,-1,-1,'N','/USE_DEFAULT/','N','/USE_DEFAULT/','N',
                %s,'',%s,'','N','Y',%s)
        """,
        (module_srl, MAIN_MENU_SRL, mid, title, content, REGDATE),
    )
    print(f"  page stub: {mid} ({title})")
    return mid


def resolve_url(cur, node: dict) -> str | None:
    if node.get("mid"):
        return node["mid"]
    if node.get("url"):
        return node["url"]
    if node.get("page"):
        return ensure_page(cur, f"p{node['page']}", node["name"])
    return None


def insert_menu_node(cur, node: dict, parent_srl: int, order: int) -> int:
    url = resolve_url(cur, node)
    children = node.get("children") or []

    if not url and children:
        first = resolve_url(cur, children[0])
        url = first or "#"

    menu_item_srl = next_srl(cur)
    listorder = -(order * 10)
    cur.execute(
        """
        INSERT INTO rx_menu_item
        (menu_item_srl, parent_srl, menu_srl, name, url, is_shortcut, open_window, expand, listorder, regdate)
        VALUES (%s,%s,%s,%s,%s,'N','N','N',%s,%s)
        """,
        (menu_item_srl, parent_srl, MAIN_MENU_SRL, node["name"], url or "#", listorder, REGDATE),
    )

    for i, child in enumerate(children, 1):
        insert_menu_node(cur, child, menu_item_srl, i)

    return menu_item_srl


def bind_board_modules(cur) -> None:
    mids = list(BOARD_MID.values()) + list(BROADCAST_MID.values())
    for mid in mids:
        cur.execute(
            "UPDATE rx_modules SET menu_srl=%s WHERE mid=%s",
            (MAIN_MENU_SRL, mid),
        )
    print(f"  bound {len(mids)} boards to Main Menu")


def main() -> None:
    conn = pymysql.connect(**DB, database=TARGET)
    cur = conn.cursor()

    cur.execute("UPDATE rx_menu SET title=%s WHERE menu_srl=%s", ("동명교회 메인", MAIN_MENU_SRL))
    cur.execute("DELETE FROM rx_menu_item WHERE menu_srl=%s", (MAIN_MENU_SRL,))
    print("cleared Main Menu items")

    for i, top in enumerate(MENU_TREE, 1):
        insert_menu_node(cur, top, 0, i)
        print(f"menu: {top['name']}")

    bind_board_modules(cur)
    conn.commit()
    conn.close()
    print("Done. Clear Rhymix cache and check http://HOST:8080/")


if __name__ == "__main__":
    main()
