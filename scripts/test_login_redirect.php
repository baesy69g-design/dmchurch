<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
getModel('church_member');
echo church_memberModel::getLayoutLoginUrl() . PHP_EOL;
$_SERVER['REQUEST_URI'] = '/index.php?mid=member&act=dispMemberLoginForm';
Context::set('act', 'dispMemberLoginForm');
Context::set('is_logged', false);
echo 'act=' . Context::get('act') . ' logged=' . (Context::get('is_logged') ? 'Y' : 'N') . PHP_EOL;
