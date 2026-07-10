<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$user_id = $argv[1] ?? 'dmc2241';
$pass = $argv[2] ?? 'dmchurch2026!';
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member) { echo "not_found\n"; exit(1); }
$oMemberController = getController('member');
$out = $oMemberController->doLogin($user_id, $pass, false);
echo 'doLogin=' . ($out->toBool() ? 'Y' : 'N') . ' msg=' . $out->getMessage() . PHP_EOL;
