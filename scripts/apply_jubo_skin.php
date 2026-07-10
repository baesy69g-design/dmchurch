<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$oDB = Rhymix\Framework\DB::getInstance();

echo "== BEFORE ==\n";
foreach ([114, 122] as $msrl)
{
	$row = $oDB->query('SELECT module_srl, module, mid, skin, is_skin_fix FROM modules WHERE module_srl = ?', $msrl)->fetch(\PDO::FETCH_OBJ);
	echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
	$ev = $oDB->query('SELECT name, value FROM module_extra_vars WHERE module_srl = ?', $msrl)->fetchAll(\PDO::FETCH_OBJ);
	echo "  extra: " . json_encode($ev, JSON_UNESCAPED_UNICODE) . "\n";
}

// 122의 list_count 참고
$lc_row = $oDB->query("SELECT value FROM module_extra_vars WHERE module_srl = 122 AND name = 'list_count'")->fetch(\PDO::FETCH_OBJ);
$list_count = $lc_row && $lc_row->value ? (int)$lc_row->value : 20;

// 114 -> picturegallery 스킨 고정
$oDB->query("UPDATE modules SET skin = 'picturegallery', is_skin_fix = 'Y' WHERE module_srl = 114");

// list_count 동기화
$oDB->query("DELETE FROM module_extra_vars WHERE module_srl = 114 AND name = 'list_count'");
$oDB->query("INSERT INTO module_extra_vars (module_srl, name, value) VALUES (114, 'list_count', ?)", (string)$list_count);

echo "\n== AFTER ==\n";
$row = $oDB->query('SELECT module_srl, module, mid, skin, is_skin_fix FROM modules WHERE module_srl = 114')->fetch(\PDO::FETCH_OBJ);
echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
$ev = $oDB->query('SELECT name, value FROM module_extra_vars WHERE module_srl = 114')->fetchAll(\PDO::FETCH_OBJ);
echo "  extra: " . json_encode($ev, JSON_UNESCAPED_UNICODE) . "\n";

echo "DONE list_count={$list_count}\n";
