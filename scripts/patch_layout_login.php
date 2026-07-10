<?php
$path = '/var/www/vhosts/localhost/html/layouts/xedition/layout.html';
$text = file_get_contents($path);

$text = str_replace(
	"getUrl('act', 'dispMemberInfo')}\" class=\"login_after\"",
	"getUrl('act', 'dispMemberModifyPassword')}\" class=\"login_after\"",
	$text
);

$text = preg_replace(
	'/\s*<li loop="\$logged_info->menu_list => \$key, \$val"><a href="\{getUrl\(\'\', \'mid\', \$mid, \'act\', \$key\)\}">\{lang\(\$val\)\}<\/a><\/li>\s*/',
	"\n",
	$text,
	1,
	$removed
);

$pwdItem = '<li><a href="{getUrl(\'act\', \'dispMemberModifyPassword\')}">암호변경</a></li>';
$logoutItem = '<li><a href="{getUrl(\'act\', \'dispMemberLogout\')}">{$lang->cmd_logout}</a></li>';

if (strpos($text, 'cmd_modify_password') === false)
{
	$text = str_replace($logoutItem, $pwdItem . "\n\t\t\t\t\t\t\t\t" . $logoutItem, $text);
}

$signupBlock = '<li><a href="{getUrl(\'act\', \'dispMemberSignUpForm\')}">{$lang->cmd_signup}</a></li>';
if (substr_count($text, 'cmd_modify_password') < 2)
{
	$text = str_replace(
		$signupBlock . "\n\t\t\t\t\t\t\t</ul>",
		$signupBlock . "\n\t\t\t\t\t\t\t\t" . $pwdItem . "\n\t\t\t\t\t\t\t</ul>",
		$text
	);
}

file_put_contents($path, $text);
echo "layout patched (menu_list removed={$removed})\n";
