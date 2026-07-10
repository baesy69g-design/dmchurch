<?php
/**
 * 장학사업(p146) — 갤러리형 페이지 초기 반영
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$mid = 'p146';
if (!dmcadminModel::isTourPage($mid))
{
	fwrite(STDERR, "p146 is not a tour page mid\n");
	exit(1);
}

$description = "동명교회 장학위원회는 신앙과 인격을 갖춘 인재를 양성하기 위해 장학 사업을 진행하고 있습니다.\n\n"
	. "어려운 환경 속에서도 꿈을 키워가는 학생들에게 작은 도움이 되어, 하나님 나라의 동역자로 세워지기를 기도합니다.\n\n"
	. "장학 사업에 관심과 후원을 보내주시는 성도 여러분께 감사드립니다.";

$data = [
	'page_title' => dmcadminModel::TOUR_PAGE_MIDS[$mid] ?? '장학사업',
	'description' => $description,
	'photos' => [],
];

$existing = dmcadminModel::getTourPageData($mid);
if (!empty($existing['photos']))
{
	$data['photos'] = $existing['photos'];
}
if (trim((string)($existing['description'] ?? '')) !== '')
{
	$data['description'] = $existing['description'];
}

$output = dmcadminModel::publishTourPage($mid, $data);
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}

$menu_out = dmcadminModel::ensureMissionTourMenuItem($mid, -99970);
if (!$menu_out->toBool())
{
	fwrite(STDERR, $menu_out->getMessage() . "\n");
	exit(1);
}

echo 'published p146: ' . count($data['photos']) . " photos, menu synced\n";
