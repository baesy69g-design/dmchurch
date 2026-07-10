<?php
/**
 * dmcadmin 모듈 설치 + /dmcadmin 페이지 생성
 * Run: docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/dmcadmin_bootstrap.php
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$oInstallController = getController('install');
$path = __RX_BASEDIR__ . '/modules/dmcadmin';
$output = $oInstallController->installModule('dmcadmin', $path);
if (!$output->toBool()) {
	echo 'installModule failed: ' . $output->getMessage() . PHP_EOL;
	exit(1);
}
echo "dmcadmin module installed.\n";

$oModuleModel = getModel('module');
$existing = $oModuleModel->getModuleInfoByMid('dmcadmin');
if (!$existing) {
	$args = new stdClass;
	$args->module = 'dmcadmin';
	$args->mid = 'dmcadmin';
	$args->browser_title = '동명교회 관리';
	$args->site_srl = 0;
	$args->layout_srl = 0;
	$output = getController('module')->insertModule($args);
	if (!$output->toBool()) {
		echo 'insertModule failed: ' . $output->getMessage() . PHP_EOL;
		exit(1);
	}
	echo "dmcadmin page created (mid=dmcadmin).\n";
} else {
	echo "dmcadmin page already exists.\n";
}

$oModuleController = getController('module');
$oModule = ModuleModel::getModuleInstallClass('dmcadmin');
if ($oModule && $oModule->checkUpdate()) {
	$oModule->moduleUpdate();
	echo "dmcadmin module updated (login trigger).\n";
}

if (is_dir(__RX_BASEDIR__ . '/files/cache')) {
	FileHandler::removeDir(__RX_BASEDIR__ . '/files/cache');
}

getModel('dmcadmin');
$imported = dmcadminModel::importLegacySubTopBanners(false);
if ($imported) {
	echo 'imported sub top banners: ' . implode(', ', $imported) . PHP_EOL;
} else {
	echo "sub top banners: no new legacy import (already set or backup missing).\n";
}

echo "cache cleared.\n";
echo "Visit: /dmcadmin\n";
