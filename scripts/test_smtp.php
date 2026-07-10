<?php
/**
 * SMTP 테스트 — docker exec church-rhymix php .../scripts/test_smtp.php recipient@example.com
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$to = $argv[1] ?? '';
if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "Usage: php test_smtp.php recipient@example.com\n";
    exit(1);
}

$oMail = new Rhymix\Framework\Mail();
$oMail->setSubject('[동명교회] SMTP 테스트');
$oMail->setBody('<p>SMTP 설정 테스트 메일입니다.</p>');
$oMail->addTo($to);
$result = $oMail->send();

if ($result) {
    echo "OK sent to {$to}\n";
    exit(0);
}

echo "FAIL\n";
foreach ($oMail->errors as $err) {
    echo $err . "\n";
}
exit(1);
