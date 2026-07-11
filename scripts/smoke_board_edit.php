<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();
$o = ModuleModel::getModuleActionXml('church_write');
$acts = array_keys((array)($o->action ?? []));
echo 'has_update=' . (in_array('procChurchWriteUpdateDocument', $acts, true) ? 'Y' : 'N') . PHP_EOL;
echo 'has_get=' . (in_array('dispChurchWriteGetDocument', $acts, true) ? 'Y' : 'N') . PHP_EOL;
$oDB = Rhymix\Framework\DB::getInstance();
$row = $oDB->query('SELECT document_srl FROM documents WHERE module_srl=114 ORDER BY document_srl DESC LIMIT 1')->fetch(PDO::FETCH_OBJ);
echo 'jubo_srl=' . ($row->document_srl ?? 0) . PHP_EOL;
if ($row)
{
	$doc = DocumentModel::getDocument((int)$row->document_srl);
	$f = church_writeModel::extractEditFields($doc);
	echo 'title=' . ($f['title'] ?? '') . PHP_EOL;
	echo 'pubdate=' . ($f['pubdate'] ?? '') . PHP_EOL;
	echo 'news=' . (!empty($f['news_image_url']) ? 'Y' : 'N') . PHP_EOL;
}
$row2 = $oDB->query('SELECT document_srl FROM documents WHERE module_srl=110 ORDER BY document_srl DESC LIMIT 1')->fetch(PDO::FETCH_OBJ);
echo 'sermon_srl=' . ($row2->document_srl ?? 0) . PHP_EOL;
if ($row2)
{
	$doc = DocumentModel::getDocument((int)$row2->document_srl);
	$f = church_writeModel::extractEditFields($doc);
	echo 'sermon_title=' . ($f['title'] ?? '') . PHP_EOL;
	echo 'youtube=' . ($f['youtube_url'] ?? '') . PHP_EOL;
	echo 'speaker=' . ($f['speaker'] ?? '') . PHP_EOL;
}
