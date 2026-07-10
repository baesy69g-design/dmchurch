<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$o = executeQuery('member.getMemberList', (object)['list_count' => 5, 'page' => 1]);
if ($o->data) {
	$list = is_array($o->data) ? $o->data : [$o->data];
	foreach ($list as $row) {
		$m = MemberModel::getMemberInfoByMemberSrl((int)$row->member_srl);
		if (church_memberModel::needsEmailVerification($m)) {
			echo $m->user_id . "\n";
		}
	}
}
