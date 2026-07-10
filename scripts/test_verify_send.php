<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

getModel('church_member');
$member = MemberModel::getMemberInfoByUserID('baesy69');
if (!$member) {
    echo "member not found\n";
    exit(1);
}

$testEmail = $argv[1] ?? 'dmchurch1972@gmail.com';
echo "member_srl={$member->member_srl} test_email=$testEmail\n";

try {
    church_memberModel::savePendingEmail((int)$member->member_srl, $testEmail);
    $auth_key = church_memberModel::createAuthMail((int)$member->member_srl, $member->user_id, church_memberModel::AUTH_VERIFY);
    $url = church_memberModel::generateAuthUrl('procChurchConfirmEmail', (int)$member->member_srl, $auth_key);
    echo "auth_key ok\nurl=$url\n";

    $body = sprintf(
        '<p>%s님, 동명교회 새 홈페이지 개인 이메일 확인입니다.</p><p><a href="%s">이메일 확인하기</a></p>',
        htmlspecialchars($member->nick_name ?: $member->user_id, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
    );
    $sent = church_memberModel::sendMail($testEmail, '[동명교회] 개인 이메일 확인', $body);
    echo 'sendMail: ' . ($sent ? 'OK' : 'FAIL') . "\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
}
