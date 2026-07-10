<?php
define('__RX_BASEDIR__', dirname(__DIR__) . '/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
$db = Rhymix\Framework\DB::getInstance();
$rows = $db->query("SELECT mid, layout_srl, mlayout_srl, use_mobile, module FROM rx_modules WHERE mid='index'")->fetchAll();
print_r($rows);
$rows2 = $db->query("SELECT layout_srl, title, path FROM rx_layouts")->fetchAll();
print_r($rows2);
