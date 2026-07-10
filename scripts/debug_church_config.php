<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();
$db = DB::getInstance();
$result = $db->executeQuery('SELECT site_srl, module, LEFT(config,200) AS c FROM rx_module_config');
foreach ($result->data as $row) {
    echo $row->site_srl . ' | ' . $row->module . ' | ' . $row->c . "\n";
}
$c = ModuleModel::getModuleConfig('church_write');
echo "\ngetModuleConfig keys: " . implode(', ', array_keys((array)$c)) . "\n";
