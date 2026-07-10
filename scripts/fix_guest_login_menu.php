<?php
$path = '/var/www/vhosts/localhost/html/layouts/xedition/layout.html';
$text = file_get_contents($path);
$signup = '<li><a href="{getUrl(\'act\', \'dispMemberSignUpForm\')}">{$lang->cmd_signup}</a></li>';
$pwd = '<li><a href="{getUrl(\'act\', \'dispMemberModifyPassword\')}">암호변경</a></li>';

if (substr_count($text, 'cmd_modify_password') >= 2)
{
	echo "guest menu already has password change\n";
	exit(0);
}

$text = preg_replace(
	'/(<li><a href="\{getUrl\(\'act\', \'dispMemberSignUpForm\'\)\}">\{\$lang->cmd_signup\}<\/a><\/li>)(\s*<\/ul>)/',
	'$1' . "\n\t\t\t\t\t\t\t\t" . $pwd . '$2',
	$text,
	1,
	$count
);

if ($count === 0)
{
	fwrite(STDERR, "guest block not found\n");
	exit(1);
}

file_put_contents($path, $text);
echo "guest password link added\n";
