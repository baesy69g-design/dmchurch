<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$path = ModuleHandler::getModulePath('church_member');
echo "module path: {$path}\n";
echo "xml exists: " . (is_file($path . 'conf/module.xml') ? 'yes' : 'no') . "\n";

$raw = file_get_contents($path . 'conf/module.xml');
echo "xml contains dispChurchMemberProfile: " . (strpos($raw, 'dispChurchMemberProfile') !== false ? 'yes' : 'no') . "\n";

$parsed = Rhymix\Framework\Parsers\ModuleActionParser::loadXML($path . 'conf/module.xml');
echo "parser actions: " . implode(',', array_keys((array)$parsed->action)) . "\n";

foreach (['ko', 'en'] as $lang)
{
	Context::setLangType($lang);
	$xml = ModuleModel::getModuleActionXml('church_member');
	$keys = $xml && isset($xml->action) ? array_keys((array)$xml->action) : [];
	echo "model lang={$lang} count=" . count($keys) . " hasProfile=" . (in_array('dispChurchMemberProfile', $keys, true) ? 'yes' : 'no') . "\n";
	if ($keys)
	{
		echo "  keys: " . implode(',', $keys) . "\n";
	}
}
