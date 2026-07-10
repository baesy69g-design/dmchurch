<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$to = $argv[1] ?? 'baesy69@hotmail.com';
getModel('church_member');
$member = MemberModel::getMemberInfoByUserID('baesy69');
if (!$member) { echo "no member\n"; exit(1); }

echo "Sending recover mail to: $to\n";
church_memberModel::savePendingEmail((int)$member->member_srl, $to);
$auth_key = church_memberModel::createAuthMail((int)$member->member_srl, $member->user_id, church_memberModel::AUTH_RECOVER);
$url = church_memberModel::generateAuthUrl('dispChurchRecoverReset', (int)$member->member_srl, $auth_key);
$body = sprintf('<p>비밀번호 재설정 테스트</p><p><a href="%s">새 비밀번호 설정</a></p>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
$sent = church_memberModel::sendMail($to, '[동명교회] 비밀번호 재설정', $body);
echo 'sendMail: ' . ($sent ? 'OK' : 'FAIL') . "\n";
echo "url: $url\n";
if (!$sent && isset($GLOBALS['oMail'])) print_r($GLOBALS['oMail']->errors ?? []);
