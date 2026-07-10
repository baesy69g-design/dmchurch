<?php
/**
 * One-time Rhymix bootstrap: install church_write module + activate addon.
 * Run: docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/church_write_bootstrap.php
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$oInstallController = getController('install');
$path = __RX_BASEDIR__ . '/modules/church_write';
$output = $oInstallController->installModule('church_write', $path);
if (!$output->toBool()) {
	echo "installModule failed: " . $output->getMessage() . PHP_EOL;
	exit(1);
}
echo "church_write module installed." . PHP_EOL;

$now = date('YmdHis');
$db = DB::getInstance();
foreach (['church_board_ui'] as $addon) {
	$args = new stdClass;
	$args->addon = $addon;
	$args->is_used = 'Y';
	$args->is_used_m = 'Y';
	$args->extra_vars = '';
	$args->regdate = $now;
	$db->executeQuery('addons.insertAddon', $args);
	$args->site_srl = 0;
	$db->executeQuery('addons.insertSiteAddon', $args);
}
echo "addon church_board_ui activated." . PHP_EOL;

$oAddonController = getController('addon');
$oAddonController->makeCacheFile(0, 'pc', 'site');
$oAddonController->makeCacheFile(0, 'mobile', 'site');
echo "addon cache rebuilt." . PHP_EOL;

if (is_dir(__RX_BASEDIR__ . '/files/cache')) {
	FileHandler::removeDir(__RX_BASEDIR__ . '/files/cache');
}
echo "cache cleared." . PHP_EOL;
