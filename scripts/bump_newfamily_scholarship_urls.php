<?php
define('RX_BASEDIR', '/var/www/vhosts/localhost/html/');
require RX_BASEDIR . 'common/autoload.php';
Context::init();

$config = getModel('module')->getModuleConfig('church_write');
$tiles = is_array($config->main_tiles ?? null) ? $config->main_tiles : [];
foreach (['new_family', 'scholarship'] as $key)
{
	$row = is_array($tiles[$key] ?? null) ? $tiles[$key] : [];
	$tiles[$key] = [
		'image_url' => './files/church/main_tile/' . $key . '.jpg?t=' . time(),
		'link_url' => trim((string)($row['link_url'] ?? '')),
	];
	echo $key . ' => ' . $tiles[$key]['image_url'] . "\n";
}
$config->main_tiles = $tiles;
$r = getController('module')->insertModuleConfig('church_write', $config);
echo ($r->toBool() ? 'config ok' : 'config fail') . "\n";
