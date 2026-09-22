<?php
/**
 * 파송선교(p27) 페이지 재발행.
 * 사용: php scripts/publish_dispatch_mission.php
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$pub = dmcadminModel::publishDispatchMissionPage();
if (!$pub->toBool())
{
	fwrite(STDERR, $pub->getMessage() . "\n");
	exit(1);
}
echo "published: " . dmcadminModel::DISPATCH_MISSION_PAGE_MID . "\n";
