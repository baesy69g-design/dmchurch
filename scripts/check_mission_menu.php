<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
$oDB = Rhymix\Framework\DB::getInstance();
$rows = $oDB->query(
    "SELECT menu_item_srl, parent_srl, name, url, listorder FROM menu_item WHERE menu_srl = 48 AND parent_srl IN (SELECT menu_item_srl FROM menu_item WHERE menu_srl=48 AND parent_srl=0 AND name LIKE '%선교%') ORDER BY listorder ASC"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['listorder'] . "\t" . $r['name'] . "\t" . $r['url'] . "\n";
}
