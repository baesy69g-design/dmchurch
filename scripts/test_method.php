<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
getModel('dmcadmin');
echo method_exists('dmcadminModel', 'injectMainHomeContent') ? 'method_ok' : 'method_missing';
echo PHP_EOL;
