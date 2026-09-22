<?php
/**
 * 파송선교 게시판 생성: dispatch_letter, dispatch_video
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$oDB = Rhymix\Framework\DB::getInstance();
$oModuleController = getController('module');

$boards = [
	[
		'mid' => 'dispatch_letter',
		'title' => '선교편지',
		'skin' => 'picturegallery',
	],
	[
		'mid' => 'dispatch_video',
		'title' => '치앙라이 동영상',
		'skin' => 'sermongallery',
	],
];

foreach ($boards as $b)
{
	$mid = $b['mid'];
	$info = ModuleModel::getModuleInfoByMid($mid);
	if ($info && !empty($info->module_srl))
	{
		$srl = (int)$info->module_srl;
		$oDB->query(
			'UPDATE modules SET browser_title = ?, skin = ?, is_skin_fix = ? WHERE module_srl = ?',
			$b['title'],
			$b['skin'],
			'Y',
			$srl
		);
		echo "exists: $mid (srl=$srl) skin={$b['skin']}\n";
	}
	else
	{
		$args = new stdClass;
		$args->module = 'board';
		$args->mid = $mid;
		$args->browser_title = $b['title'];
		$args->site_srl = 0;
		$args->layout_srl = -1;
		$args->mlayout_srl = -1;
		$args->skin = $b['skin'];
		$args->is_skin_fix = 'Y';
		$args->mskin = $b['skin'];
		$args->is_mskin_fix = 'Y';
		$args->use_mobile = 'N';
		$args->menu_srl = 48;
		$output = $oModuleController->insertModule($args);
		if (!$output->toBool())
		{
			fwrite(STDERR, "insertModule $mid failed: " . $output->getMessage() . "\n");
			exit(1);
		}
		$srl = (int)$output->get('module_srl');
		if ($srl < 1)
		{
			$info2 = ModuleModel::getModuleInfoByMid($mid);
			$srl = $info2 ? (int)$info2->module_srl : 0;
		}
		echo "created: $mid (srl=$srl)\n";
	}

	$srl = (int)(ModuleModel::getModuleInfoByMid($mid)->module_srl ?? 0);
	if ($srl < 1)
	{
		continue;
	}

	/* 공개 열람 + 관리자 그룹 글쓰기 (실제 쓰기는 church_write canWriteMissionBoard 로 허용) */
	$oDB->query('DELETE FROM module_grants WHERE module_srl = ? AND name IN (?,?,?,?,?)', $srl, 'access', 'list', 'view', 'write_document', 'write_comment');
	foreach (['access', 'list', 'view'] as $grant)
	{
		$oDB->query('INSERT INTO module_grants (module_srl, name, group_srl) VALUES (?,?,?)', $srl, $grant, 0);
	}
	$oDB->query('INSERT INTO module_grants (module_srl, name, group_srl) VALUES (?,?,?)', $srl, 'write_document', 2);
	$oDB->query('INSERT INTO module_grants (module_srl, name, group_srl) VALUES (?,?,?)', $srl, 'write_comment', 2);

	$oDB->query("DELETE FROM module_extra_vars WHERE module_srl = ? AND name = 'list_count'", $srl);
	$oDB->query("INSERT INTO module_extra_vars (module_srl, name, value) VALUES (?, 'list_count', ?)", $srl, '20');
	echo "grants ok: $mid\n";
}

echo "DONE\n";
