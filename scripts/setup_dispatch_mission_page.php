<?php
/**
 * 파송선교(p27) 페이지 모듈·메뉴 생성 및 초기 발행.
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$mid = dmcadminModel::DISPATCH_MISSION_PAGE_MID;
$title = dmcadminModel::getDispatchMissionPageLabel($mid);
$oDB = DB::getInstance();

$module_srl = dmcadminModel::getPageModuleSrl($mid);
if ($module_srl < 1)
{
	$module_srl = getNextSequence();
	$stub = '<div class="church-page-stub" style="padding:24px;line-height:1.7"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p>콘텐츠 준비 중입니다.</p></div>';
	$oDB->query(
		'INSERT INTO modules (module_srl, module, module_category_srl, menu_srl, site_srl, domain_srl, mid, layout_srl, mlayout_srl, use_mobile, skin, is_skin_fix, mskin, is_mskin_fix, browser_title, description, content, mcontent, is_default, open_rss, regdate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
		$module_srl,
		'page',
		0,
		48,
		0,
		-1,
		$mid,
		-1,
		-1,
		'N',
		'/USE_DEFAULT/',
		'N',
		'/USE_DEFAULT/',
		'N',
		$title,
		'',
		$stub,
		'',
		'N',
		'Y',
		date('YmdHis')
	);
	echo "created page module: $mid (srl=$module_srl)\n";
}
else
{
	echo "page module exists: $mid (srl=$module_srl)\n";
}

$menu_out = dmcadminModel::ensureDispatchMissionMenuItem(-10);
if (!$menu_out->toBool())
{
	fwrite(STDERR, $menu_out->getMessage() . "\n");
	exit(1);
}
echo "menu synced: $title\n";

$data = dmcadminModel::getDispatchMissionPageData();
if (trim($data['intro']) === '')
{
	$data['intro'] = '치앙라이에서 기치교회와 함께 복음을 전하는 파송 선교 소식입니다.';
}
if (trim($data['prayer_line']) === '')
{
	$data['prayer_line'] = '현지 성도들과 사역팀의 건강과 복음 전파를 위해 기도해 주세요.';
}
$data['page_title'] = $title;

$pub = dmcadminModel::publishDispatchMissionPage($data);
if (!$pub->toBool())
{
	fwrite(STDERR, $pub->getMessage() . "\n");
	exit(1);
}

$cheer_path = dmcadminModel::getDispatchCheerFilePath();
if (!is_file($cheer_path))
{
	dmcadminModel::saveDispatchCheerData(['comments' => []]);
	echo "cheer store created\n";
}

echo "published: $mid\n";

/* 해외선교 목록의 파송 카드 → p27 링크 반영을 위해 재발행 */
if (method_exists('dmcadminModel', 'publishOverseasMissionAll'))
{
	$om = dmcadminModel::publishOverseasMissionAll();
	if ($om->toBool())
	{
		echo "republished overseas list (p26) with dispatch link\n";
	}
	else
	{
		fwrite(STDERR, 'overseas republish: ' . $om->getMessage() . "\n");
	}
}

echo "admin: /dmcadmin?act=dispDmcMgrDispatchMissionEdit\n";
