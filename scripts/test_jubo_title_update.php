<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();

$pdo = Rhymix\Framework\DB::getInstance();
echo "lang=" . Context::getLangType() . "\n";

$stmt = $pdo->query('SELECT document_srl, title, module_srl, lang_code FROM documents WHERE module_srl = 114 ORDER BY document_srl DESC LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
	echo "srl={$row->document_srl} lang=[" . ($row->lang_code ?? '') . "] title=[" . $row->title . "]\n";
}

$row = $pdo->query('SELECT document_srl, title, lang_code, content FROM documents WHERE module_srl = 114 ORDER BY document_srl DESC LIMIT 1')->fetch(PDO::FETCH_OBJ);
$srl = (int)$row->document_srl;
$old = $row->title;
$new = 'EDITTEST_' . date('His');

$logged = MemberModel::getMemberInfoByUserID('dmc2241');
Context::set('logged_info', $logged);
Context::set('is_logged', true);

$oDocument = DocumentModel::getDocument($srl);
echo "doc_lang=[" . $oDocument->get('lang_code') . "] ctx_lang=[" . Context::getLangType() . "] mismatch=" . (($oDocument->get('lang_code') !== Context::getLangType()) ? 'Y' : 'N') . "\n";
echo "src_document_srl_prop=" . var_export(isset($oDocument->document_srl) ? $oDocument->document_srl : 'NO', true) . "\n";

$upd = new stdClass;
$upd->document_srl = $srl;
$upd->module_srl = (int)$oDocument->get('module_srl');
$upd->title = $new;
$upd->content = $oDocument->get('content');
$upd->status = $oDocument->get('status') ?: 'PUBLIC';
$upd->comment_status = $oDocument->get('comment_status') ?: 'ALLOW';
$upd->commentStatus = 'ALLOW';
$upd->lang_code = $oDocument->get('lang_code') ?: Context::getLangType();

$out = DocumentController::getInstance()->updateDocument($oDocument, $upd, true);
echo "update_ok=" . ($out->toBool() ? 'Y' : 'N') . " err=" . $out->getError() . " msg=" . $out->getMessage() . "\n";
DocumentController::clearDocumentCache($srl);

$row2 = $pdo->query('SELECT title FROM documents WHERE document_srl = ' . $srl)->fetch(PDO::FETCH_OBJ);
echo "db_after=[{$row2->title}]\n";

// Direct SQL update test
$pdo->query('UPDATE documents SET title = ' . $pdo->quote($new . '_SQL') . ' WHERE document_srl = ' . $srl);
$row3 = $pdo->query('SELECT title FROM documents WHERE document_srl = ' . $srl)->fetch(PDO::FETCH_OBJ);
echo "db_sql=[{$row3->title}]\n";

// restore
$pdo->query('UPDATE documents SET title = ' . $pdo->quote($old) . ' WHERE document_srl = ' . $srl);
DocumentController::clearDocumentCache($srl);
echo "restored to [$old]\n";
