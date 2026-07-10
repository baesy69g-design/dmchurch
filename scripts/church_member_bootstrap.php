<?php
/**
 * church_member 모듈 설치 + church_member_onboard 애드온 활성화
 * Run: docker exec church-rhymix php .../scripts/church_member_bootstrap.php
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$oInstallController = getController('install');
$output = $oInstallController->installModule('church_member', __RX_BASEDIR__ . '/modules/church_member');
if (!$output->toBool()) {
	echo 'installModule failed: ' . $output->getMessage() . PHP_EOL;
	exit(1);
}
echo "church_member module installed." . PHP_EOL;

$oModuleController = getController('module');
$oModuleController->insertTrigger('member.doLogin', 'church_member', 'controller', 'triggerMemberDoLoginAfter', 'after');
$oModuleController->insertTrigger('member.procMemberModifyInfo', 'church_member', 'controller', 'triggerMemberModifyInfoBefore', 'before');
$oModuleController->insertTrigger('member.procMemberModifyInfo', 'church_member', 'controller', 'triggerMemberModifyInfoAfter', 'after');
echo "member modify triggers registered." . PHP_EOL;

$now = date('YmdHis');
$db = DB::getInstance();
foreach (['church_member_onboard'] as $addon) {
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
echo "addon church_member_onboard activated." . PHP_EOL;

$oAddonController = getController('addon');
$oAddonController->makeCacheFile(0, 'pc', 'site');
$oAddonController->makeCacheFile(0, 'mobile', 'site');
echo "addon cache rebuilt." . PHP_EOL;

if (is_dir(__RX_BASEDIR__ . '/files/cache')) {
	FileHandler::removeDir(__RX_BASEDIR__ . '/files/cache');
}
@mkdir(__RX_BASEDIR__ . '/files/cache/template', 0775, true);
@chown(__RX_BASEDIR__ . '/files/cache', 'nobody');
@chgrp(__RX_BASEDIR__ . '/files/cache', 'nogroup');
@chmod(__RX_BASEDIR__ . '/files/cache', 0775);
echo "cache cleared." . PHP_EOL;
