<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
chdir(__RX_BASEDIR__);
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
$_SERVER['REQUEST_METHOD'] = 'GET';

getController('member')->doLogin('baesy69', 'dkagh@6918', false);

$oView = getView('church_member');
$oView->dispChurchMemberProfile();
ob_start();
try {
	$oView->display();
} catch (Throwable $e) {
	echo 'DISPLAY ERROR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
$html = ob_get_clean();
echo 'len=' . strlen($html) . "\n";
echo (strpos($html, 'church-member-heading') !== false ? "OK\n" : "FAIL\n");
if (strpos($html, 'church-member-heading') === false) {
	echo substr($html, 0, 800) . "\n";
}
