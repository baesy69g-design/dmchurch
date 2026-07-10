<?php
/**
 * 사랑의 쌀나누기(p92) — 구홈피 사진·본문 반영 + 메뉴 등록
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$mid = 'p92';
if (!dmcadminModel::isTourPage($mid))
{
	fwrite(STDERR, "p92 is not a tour page mid\n");
	exit(1);
}

$upload_dir = dmcadminModel::getTourPageUploadDir($mid);
FileHandler::makeDir($upload_dir);

$search_bases = [
	__RX_BASEDIR__ . '/files/church/seed/rice_share',
	__RX_BASEDIR__ . '/../dmchurch.kr_260610/wysiwyg/PEG',
	'/tmp/rice_share_images',
];

$image_names = [
	'se2_15022467379496.jpg',
	'se2_15022467379792.jpg',
	'se2_15022467380253.jpg',
];

$photos = [];
foreach ($image_names as $i => $filename)
{
	$src = '';
	foreach ($search_bases as $base)
	{
		$candidate = rtrim($base, '/') . '/' . $filename;
		if (is_file($candidate))
		{
			$src = $candidate;
			break;
		}
	}
	if ($src === '')
	{
		echo "  [warn] image not found: {$filename}\n";
		continue;
	}
	$dest = $upload_dir . '/rice' . $i . '_' . $filename;
	if (!is_file($dest))
	{
		copy($src, $dest);
		@chmod($dest, 0644);
	}
	$photos[] = './files/church/tour/' . $mid . '/rice' . $i . '_' . $filename;
}

$description = "동명교회는 그리스도의 모습을 본받아 지역사회를 위해 희생하고 봉사하는 삶을 살기 위해 노력하고 있습니다.\n\n"
	. "이미 20여전 낙후된 지역사회의 특성을 감안하여 독거노인, 소년소녀 가장들에게 사랑의 쌀을 무상으로 나눠주는 사업을 이어오고 있으며, "
	. "답십리 전농동 지역의 동사무소의 협조를 받아 년 2회 매년 50가구씩 선정하여 지속적인 나눔의 삶을 실천하고 있습니다.";

$data = [
	'page_title' => dmcadminModel::TOUR_PAGE_MIDS[$mid] ?? '사랑의 쌀나누기',
	'description' => $description,
	'photos' => $photos,
];

$output = dmcadminModel::publishTourPage($mid, $data);
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}

$menu_out = dmcadminModel::ensureMissionTourMenuItem('p92', -99980);
if (!$menu_out->toBool())
{
	fwrite(STDERR, $menu_out->getMessage() . "\n");
	exit(1);
}

echo 'published p92: ' . count($photos) . " photos, menu synced\n";
