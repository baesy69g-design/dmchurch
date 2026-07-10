<?php
$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = '/index.php';

define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
try {
	$oModule = ModuleHandler::getInstance();
	$oModule->init();
	echo "OK mid=" . Context::get('mid') . " act=" . Context::get('act') . PHP_EOL;
} catch (Throwable $e) {
	echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
	echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
	echo $e->getTraceAsString() . PHP_EOL;
}
