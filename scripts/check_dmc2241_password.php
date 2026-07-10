<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$user_id = 'dmc2241';
$candidates = ['dmchurch2026!', 'dmchurch2241', 'dmc@7191', 'dkagh@6918'];
$oMemberController = getController('member');
foreach ($candidates as $p) {
	$out = $oMemberController->doLogin($user_id, $p, false);
	echo $p . '=' . ($out->toBool() ? 'Y' : 'N') . PHP_EOL;
}
