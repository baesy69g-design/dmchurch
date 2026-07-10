<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$db = Rhymix\Framework\DB::getInstance();
$rows = $db->query('SELECT menu_item_srl, parent_srl, name, url, listorder FROM menu_item WHERE menu_srl = 48')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if ((int)$r['parent_srl'] === 213 || (int)$r['menu_item_srl'] === 213 || preg_match('/^p2/', (string)$r['url'])) {
        echo implode(' | ', $r) . "\n";
    }
}
