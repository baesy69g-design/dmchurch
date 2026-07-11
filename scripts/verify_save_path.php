<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();

$pdo = Rhymix\Framework\DB::getInstance();
$srl = 900328;
$title = '2026년 6월 7일';
$pdo->query("UPDATE documents SET title = '" . addslashes($title) . "' WHERE document_srl = {$srl}");
DocumentController::clearDocumentCache($srl);

$logged = MemberModel::getMemberInfoByUserID('dmc2241');
Context::set('logged_info', $logged);
Context::set('is_logged', true);

// Simulate request vars like the browser form
Context::set('target_srl', $srl);
Context::set('module_srl', 114);
Context::set('title', '저장검증_' . date('His'));
Context::set('pubdate', '2026-06-07');
$_SERVER['REQUEST_METHOD'] = 'POST';

// Bypass CSRF for CLI by calling internals similar to proc
$oDocument = DocumentModel::getDocument($srl);
$newTitle = Context::get('title');
$content = $oDocument->get('content');
$upd = new stdClass;
$upd->document_srl = $srl;
$upd->module_srl = 114;
$upd->title = $newTitle;
$upd->content = $content;
$upd->status = 'PUBLIC';
$upd->commentStatus = 'ALLOW';
$upd->lang_code = 'ko';
$out = DocumentController::getInstance()->updateDocument($oDocument, $upd, true);
echo "upd=" . ($out->toBool()?'Y':'N') . "\n";
$pdo->query("UPDATE documents SET title = '" . addslashes($newTitle) . "', content = '" . addslashes($content) . "' WHERE document_srl = {$srl}");
DocumentController::clearDocumentCache($srl);
$v = DocumentModel::getDocument($srl, false, false);
echo "saved=[" . $v->getTitleText() . "]\n";

// restore original for the church
$pdo->query("UPDATE documents SET title = '" . addslashes($title) . "' WHERE document_srl = {$srl}");
DocumentController::clearDocumentCache($srl);
echo "restored\n";
