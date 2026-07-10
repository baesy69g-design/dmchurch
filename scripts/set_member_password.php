<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$user_id = $argv[1] ?? 'dmc2241';
$new_pass = $argv[2] ?? '';
if ($new_pass === '') { fwrite(STDERR, "usage: php set_member_password.php USER_ID NEW_PASSWORD\n"); exit(1); }
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member) { fwrite(STDERR, "not found\n"); exit(1); }
$args = new stdClass();
$args->member_srl = $member->member_srl;
$args->password = $new_pass;
$oMemberController = getController('member');
$output = $oMemberController->updateMemberPassword($args);
if (!$output->toBool()) {
	fwrite(STDERR, 'update failed: ' . $output->getMessage() . PHP_EOL);
	exit(1);
}
$login = $oMemberController->doLogin($user_id, $new_pass, false);
echo 'login_ok=' . ($login->toBool() ? 'Y' : 'N') . ' msg=' . $login->getMessage() . PHP_EOL;
