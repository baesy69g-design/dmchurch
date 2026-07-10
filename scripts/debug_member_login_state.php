<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

foreach (['dmc2241', 'baesy69'] as $uid) {
	$m = MemberModel::getMemberInfoByUserID($uid);
	if (!$m) {
		echo "{$uid}: NOT FOUND\n";
		continue;
	}
	$need = church_memberModel::needsEmailVerification($m);
	$exempt = church_memberModel::isExemptMember($m);
	echo "{$uid}: srl={$m->member_srl} admin={$m->is_admin} denied={$m->denied} email={$m->email_address}\n";
	echo "  needsEmailVerification=" . ($need ? 'Y' : 'N') . " exempt=" . ($exempt ? 'Y' : 'N') . "\n";
}
