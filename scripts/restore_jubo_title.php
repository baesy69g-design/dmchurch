<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();
$pdo = Rhymix\Framework\DB::getInstance();
$srl = 900328;
$old = '2026년 6월 7일';
$pdo->query("UPDATE documents SET title = '" . addslashes($old) . "' WHERE document_srl = " . $srl);
DocumentController::clearDocumentCache($srl);
$row = $pdo->query('SELECT title FROM documents WHERE document_srl = ' . $srl)->fetch(PDO::FETCH_OBJ);
echo "restored=[{$row->title}]\n";
