<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
chdir(__RX_BASEDIR__);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

getController('member')->doLogin('baesy69', 'dkagh@6918', false);
$_GET['module'] = 'church_member';
$_GET['act'] = 'dispChurchMemberProfile';

ob_start();
require __RX_BASEDIR__ . 'index.php';
$html = ob_get_clean();

echo 'html len=' . strlen($html) . PHP_EOL;
foreach (['church-member-heading', 'church_profile_form', '로그인이 필요합니다', 'Error', 'Fatal'] as $k)
{
	if (strpos($html, $k) !== false)
	{
		echo "found: {$k}\n";
	}
}
exit(strpos($html, 'church-member-heading') !== false ? 0 : 1);
