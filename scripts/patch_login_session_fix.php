<?php
/**
 * 로그인 직후 세션 ID 재발급(next_refresh)으로 세션이 끊기는 문제 수정
 * - Session::login($srl, false) 로 next_refresh 방지
 * - rx_login_status 쿠키는 별도 갱신
 */
$path = __DIR__ . '/../modules/member/member.controller.php';
if (!is_file($path))
{
	fwrite(STDERR, "missing: {$path}\n");
	exit(1);
}
$code = file_get_contents($path);
$old = "\t\t// Log in!\n\t\tRhymix\\Framework\\Session::login(\$member_info->member_srl);";
$new = "\t\t// Log in! (next_refresh 방지: 로그인 유지 미체크 시 세션 끊김 수정)\n\t\tRhymix\\Framework\\Session::login(\$member_info->member_srl, false);\n\t\tRhymix\\Framework\\Session::checkLoginStatusCookie();";
if (strpos($code, $new) !== false)
{
	echo "already patched\n";
	exit(0);
}
if (strpos($code, $old) === false)
{
	fwrite(STDERR, "pattern not found\n");
	exit(1);
}
file_put_contents($path, str_replace($old, $new, $code));
echo "patched: {$path}\n";
