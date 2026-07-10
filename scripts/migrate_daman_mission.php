<?php
/**
 * 구홈피 특수선교(p91) → 국내선교 담안선교회 상세 항목 이전
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$src_img = __RX_BASEDIR__ . '/files/church/page/p91/photos/se2_15022453675750.jpg';
$dest_dir = dmcadminModel::getDomesticMissionUploadDir('p25');
$dest_name = 'daman_se2_15022453675750.jpg';
$dest_path = $dest_dir . '/' . $dest_name;

if (!is_file($src_img))
{
	fwrite(STDERR, "source image not found: {$src_img}\n");
	exit(1);
}
FileHandler::makeDir($dest_dir);
if (!is_file($dest_path))
{
	if (!copy($src_img, $dest_path))
	{
		fwrite(STDERR, "copy failed\n");
		exit(1);
	}
	@chmod($dest_path, 0644);
}
$photo_url = './files/church/domestic/p25/' . $dest_name;

$sub_body = <<<'TXT'
본 교회 이경희 은퇴장로가 재단 이사장인 담안선교회는 "갇힌 자에게 복음을, 풀린자에게 사랑을"이라는 기독교적 사명을 바탕으로 설립된 단체로서 무의탁 출소자들에게 숙식을 무료로 제공하여 사회에 대한 충격을 완화시키고, 부단한 신앙지도로 정서를 순화하며, 취업을 위한 기술 습득 지원, 직업알선을 통해 정상적으로 사회에 복귀할 수 있는 기반을 조성하려는 노력을 꾸준히 해 왔다. 재범을 미연에 방지하고 사회와 출소자간의 편견 불식시킴으로서 다른 출소자들이나 출소예정자들에게 사회를 긍정적으로 바라볼 수 있는 기회를 부여하고자 하며, 아울러 정상적인 사회인으로 복귀된 모범을 보여줌으로 인하여 절망에 빠져있는 대다수 출소자들에게 희망적인 삶을 살아갈 수 있는 갱생보호 시설의 모델을 제시, 조기정착을 유도하는 것을 목적으로 한다.
http://daman.co.kr/
TXT;

$data = dmcadminModel::getDomesticMissionData();
$found = false;
$sub_mid = 'p253';

foreach ($data['items'] as &$item)
{
	$name = trim((string)($item['name'] ?? ''));
	$id = trim((string)($item['id'] ?? ''));
	if ($name !== '담안선교회' && $id !== 'dm_e15ec6e1f9' && $id !== 'dm_' . substr(md5('org_담안선교회'), 0, 10))
	{
		continue;
	}
	$found = true;
	$item['category'] = 'org';
	$item['has_sub'] = true;
	$item['sub_mid'] = $sub_mid;
	$item['sub_photo'] = $photo_url;
	$item['sub_body'] = $sub_body;
	$item['thumb'] = '';
	break;
}
unset($item);

if (!$found)
{
	fwrite(STDERR, "담안선교회 항목을 찾지 못했습니다.\n");
	exit(1);
}

$data['next_sub_frame'] = max((int)($data['next_sub_frame'] ?? 253), 254);
$data['updated'] = date('Y-m-d H:i:s');

if (!dmcadminModel::saveDomesticMissionData($data))
{
	fwrite(STDERR, "JSON save failed\n");
	exit(1);
}
dmcadminModel::fixDomesticMissionFilePermissions();

$output = dmcadminModel::publishDomesticMissionAll();
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}

echo "migrated 담안선교회 -> {$sub_mid}\n";
