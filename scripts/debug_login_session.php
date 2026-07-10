<?php
/**
 * 로그인 진단 + 임시 비밀번호로 HTTPS 세션 테스트
 * usage: php debug_login_session.php [user_id]
 */
define('__RX_BASEDIR__', dirname(__DIR__));

$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';

require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$user_id = $argv[1] ?? 'dmc2241';
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member || !$member->member_srl)
{
	echo "member_not_found: {$user_id}\n";
	exit(1);
}

echo 'RX_SSL=' . (defined('RX_SSL') && RX_SSL ? 'Y' : 'N') . PHP_EOL;
echo 'url.default=' . config('url.default') . PHP_EOL;
echo 'url.ssl=' . config('url.ssl') . PHP_EOL;
echo 'session.use_ssl=' . (config('session.use_ssl') ? 'Y' : 'N') . PHP_EOL;
echo 'session.use_ssl_cookies=' . (config('session.use_ssl_cookies') ? 'Y' : 'N') . PHP_EOL;
echo 'cookie.secure=' . (config('cookie.secure') ? 'Y' : 'N') . PHP_EOL;
echo 'member=' . $member->user_id . ' srl=' . $member->member_srl . ' denied=' . ($member->denied ?? '') . PHP_EOL;

$temp_pass = 'ChurchTest!' . date('His');
$oMemberController = getController('member');
$args = new stdClass();
$args->member_srl = $member->member_srl;
$args->password = $temp_pass;
$output = executeQuery('member.updatePassword', $args);
if (!$output->toBool())
{
	// fallback: direct controller if query missing
	echo 'update_password_query_failed, trying changePassword' . PHP_EOL;
}

// Use Rhymix password API
Rhymix\Framework\Password::hashPassword($temp_pass, $member->member_srl);
$hashed = Rhymix\Framework\Password::hashPassword($temp_pass);
$args2 = new stdClass();
$args2->member_srl = $member->member_srl;
$args2->password = $hashed;
executeQuery('member.updateMemberPassword', $args2);

echo 'temp_password=' . $temp_pass . PHP_EOL;

Rhymix\Framework\Session::start();
$csrf = Rhymix\Framework\Session::createToken('');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
	'act' => 'procMemberLogin',
	'user_id' => $user_id,
	'password' => $temp_pass,
	'xe_validator_id' => 'layouts/xedition/layout/1',
	'success_return_url' => 'https://dmchurch.kr/',
	'error_return_url' => 'https://dmchurch.kr/',
	'_rx_csrf_token' => $csrf,
];
$_SERVER['HTTP_ORIGIN'] = 'https://dmchurch.kr';
$_SERVER['HTTP_REFERER'] = 'https://dmchurch.kr/';
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';

Context::setRequestMethod('POST');
Context::setRequestVars($_POST);
Context::set('act', 'procMemberLogin');

$out = $oMemberController->procMemberLogin($user_id, $temp_pass, '');
echo 'proc_result=' . ($out ? $out->getMessage() : 'ok') . PHP_EOL;
echo 'is_logged=' . (Context::get('is_logged') ? 'Y' : 'N') . PHP_EOL;
$logged = Context::get('logged_info');
echo 'logged_user=' . ($logged->user_id ?? 'none') . PHP_EOL;
echo 'session_id=' . session_id() . PHP_EOL;
echo 'csrf_check=' . (Rhymix\Framework\Security::checkCSRF() ? 'pass' : 'fail') . PHP_EOL;
