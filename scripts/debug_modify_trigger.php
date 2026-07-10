<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
$t = getModel('module')->getTrigger('member.procMemberModifyInfo', 'church_member', 'controller', 'triggerMemberModifyInfoBefore', 'before');
echo 'trigger=' . ($t ? 'Y' : 'N') . PHP_EOL;
$m = MemberModel::getMemberInfoByUserID('baesy69');
echo 'email=' . ($m->email_address ?? '') . PHP_EOL;
