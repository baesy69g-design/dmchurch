<?php
/** domestic_mission.json 권한 수정 + 좀비 항목(dm_89729b1118) 제거 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$path = dmcadminModel::getDomesticMissionFilePath();
$data = dmcadminModel::getDomesticMissionData();
$before = count((array)($data['items'] ?? []));
$data['items'] = array_values(array_filter(
	(array)($data['items'] ?? []),
	static function ($item) {
		if (!is_array($item))
		{
			return false;
		}
		$id = trim((string)($item['id'] ?? ''));
		$name = trim((string)($item['name'] ?? ''));
		if ($id === 'dm_89729b1118')
		{
			return false;
		}
		if ($name === '강북시찰선교회')
		{
			return false;
		}
		return true;
	}
));
$data['updated'] = date('Y-m-d H:i:s');

if (!dmcadminModel::saveDomesticMissionData($data))
{
	fwrite(STDERR, "save failed\n");
	exit(1);
}
dmcadminModel::fixDomesticMissionFilePermissions($path);
$output = dmcadminModel::publishDomesticMissionAll();
if (!$output->toBool())
{
	fwrite(STDERR, $output->getMessage() . "\n");
	exit(1);
}
$after = count($data['items']);
echo "fixed permissions; items {$before} -> {$after}\n";
