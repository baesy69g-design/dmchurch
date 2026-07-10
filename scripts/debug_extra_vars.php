<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$m = MemberModel::getMemberInfoByUserID('baesy69');
echo "member_srl={$m->member_srl}\n";

$out = executeQuery('member.getMemberInfoByMemberSrl', (object)['member_srl' => $m->member_srl]);
echo "db extra raw: " . substr((string)$out->data->extra_vars, 0, 300) . "\n";

getModel('dmcadmin');
$extra = dmcadminModel::getMemberExtra($m);
echo "parsed keys: " . implode(',', array_keys((array)$extra)) . "\n";

$ctrl = MemberController::getInstance();
$r = $ctrl->updateMemberExtraVars($m->member_srl, ['pending_email' => 'dmchurch1972@gmail.com']);
echo 'updateMemberExtraVars: ' . ($r->toBool() ? 'OK' : 'FAIL ' . $r->getMessage()) . "\n";

$out2 = executeQuery('member.getMemberInfoByMemberSrl', (object)['member_srl' => $m->member_srl]);
echo "after extra: " . substr((string)$out2->data->extra_vars, 0, 300) . "\n";
