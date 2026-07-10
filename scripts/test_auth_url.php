<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$_SERVER['HTTP_HOST'] = '49.247.205.159:8080';
$_SERVER['HTTPS'] = 'off';

getModel('church_member');
$url1 = church_memberModel::generateAuthUrl('procChurchConfirmEmail', 130, 'testkey123');
$url2 = church_memberModel::generateAuthUrl('dispChurchRecoverReset', 130, 'testkey456');
echo "confirm: $url1\n";
echo "recover: $url2\n";

$path = getNotEncodedUrl('', 'module', 'church_member', 'act', 'procChurchConfirmEmail', 'member_srl', 130, 'auth_key', 'x');
echo "getNotEncodedUrl: $path\n";
echo "getCurrentDomainURL: " . Rhymix\Framework\URL::getCurrentDomainURL($path) . "\n";
