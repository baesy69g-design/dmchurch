#!/usr/bin/env python3
"""layout.html 로그인 드롭다운을 회원가입·로그인·비번변경(로그아웃)만 남기도록 패치."""

from pathlib import Path

LAYOUT = Path(__file__).resolve().parents[1] / "scripts" / "layout_server.html"

OLD_LOGGED = """\t\t\t\t\t\t<a href="{getUrl('act', 'dispMemberInfo')}" class="login_after">
\t\t\t\t\t\t\t<!--@if(!empty($logged_info->profile_image->src))-->
\t\t\t\t\t\t\t\t<img src="{$logged_info->profile_image->src}" alt="{$logged_info->nick_name}" />
\t\t\t\t\t\t\t<!--@else-->
\t\t\t\t\t\t\t\t<img src="./img/ico_default.jpg" alt="{$logged_info->nick_name}" />
\t\t\t\t\t\t\t<!--@end-->
\t\t\t\t\t\t</a>
\t\t\t\t\t\t<div class="ly ly_login">
\t\t\t\t\t\t\t<ul>
\t\t\t\t\t\t\t\t<li loop="$logged_info->menu_list => $key, $val"><a href="{getUrl('', 'mid', $mid, 'act', $key)}">{lang($val)}</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberLogout')}">{$lang->cmd_logout}</a></li>
\t\t\t\t\t\t\t</ul>
\t\t\t\t\t\t\t<span class="edge"></span>
\t\t\t\t\t\t</div>"""

NEW_LOGGED = """\t\t\t\t\t\t<a href="{getUrl('act', 'dispMemberModifyPassword')}" class="login_after">
\t\t\t\t\t\t\t<!--@if(!empty($logged_info->profile_image->src))-->
\t\t\t\t\t\t\t\t<img src="{$logged_info->profile_image->src}" alt="{$logged_info->nick_name}" />
\t\t\t\t\t\t\t<!--@else-->
\t\t\t\t\t\t\t\t<img src="./img/ico_default.jpg" alt="{$logged_info->nick_name}" />
\t\t\t\t\t\t\t<!--@end-->
\t\t\t\t\t\t</a>
\t\t\t\t\t\t<div class="ly ly_login">
\t\t\t\t\t\t\t<ul>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberModifyPassword')}">암호변경</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberLogout')}">{$lang->cmd_logout}</a></li>
\t\t\t\t\t\t\t</ul>
\t\t\t\t\t\t\t<span class="edge"></span>
\t\t\t\t\t\t</div>"""

OLD_GUEST = """\t\t\t\t\t\t<div class="ly ly_login">
\t\t\t\t\t\t\t<ul>
\t\t\t\t\t\t\t\t<li><a id="ly_login_btn" href="{getUrl('act', 'dispMemberLoginForm')}">{$lang->cmd_login}</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberSignUpForm')}">{$lang->cmd_signup}</a></li>
\t\t\t\t\t\t\t</ul>
\t\t\t\t\t\t\t<span class="edge"></span>
\t\t\t\t\t\t</div>"""

NEW_GUEST = """\t\t\t\t\t\t<div class="ly ly_login">
\t\t\t\t\t\t\t<ul>
\t\t\t\t\t\t\t\t<li><a id="ly_login_btn" href="{getUrl('act', 'dispMemberLoginForm')}">{$lang->cmd_login}</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberSignUpForm')}">{$lang->cmd_signup}</a></li>
\t\t\t\t\t\t\t\t<li><a href="{getUrl('act', 'dispMemberModifyPassword')}">암호변경</a></li>
\t\t\t\t\t\t\t</ul>
\t\t\t\t\t\t\t<span class="edge"></span>
\t\t\t\t\t\t</div>"""


def main() -> None:
	text = LAYOUT.read_text(encoding="utf-8")
	changed = False
	for old, new in ((OLD_LOGGED, NEW_LOGGED), (OLD_GUEST, NEW_GUEST)):
		if old in text:
			text = text.replace(old, new)
			changed = True
		elif new in text:
			print("already patched:", new[:40])
		else:
			raise SystemExit("patch block not found in layout_server.html")
	if changed:
		LAYOUT.write_text(text, encoding="utf-8")
		print("patched", LAYOUT)
	else:
		print("no changes")


if __name__ == "__main__":
	main()
