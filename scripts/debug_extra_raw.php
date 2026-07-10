<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
$m = MemberModel::getMemberInfoByUserID('baesy69');
echo 'srl=' . ($m->member_srl ?? '') . PHP_EOL;
$o = executeQuery('member.getMemberInfoByMemberSrl', (object)['member_srl' => (int)$m->member_srl]);
echo ($o->data->extra_vars ?? 'empty') . PHP_EOL;
