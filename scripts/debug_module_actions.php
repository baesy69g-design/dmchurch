<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

foreach (['church_member', 'church_write', 'dmcadmin'] as $mod) {
    $xml = ModuleModel::getModuleActionXml($mod);
    $n = ($xml && isset($xml->action)) ? count((array)$xml->action) : 0;
    echo "$mod actions: $n\n";
}

$oModuleModel = getModel('module');
$info = $oModuleModel->getModuleInfoByMid('dmcadmin');
echo 'dmcadmin mid info: ' . ($info ? $info->module : 'none') . "\n";

$db = DB::getInstance();
$rows = $db->query("SELECT module, mid, browser_title FROM modules WHERE module IN ('church_member','church_write','dmcadmin')")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
