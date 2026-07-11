<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();
$path = RX_BASEDIR . 'modules/church_write/conf/module.xml';
echo "xml_exists=" . (is_file($path) ? 'Y' : 'N') . PHP_EOL;
echo "xml_mtime=" . date('c', filemtime($path)) . PHP_EOL;
echo file_get_contents($path) . PHP_EOL;
$o = ModuleModel::getModuleActionXml('church_write');
echo "type=" . gettype($o) . PHP_EOL;
if (is_object($o)) {
	echo "keys=" . implode(',', array_keys(get_object_vars($o))) . PHP_EOL;
	if (isset($o->action)) {
		echo "action_keys=" . implode(',', array_keys((array)$o->action)) . PHP_EOL;
	}
	if (isset($o->action_list)) {
		echo "action_list_keys=" . implode(',', array_keys((array)$o->action_list)) . PHP_EOL;
	}
}
// try routing a fake request
$mi = ModuleModel::getModuleInfoXml('church_write');
echo "info_type=" . gettype($mi) . PHP_EOL;
