<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$xml_file = __RX_BASEDIR__ . 'modules/church_member/conf/module.xml';
$info = Rhymix\Framework\Parsers\ModuleActionParser::loadXML($xml_file);
echo 'type: ' . gettype($info) . PHP_EOL;
if (is_object($info))
{
	echo 'class: ' . get_class($info) . PHP_EOL;
	if (isset($info->action))
	{
		echo 'action type: ' . gettype($info->action) . PHP_EOL;
		if (is_array($info->action))
		{
			foreach ($info->action as $name => $a)
			{
				echo "  $name standalone=" . ($a->standalone ?? '?') . PHP_EOL;
			}
		}
	}
	print_r($info);
}
