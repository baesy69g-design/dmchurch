<?php
define('RX_BASEDIR', '/var/www/vhosts/localhost/html/');
require RX_BASEDIR . 'common/autoload.php';
Context::init();

$config = getModel('module')->getModuleConfig('church_write');
$tiles = is_array($config->main_tiles ?? null) ? $config->main_tiles : [];
$prev = is_array($tiles['worship_time'] ?? null) ? $tiles['worship_time'] : [];
$tiles['worship_time'] = [
	'image_url' => './files/church/main_tile/worship_time.jpg?t=' . time(),
	'link_url' => trim((string)($prev['link_url'] ?? '')),
];
$config->main_tiles = $tiles;
$r = getController('module')->insertModuleConfig('church_write', $config);
echo ($r->toBool() ? 'config ok' : 'config fail') . "\n";
echo $tiles['worship_time']['image_url'] . "\n";
