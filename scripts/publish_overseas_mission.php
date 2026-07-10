<?php
/**
 * 해외선교 페이지 반영.
 * - 기본: overseas_mission.json 유지, HTML만 재발행
 * - --force: 교회 표(구분·국가·선교사) 시드로 JSON 초기화 후 발행
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$force = in_array('--force', $argv ?? [], true);
$path = dmcadminModel::getOverseasMissionFilePath();

if (is_file($path) && !$force)
{
	dmcadminModel::fixDomesticMissionFilePermissions($path);
	$output = dmcadminModel::publishOverseasMissionAll();
	if (!$output->toBool())
	{
		fwrite(STDERR, $output->getMessage() . "\n");
		exit(1);
	}
	$data = dmcadminModel::getOverseasMissionData();
	echo 'republish only: ' . count((array)($data['items'] ?? [])) . " items (JSON preserved)\n";
	exit(0);
}

$upload_dir = dmcadminModel::getOverseasMissionUploadDir('p26');
FileHandler::makeDir($upload_dir);

$search_bases = [
	__RX_BASEDIR__ . '/files/church/page/p26/photos',
	__RX_BASEDIR__ . '/files/church/overseas/p26',
];

function om_copy_image(string $filename, string $dest_dir, array $search_bases): string
{
	$filename = basename($filename);
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
		return '';
	}
	$dest = rtrim($dest_dir, '/') . '/' . $filename;
	if (!is_file($dest))
	{
		copy($src, $dest);
		@chmod($dest, 0644);
	}
	return './files/church/overseas/p26/' . $filename;
}

$tokyo_body = "안녕하세요? 일본 동경에 있는 이혜숙 선교사입니다. 초대교회를 꿈꾸며 사랑의 교회를 개척하여 6년째를 맞이했습니다. 한국교회의 기도와 후원으로 우상의 도시 동경에 서 있을 수 있었습니다. 진심으로 감사드립니다. 사랑의 교회는 다 영적인 질병을 앓고 있는 분들이 모여 있습니다. 이곳 사랑의 교회가 베데스다 연못이 되어 주님을 만나고 치유와 회복이 일어나는 축복의 동산이 되도록 기도해주시길 부탁드립니다.";
$phil_body = "한마음크리스찬교회(OCF)와 마릴라오교회 그리고 바공바리오어린이교회가 지역을 살리고 생명을 살리는 하나님께서 기뻐하시는 교회로, 모슬렘지역인 민다나오 OCF 파가디안교회와 보니파쇼우교회가 모슬렘지역을 살리는 풍성한 생명을 낳아 기르는 신앙동공체가 되기를 기도해주십시오.\n-ONE HEART CHRISTIAN FELLOWSHIP(한마음 크리스찬 교회)\n-OCF 바공바리오 어린이 사역\n-OCF 마릴라오 한마음 크리스찬 교회\n-민다나오 OCF 파가디안교회\n-민다나오 보니파쇼우 교회\n-박영순 선교사님의 보건사역";
$cambodia_body = "캄보디아에는 불교국가이기에, 기도제목 중에 가장 중요한 사항 하나는, 복음의 권능을 증거, 인격의 향기를 풍기며, 능력의 역사를 나타낸다는 기도제목을 정해 주었습니다. 폭탄과 대포 총알같은 무기 대신 말씀의 능력으로 다니며 찾아 있는 이 시대에 다시 부흥되기를 원하시고, 예비하신 하나님의 말씀으로 살 수 있도록 기도해 주세요.\n\n-한마음 선교협의회\n-하나 사랑의교회";

$seed = [
	[
		'id' => 'om_park',
		'category' => 'dispatch',
		'country' => '태국',
		'missionary_name' => '박미경 선교사',
		'name' => '치앙라이',
		'order' => 1,
	],
	['id' => 'om_kim_tw', 'category' => 'support', 'country' => '대만', 'missionary_name' => '김성훈 선교사', 'name' => '', 'order' => 1],
	[
		'id' => 'om_tokyo',
		'category' => 'support',
		'country' => '일본',
		'missionary_name' => '이혜숙 선교사',
		'name' => '동경 사랑의교회',
		'img' => 'gw.15022446631447.jpg',
		'sub_mid' => 'p261',
		'sub_body' => $tokyo_body,
		'order' => 2,
	],
	[
		'id' => 'om_philippines',
		'category' => 'support',
		'country' => '필리핀',
		'missionary_name' => '임홍재 선교사',
		'name' => '',
		'img' => 'gw.15006383646011.jpg',
		'sub_mid' => 'p262',
		'sub_body' => $phil_body,
		'order' => 3,
	],
	['id' => 'om_new_emmanuel', 'category' => 'support', 'country' => '필리핀', 'missionary_name' => '', 'name' => '뉴임마누엘교회', 'order' => 4],
	[
		'id' => 'om_cambodia',
		'category' => 'support',
		'country' => '캄보디아',
		'missionary_name' => '서원교 선교사',
		'name' => '',
		'img' => 'gw.15006387866976.png',
		'sub_mid' => 'p263',
		'sub_body' => $cambodia_body,
		'order' => 5,
	],
	['id' => 'om_th_yang', 'category' => 'support', 'country' => '태국', 'missionary_name' => '양정금 선교사', 'name' => '', 'order' => 6],
	['id' => 'om_tr_kimhc', 'category' => 'support', 'country' => '튀르키예', 'missionary_name' => '김희철 선교사', 'name' => '', 'order' => 7],
	['id' => 'om_tr_heo', 'category' => 'support', 'country' => '튀르키예', 'missionary_name' => '허수성 선교사', 'name' => '', 'order' => 8],
	['id' => 'om_tr_kim', 'category' => 'support', 'country' => '튀르키예', 'missionary_name' => '김명섭 선교사', 'name' => '', 'order' => 9],
	['id' => 'om_png', 'category' => 'support', 'country' => '파푸아뉴기니', 'missionary_name' => '김광현 선교사', 'name' => '', 'order' => 10],
];

$items = [];
foreach ($seed as $row)
{
	$has_sub = !empty($row['img']);
	$photo = $has_sub ? om_copy_image($row['img'], $upload_dir, $search_bases) : '';
	$items[] = [
		'id' => $row['id'],
		'category' => $row['category'],
		'country' => $row['country'],
		'name' => $row['name'],
		'missionary_name' => $row['missionary_name'],
		'thumb' => '',
		'has_sub' => $has_sub,
		'sub_mid' => $has_sub ? ($row['sub_mid'] ?? '') : '',
		'sub_photo' => $photo,
		'sub_body' => $has_sub ? ($row['sub_body'] ?? '') : '',
		'order' => $row['order'],
	];
}

$data = [
	'page_title' => '해외선교',
	'next_sub_frame' => 264,
	'items' => $items,
	'updated' => date('Y-m-d H:i:s'),
];

if (!dmcadminModel::saveOverseasMissionData($data))
{
	fwrite(STDERR, "save json failed\n");
	exit(1);
}

$output = dmcadminModel::publishOverseasMissionAll();
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}

echo 'seed force published: ' . count($items) . " items on p26\n";
