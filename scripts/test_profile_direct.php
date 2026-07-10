<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$_SERVER['REQUEST_METHOD'] = 'GET';
$user_id = $argv[1] ?? 'baesy69';
$password = $argv[2] ?? 'dkagh@6918';

getController('member')->doLogin($user_id, $password, false);
if (!Context::get('logged_info'))
{
	echo "login failed\n";
	exit(1);
}

Context::set('act', 'dispChurchMemberProfile');
Context::set('module', 'church_member');

$oView = getView('church_member');
ob_start();
$oView->dispChurchMemberProfile();
$redirect = $oView->getRedirectUrl();
$buf = ob_get_clean();

if ($redirect)
{
	echo "REDIRECT: {$redirect}\n";
	exit(1);
}

$html = '';
if (method_exists($oView, 'getTemplateBuffer'))
{
	$html = (string)$oView->getTemplateBuffer();
}
echo 'direct view html len=' . strlen($html) . "\n";
echo (strpos($html, 'church-member-heading') !== false ? "DIRECT VIEW OK\n" : "DIRECT VIEW FAIL\n");

// full handler path
$mh = new ModuleHandler('church_member', 'dispChurchMemberProfile');
$mh->init();
$res = $mh->procModule();
echo 'handler class=' . get_class($res) . ' msg=' . (method_exists($res, 'getMessage') ? $res->getMessage() : '') . "\n";

exit(strpos($html, 'church-member-heading') !== false ? 0 : 1);
