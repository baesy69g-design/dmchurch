<?php
/**
 * HTTPS 환경 로그인 시뮬레이션 (Rhymix 내부)
 * usage: php debug_login_simulate.php USER_ID PASSWORD [Y|N keep_signed]
 */
define('__RX_BASEDIR__', dirname(__DIR__));

$user_id = $argv[1] ?? '';
$password = $argv[2] ?? '';
$keep = ($argv[3] ?? 'N') === 'Y' ? 'Y' : '';

if ($user_id === '' || $password === '')
{
	fwrite(STDERR, "usage: php debug_login_simulate.php USER_ID PASSWORD [Y|N]\n");
	exit(1);
}

// HTTPS 프록시 환경
$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'LoginDebug/1.0';

require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

Rhymix\Framework\Session::start();
$csrf = Rhymix\Framework\Session::createToken('');

$_POST = [
	'act' => 'procMemberLogin',
	'user_id' => $user_id,
	'password' => $password,
	'xe_validator_id' => 'layouts/xedition/layout/1',
	'success_return_url' => 'https://dmchurch.kr/',
	'error_return_url' => 'https://dmchurch.kr/',
	'_rx_csrf_token' => $csrf,
];
if ($keep === 'Y')
{
	$_POST['keep_signed'] = 'Y';
}

Context::setRequestMethod('POST');
Context::setRequestVars($_POST);
Context::set('act', 'procMemberLogin');
Context::set('user_id', $user_id);
Context::set('password', $password);

echo 'RX_SSL=' . (defined('RX_SSL') && RX_SSL ? 'Y' : 'N') . PHP_EOL;
echo 'login_url=' . getUrl('', 'act', 'procMemberLogin') . PHP_EOL;

try
{
	$oModule = ModuleHandler::getInstance();
	$oModule->init();
	$logged = Context::get('logged_info');
	$is_logged = Context::get('is_logged');
	echo 'is_logged=' . ($is_logged ? 'Y' : 'N') . PHP_EOL;
	echo 'logged_user=' . ($logged->user_id ?? 'none') . PHP_EOL;
	echo 'member_srl=' . (int)($logged->member_srl ?? 0) . PHP_EOL;
	echo 'validator=' . (Context::get('XE_VALIDATOR_MESSAGE') ?: 'none') . PHP_EOL;
	echo 'redirect=' . (Context::get('redirect_url') ?: 'none') . PHP_EOL;
	echo 'session_id=' . session_id() . PHP_EOL;
	echo 'rx_autologin=' . (isset($_COOKIE['rx_autologin']) ? 'set' : 'none') . PHP_EOL;
}
catch (Throwable $e)
{
	echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
	echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
	exit(1);
}
