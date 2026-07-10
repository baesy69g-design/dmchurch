<?php
/**
 * 임시 비밀번호로 로그인 세션 테스트 (CLI)
 * usage: php test_login_with_temp_pass.php USER_ID
 */
define('__RX_BASEDIR__', dirname(__DIR__));

$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$user_id = $argv[1] ?? 'dmc2241';
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member || !$member->member_srl)
{
	echo "member_not_found\n";
	exit(1);
}

$temp_pass = 'ChurchFix!' . substr(md5((string)time()), 0, 6);
$hashed = Rhymix\Framework\Password::hashPassword($temp_pass);
$args = new stdClass();
$args->member_srl = $member->member_srl;
$args->password = $hashed;
executeQuery('member.updateMemberPassword', $args);

Rhymix\Framework\Session::start();
$csrf = Rhymix\Framework\Session::createToken('');

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ORIGIN'] = 'https://dmchurch.kr';
$_SERVER['HTTP_REFERER'] = 'https://dmchurch.kr/';
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
$_POST = [
	'act' => 'procMemberLogin',
	'user_id' => $user_id,
	'password' => $temp_pass,
	'xe_validator_id' => 'layouts/xedition/layout/1',
	'success_return_url' => 'https://dmchurch.kr/',
	'error_return_url' => 'https://dmchurch.kr/',
	'_rx_csrf_token' => $csrf,
];
Context::setRequestMethod('POST');
Context::setRequestVars($_POST);
Context::set('act', 'procMemberLogin');

$oModule = ModuleHandler::getInstance();
$oModule->init();

echo 'RX_SSL=' . (RX_SSL ? 'Y' : 'N') . PHP_EOL;
echo 'enforce_ssl_check=' . ((Context::get('site_module_info')->security ?? '') === 'always' ? 'Y' : 'N') . PHP_EOL;
echo 'csrf=' . (Rhymix\Framework\Security::checkCSRF() ? 'pass' : 'fail') . PHP_EOL;
echo 'is_logged=' . (Context::get('is_logged') ? 'Y' : 'N') . PHP_EOL;
echo 'logged=' . (Context::get('logged_info')->user_id ?? 'none') . PHP_EOL;
echo 'validator=' . (Context::get('XE_VALIDATOR_MESSAGE') ?: 'none') . PHP_EOL;
echo 'temp_password=' . $temp_pass . PHP_EOL;
