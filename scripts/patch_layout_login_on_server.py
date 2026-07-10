#!/usr/bin/env python3
from pathlib import Path

path = Path("/var/www/vhosts/localhost/html/layouts/xedition/layout.html")
text = path.read_text(encoding="utf-8")

old_logged = """<a href="{getUrl('act', 'dispMemberInfo')}" class="login_after">"""
new_logged = """<a href="{getUrl('act', 'dispMemberModifyPassword')}" class="login_after">"""
old_menu_loop = """<li loop="$logged_info->menu_list => $key, $val"><a href="{getUrl('', 'mid', $mid, 'act', $key)}">{lang($val)}</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberLogout')}">{$lang->cmd_logout}</a></li>"""
new_menu = """<li><a href="{getUrl('act', 'dispMemberModifyPassword')}">암호변경</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberLogout')}">{$lang->cmd_logout}</a></li>"""
old_guest = """<li><a href="{getUrl('act', 'dispMemberSignUpForm')}">{$lang->cmd_signup}</a></li>
\t\t\t\t\t\t\t</ul>"""
new_guest = """<li><a href="{getUrl('act', 'dispMemberSignUpForm')}">{$lang->cmd_signup}</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberModifyPassword')}">암호변경</a></li>
\t\t\t\t\t\t\t</ul>"""

for old, new in ((old_logged, new_logged), (old_menu_loop, new_menu), (old_guest, new_guest)):
    if old not in text:
        if new in text:
            print("skip (already):", new[:50])
            continue
        raise SystemExit("block not found: " + old[:60])
    text = text.replace(old, new)

path.write_text(text, encoding="utf-8")
print("layout patched")
