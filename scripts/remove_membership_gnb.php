<?php
/**
 * GNB에서 멤버십 대메뉴(및 하위 항목) 삭제
 */
define('__RX_BASEDIR__', dirname(__DIR__) . '/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$db = Rhymix\Framework\DB::getInstance();
$menu_srl = 48;

$rows = $db->query(
	'SELECT menu_item_srl, parent_srl, name FROM menu_item WHERE menu_srl = ? AND name = ?',
	[$menu_srl, '멤버십']
)->fetchAll(PDO::FETCH_ASSOC);

if (!$rows)
{
	echo "멤버십 메뉴 없음\n";
	exit(0);
}

$root_srls = [];
foreach ($rows as $row)
{
	$root_srls[] = (int)$row['menu_item_srl'];
}

$to_delete = $root_srls;
$queue = $root_srls;
while ($queue)
{
	$parent = array_shift($queue);
	$children = $db->query(
		'SELECT menu_item_srl FROM menu_item WHERE parent_srl = ?',
		[$parent]
	)->fetchAll(PDO::FETCH_COLUMN);
	foreach ($children as $child_srl)
	{
		$child_srl = (int)$child_srl;
		$to_delete[] = $child_srl;
		$queue[] = $child_srl;
	}
}

$to_delete = array_values(array_unique($to_delete));
foreach ($to_delete as $srl)
{
	$db->query('DELETE FROM menu_item WHERE menu_item_srl = ?', [$srl]);
	echo "deleted menu_item_srl={$srl}\n";
}

$oCache = CacheHandler::getInstance('object');
if ($oCache)
{
	$oCache->invalidateGroupKey('menu');
}

echo "done (" . count($to_delete) . " items)\n";
