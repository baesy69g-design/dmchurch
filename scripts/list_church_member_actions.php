<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$xml = ModuleModel::getModuleActionXml('church_member');
$actions = [];
if ($xml && isset($xml->action))
{
	foreach ((array)$xml->action as $a)
	{
		$name = (string)($a['name'] ?? '');
		$standalone = (string)($a['standalone'] ?? 'N');
		$actions[] = $name . ' standalone=' . ($standalone ?: 'N');
	}
}
echo 'church_member action count: ' . count($actions) . PHP_EOL;
foreach ($actions as $line)
{
	echo '  - ' . $line . PHP_EOL;
}

$has_profile = in_array('dispChurchMemberProfile', array_map(function ($line) {
	return strtok($line, ' ');
}, $actions), true);
echo 'has dispChurchMemberProfile: ' . ($has_profile ? 'Y' : 'N') . PHP_EOL;
