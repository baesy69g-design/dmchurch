<?php
/**
 * 로그인 후 다음 GET 요청 시뮬레이션
 */
define('__RX_BASEDIR__', dirname(__DIR__));

// 1) POST login
$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/index.php?act=procMemberLogin';
$_SERVER['HTTP_USER_AGENT'] = 'SessionDebug/1.0';

require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
Rhymix\Framework\Session::start();
getController('member')->doLogin('baesy69', 'dkagh@6918', false);
$sid = session_id();
Rhymix\Framework\Session::close();
echo 'post_sid=' . $sid . ' srl=' . ($_SESSION['member_srl'] ?? 0) . PHP_EOL;

// 2) GET homepage with same session cookie
session_write_close();
$_COOKIE[session_name()] = $sid;
$_SESSION = [];
unset($GLOBALS['__Context__']);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
Context::init();
Rhymix\Framework\Session::start();
echo 'get_sid=' . session_id() . PHP_EOL;
echo 'get_srl=' . Rhymix\Framework\Session::getMemberSrl() . PHP_EOL;
echo 'is_member=' . (Rhymix\Framework\Session::isMember() ? 'Y' : 'N') . PHP_EOL;
echo 'next_refresh=' . (($_SESSION['RHYMIX']['next_refresh'] ?? false) ? 'Y' : 'N') . PHP_EOL;
