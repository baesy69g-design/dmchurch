<?php
/**
 * 교회학교 부서 소개 4페이지 HTML 재발행 (school_pages.json 유지).
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

foreach (array_keys(dmcadminModel::SCHOOL_PAGE_MIDS) as $mid)
{
	$data = dmcadminModel::getSchoolPageData($mid);
	$output = dmcadminModel::publishSchoolPage($mid, $data);
	if (!$output->toBool())
	{
		fwrite(STDERR, $mid . ': ' . $output->getMessage() . "\n");
		exit(1);
	}
	echo 'published: ' . $mid . ' title=' . dmcadminModel::getSubPageTitleForMid($mid) . "\n";
}

echo "school pages republish done\n";
