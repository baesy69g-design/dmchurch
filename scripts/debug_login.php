<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$_POST['act'] = 'procMemberLogin';
$_POST['user_id'] = 'baesy69';
$_POST['password'] = 'wrongpass';
$_POST['xe_validator_id'] = 'layouts/xedition/layout/1';
Context::setRequestMethod('POST');
Context::set('act', 'procMemberLogin');
Context::set('user_id', 'baesy69');
Context::set('password', 'wrongpass');

try {
	$oModule = ModuleHandler::getInstance();
	$oModule->init();
	$oModule->displayContent();
	echo "OK\n";
} catch (Throwable $e) {
	echo "ERR: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
}
