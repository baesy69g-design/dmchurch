<?php
/** 기존 tour JSON으로 p92·p147 HTML만 재발행 (설명 스타일 클래스 반영) */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

foreach (['p92', 'p147'] as $mid)
{
	if (!dmcadminModel::isTourPage($mid))
	{
		continue;
	}
	$data = dmcadminModel::getTourPageData($mid);
	$out = dmcadminModel::publishTourPage($mid, $data);
	if (!$out->toBool())
	{
		fwrite(STDERR, $mid . ': ' . $out->getMessage() . "\n");
		exit(1);
	}
	echo "republished {$mid}\n";
}
