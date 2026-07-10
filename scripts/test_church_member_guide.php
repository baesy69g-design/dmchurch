<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$oView = getView('church_member');
if (!$oView) {
    echo "getView failed\n";
    exit(1);
}
try {
    $oView->dispChurchMemberGuide();
    echo "OK template=" . Context::get('template_file') . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
