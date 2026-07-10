<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$user_id = $argv[1] ?? 'baesy69';
$password = $argv[2] ?? 'dkagh@6918';

$login = getController('member')->doLogin($user_id, $password, false);
echo 'login: ' . ($login->toBool() ? 'OK' : 'FAIL') . "\n";
$logged = Context::get('logged_info');
if (!$logged)
{
	exit(1);
}

$profile_path = church_memberModel::getMemberProfileUrl();
$host = 'https://dmchurch.kr';
$fetch_url = preg_match('~^https?://~i', $profile_path) ? $profile_path : $host . $profile_path;
echo "fetch: {$fetch_url}\n";

$ch = curl_init($fetch_url);
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_MAXREDIRS => 5,
	CURLOPT_COOKIE => session_name() . '=' . session_id(),
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$redirects = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
curl_close($ch);

echo "HTTP={$code} redirects={$redirects}\nurl={$effective}\n";
foreach (['church-member-heading', 'church_profile_form', 'ERR_ACT_NOT_FOUND', 'ERR_TOO_MANY', 'msg_invalid_request'] as $k)
{
	if (strpos((string)$body, $k) !== false)
	{
		echo "found: {$k}\n";
	}
}
echo strpos((string)$body, 'church-member-heading') !== false ? "PROFILE OK\n" : "PROFILE FAIL\n";
exit(strpos((string)$body, 'church-member-heading') !== false ? 0 : 1);
