<?php
/**
 * 동키데이(p93) 페이지 모듈·메뉴 생성 및 초기 발행.
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$mid = dmcadminModel::DONGKEYDAY_PAGE_MID;
$title = dmcadminModel::getDongkeydayPageLabel($mid);
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

$menu_out = dmcadminModel::ensureDongkeydayMenuItem(-99960);
if (!$menu_out->toBool())
{
	fwrite(STDERR, $menu_out->getMessage() . "\n");
	exit(1);
}
echo "menu synced: $title\n";

$data = dmcadminModel::getDongkeydayPageData();
if (trim($data['intro']) === '')
{
	$data['intro'] = "동명교회 동키데이에 오신 것을 환영합니다.\n아이들과 함께하는 특별한 봉사·나눔의 날입니다.";
}
$data['page_title'] = $title;

$pub = dmcadminModel::publishDongkeydayPage($data);
if (!$pub->toBool())
{
	fwrite(STDERR, $pub->getMessage() . "\n");
	exit(1);
}

echo "published: $mid\n";
echo "admin: /dmcadmin?act=dispDmcMgrDongkeydayPageEdit\n";
