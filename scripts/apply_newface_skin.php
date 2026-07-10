<?php
/**
 * 새가족소개(newface) 게시판 picturegallery 스킨 적용
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$oDB = Rhymix\Framework\DB::getInstance();
foreach (['newface', 'picture'] as $mid) {
	$oDB->query(
		"UPDATE modules SET skin = 'picturegallery', is_skin_fix = 'Y' WHERE mid = ? AND module = 'board'",
		$mid
	);
	echo "skin applied: {$mid}\n";
}

echo "done\n";
