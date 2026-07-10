<?php
/**
 * 동키데이 페이지 HTML 재발행 (dongkeyday_page.json 유지).
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$data = dmcadminModel::getDongkeydayPageData();
$output = dmcadminModel::publishDongkeydayPage($data);
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}

echo 'republished: ' . dmcadminModel::DONGKEYDAY_PAGE_MID . "\n";
