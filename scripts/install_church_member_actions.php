<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$oInstallController = getController('install');
$oInstallController->installModule('church_member', RX_BASEDIR . 'modules/church_member');
echo "church_member module actions installed\n";
