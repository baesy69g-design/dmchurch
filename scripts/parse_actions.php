<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();
$xml = '/var/www/vhosts/localhost/html/modules/church_write/conf/module.xml';
$info = Rhymix\Framework\Parsers\ModuleActionParser::loadXML($xml);
$keys = array_keys((array)($info->action ?? []));
sort($keys);
echo implode("\n", $keys) . PHP_EOL;
echo 'path=' . ModuleHandler::getModulePath('church_write') . PHP_EOL;
