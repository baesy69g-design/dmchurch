<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
require __RX_BASEDIR__ . 'modules/dmcadmin/dmcadmin.model.php';
echo dmcadminModel::getMemberPageBannerUrl() . PHP_EOL;
$path = __RX_BASEDIR__ . 'files/church/sub_top/info.jpg';
echo (is_file($path) ? 'info.jpg exists' : 'info.jpg missing') . PHP_EOL;
