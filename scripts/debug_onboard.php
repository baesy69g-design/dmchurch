<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

foreach (['baesy69', 'dmc2241'] as $uid) {
	$m = MemberModel::getMemberInfoByUserID($uid);
	echo "=== $uid ===\n";
	if (!$m) {
		echo "not found\n\n";
		continue;
	}
	$extra = church_memberModel::getMemberExtra($m);
	echo 'email: ' . ($m->email_address ?? '') . "\n";
	echo 'extra: ' . json_encode($extra, JSON_UNESCAPED_UNICODE) . "\n";
	echo 'needs: ' . (church_memberModel::needsEmailVerification($m) ? 'Y' : 'N') . "\n";
	echo 'guide: ' . (church_memberModel::shouldShowLoginGuide($uid) ? 'Y' : 'N') . "\n\n";
}
