<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$xml_file = __RX_BASEDIR__ . 'modules/church_member/conf/module.xml';
$mtime = filemtime($xml_file);
$lang = Context::getLangType() ?: 'ko';
$cache_key = sprintf('site_and_module:module_action_xml:%s:%s:%d', 'church_member', $lang, $mtime);

Rhymix\Framework\Cache::delete($cache_key);
Rhymix\Framework\Cache::clearGroup('site_and_module');

$parsed = Rhymix\Framework\Parsers\ModuleActionParser::loadXML($xml_file);
echo "parser has profile: " . (isset($parsed->action->dispChurchMemberProfile) ? 'yes' : 'no') . PHP_EOL;

$xml = ModuleModel::getModuleActionXml('church_member');
echo "model has profile: " . (isset($xml->action->dispChurchMemberProfile) ? 'yes' : 'no') . PHP_EOL;
if ($xml && isset($xml->action))
{
	echo 'actions: ' . implode(',', array_keys((array)$xml->action)) . PHP_EOL;
}

getController('install')->installModule('church_member', __RX_BASEDIR__ . 'modules/church_member');
Rhymix\Framework\Cache::clearGroup('site_and_module');

$xml2 = ModuleModel::getModuleActionXml('church_member');
echo "after install has profile: " . (isset($xml2->action->dispChurchMemberProfile) ? 'yes' : 'no') . PHP_EOL;
