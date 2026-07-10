<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
$db = Rhymix\Framework\DB::getInstance();
foreach ($db->query("SELECT mid, module, browser_title, is_default, LEFT(mcontent,80) AS mc FROM rx_modules WHERE mid IN ('index','dmcadmin')")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
foreach ($db->query('SELECT domain, index_module_srl FROM rx_domains')->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo 'domain: ' . json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
