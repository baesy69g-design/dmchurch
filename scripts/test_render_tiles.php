<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
getModel('dmcadmin');
$html = dmcadminModel::renderMainHomeHtml();
echo 'html_len=' . strlen($html) . PHP_EOL;
echo (strpos($html, 'church-main-tiles') !== false ? 'has_tiles=1' : 'has_tiles=0') . PHP_EOL;
