<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$m = MemberModel::getMemberInfoByUserID('baesy69');
echo 'baesy69 srl=' . $m->member_srl . ' needs_verify=' . (church_memberModel::needsEmailVerification($m) ? 'Y' : 'N') . PHP_EOL;
