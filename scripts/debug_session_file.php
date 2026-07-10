<?php
/**
 * 로그인 직후 PHP 세션 파일 내용 확인
 */
define('__RX_BASEDIR__', dirname(__DIR__));
$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'SessionDebug/1.0';

require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

Rhymix\Framework\Session::start();
$sid = session_id();
echo 'before_sid=' . $sid . PHP_EOL;

$o = getController('member')->doLogin('baesy69', 'dkagh@6918', false);
echo 'login=' . ($o->toBool() ? 'Y' : 'N') . PHP_EOL;
echo 'member_srl=' . ($_SESSION['member_srl'] ?? 'none') . PHP_EOL;
echo 'rx_login=' . ($_SESSION['RHYMIX']['login'] ?? 'none') . PHP_EOL;
echo 'next_refresh=' . (($_SESSION['RHYMIX']['next_refresh'] ?? false) ? 'Y' : 'N') . PHP_EOL;

Rhymix\Framework\Session::close();
$path = session_save_path() ?: sys_get_temp_dir();
$file = rtrim($path, '/') . '/sess_' . $sid;
echo 'session_file=' . $file . PHP_EOL;
echo 'exists=' . (is_file($file) ? 'Y' : 'N') . PHP_EOL;
if (is_file($file)) {
	$raw = file_get_contents($file);
	echo 'has_member=' . (strpos($raw, 'member_srl') !== false ? 'Y' : 'N') . PHP_EOL;
	echo 'snippet=' . substr($raw, 0, 200) . PHP_EOL;
}
