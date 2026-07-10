<?php
/**
 * 국내선교 페이지 반영.
 * - 기본: domestic_mission.json 그대로 두고 HTML만 재발행
 * - --force: 구홈피 시드로 JSON 전체 초기화 후 발행 (dmcadmin 편집 내용 삭제됨)
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$force = in_array('--force', $argv ?? [], true);
$path = dmcadminModel::getDomesticMissionFilePath();

if (is_file($path) && !$force)
{
	dmcadminModel::fixDomesticMissionFilePermissions($path);
	$output = dmcadminModel::publishDomesticMissionAll();
	if (!$output->toBool())
	{
		fwrite(STDERR, $output->getMessage() . "\n");
		exit(1);
	}
	$data = dmcadminModel::getDomesticMissionData();
	echo 'republish only: ' . count((array)($data['items'] ?? [])) . " items (JSON preserved)\n";
	exit(0);
}

$upload_dir = dmcadminModel::getDomesticMissionUploadDir('p25');
FileHandler::makeDir($upload_dir);

$search_bases = [
	__RX_BASEDIR__ . '/files/church/page/p25/photos',
	__RX_BASEDIR__ . '/files/church/domestic/p25',
];

function dm_copy_image(string $filename, string $dest_dir, array $search_bases): string
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
	return './files/church/domestic/p25/' . $filename;
}

$gangbuk_img = dm_copy_image('gw.15016571876445.jpg', $upload_dir, $search_bases);
$ccc_img = dm_copy_image('gw.15016536980103.jpg', $upload_dir, $search_bases);
$ilsan_img = dm_copy_image('gw.15016563182930.jpg', $upload_dir, $search_bases);

$ccc_body = "CCC는 'Movement Everywhere' (어느 곳에서나 영적 운동을 일으키기)라는 비전을 가지고 성령의 능력으로 사람들에게 그리스도를 전하고 믿음을 훈련시키고, 이들이 다른 사람들을 전도하고 제자화 할 수 있도록 파송하여, 지상 명령을 성취하도록 돕는 단체입니다.\nhttp://nh.kccc.org/";
$gangbuk_body = "2015년 10월 19일 평양노회로 부터 분립되어 백주년기념관에서 분립예배를 드린 평양 남노회는 강서,강남,강북,부산노회가 있으며 동명교회는 강북시찰 소속이다\nhttp://pynn.co.kr";

$church_names = [
	'평양남노회 강북시찰',
	'가납소망교회',
	'감동의교회',
	'대은교회',
	'더행복한교회',
	'샛별교회',
	'인제승전교회',
	'초대교회(공릉동)',
	'일산살림교회',
];

// 강북시찰선교회는 평양남노회 강북시찰(상세)과 중복 — 시드에서 제외
$org_names = [
	'다일공동체',
	'담안선교회',
	'동대문구 교구협의회',
	'유두고선교회',
	'한국대학생선교회(CCC)',
];

$items = [];
$order = 1;
foreach ($church_names as $name)
{
	$row = [
		'id' => 'dm_' . substr(md5('church_' . $name), 0, 10),
		'category' => 'church',
		'name' => $name,
		'thumb' => '',
		'has_sub' => false,
		'sub_mid' => '',
		'sub_photo' => '',
		'sub_body' => '',
		'order' => $order++,
	];
	if ($name === '평양남노회 강북시찰')
	{
		$row['id'] = 'dm_gangbuk';
		$row['has_sub'] = true;
		$row['sub_mid'] = 'p251';
		$row['sub_photo'] = $gangbuk_img;
		$row['sub_body'] = $gangbuk_body;
	}
	elseif ($name === '일산살림교회')
	{
		$row['id'] = 'dm_ilsan';
		$row['thumb'] = $ilsan_img;
	}
	$items[] = $row;
}

$order = 1;
foreach ($org_names as $name)
{
	$row = [
		'id' => 'dm_' . substr(md5('org_' . $name), 0, 10),
		'category' => 'org',
		'name' => $name,
		'thumb' => '',
		'has_sub' => false,
		'sub_mid' => '',
		'sub_photo' => '',
		'sub_body' => '',
		'order' => $order++,
	];
	if ($name === '한국대학생선교회(CCC)')
	{
		$row['id'] = 'dm_ccc';
		$row['name'] = 'CCC';
		$row['has_sub'] = true;
		$row['sub_mid'] = 'p252';
		$row['sub_photo'] = $ccc_img;
		$row['sub_body'] = $ccc_body;
	}
	$items[] = $row;
}

$data = [
	'page_title' => '국내선교',
	'next_sub_frame' => 253,
	'items' => $items,
	'updated' => date('Y-m-d H:i:s'),
];

if (!dmcadminModel::saveDomesticMissionData($data))
{
	fwrite(STDERR, "save json failed\n");
	exit(1);
}

$output = dmcadminModel::publishDomesticMissionAll();
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}

echo "seed force published: " . count($items) . " items on p25\n";
