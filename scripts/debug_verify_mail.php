<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$db = DB::getInstance();
$count = $db->query('SELECT COUNT(*) AS c FROM member_auth_mail')->fetchAll(PDO::FETCH_ASSOC);
echo 'auth_mail rows: ' . ($count[0]['c'] ?? '?') . PHP_EOL;

$xml = ModuleModel::getModuleActionXml('church_member');
echo 'church_member actions: ' . ($xml ? count((array)$xml->action) : 0) . PHP_EOL;

getModel('church_member');
$member = MemberModel::getMemberInfoByUserID('baesy69');
if ($member) {
    echo 'baesy69 needs verify: ' . (church_memberModel::needsEmailVerification($member) ? 'Y' : 'N') . PHP_EOL;
    echo 'baesy69 email: ' . $member->email_address . PHP_EOL;
    echo 'pending: ' . church_memberModel::getPendingEmail($member) . PHP_EOL;
}

$oMail = new Rhymix\Framework\Mail();
$oMail->setSubject('[동명교회] verify flow test');
$oMail->setBody('<p>test</p>');
$oMail->addTo('dmchurch1972@gmail.com');
echo 'mail send: ' . ($oMail->send() ? 'OK' : 'FAIL') . PHP_EOL;
if (!$oMail->send()) {
    print_r($oMail->errors ?? []);
}
