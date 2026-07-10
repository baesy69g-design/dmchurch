<?php
/**
 * 교회학교 소개 페이지 browser_title 한글 복구.
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$titles = [
	'p109' => '유치부 소개',
	'p112' => '아동부 소개',
	'p115' => '청소년부 소개',
	'p118' => '청년부 소개',
];

$oDB = DB::getInstance();
foreach ($titles as $mid => $title)
{
	$args = new stdClass();
	$args->browser_title = $title;
	$args->mid = $mid;
	$output = executeQuery('module.updateModule', $args);
	if (!$output->toBool())
	{
		fwrite(STDERR, $mid . ': ' . $output->getMessage() . "\n");
		exit(1);
	}
	echo "browser_title: $mid => $title\n";
}

echo "school browser titles done\n";
