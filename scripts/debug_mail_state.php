<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

getModel('church_member');

$output = executeQueryArray('member.getMemberList', ['list_count' => 100, 'page' => 1]);
echo "members with real/pending email:\n";
if ($output->data) {
    foreach ($output->data as $row) {
        $m = MemberModel::getMemberInfoByMemberSrl((int)$row->member_srl);
        if (!$m) continue;
        $extra = church_memberModel::loadExtraBySrl((int)$m->member_srl);
        $pending = $extra->pending_email ?? '';
        $email = $m->email_address ?? '';
        if ($pending || strpos($email, '@dmchurch.local') === false) {
            echo $m->user_id . " email=$email pending=$pending verified=" . ($extra->email_verified ?? '') . "\n";
        }
    }
}

$auth = DB::getInstance()->executeQuery('SELECT member_srl, user_id, auth_type, regdate FROM member_auth_mail ORDER BY regdate DESC LIMIT 10');
echo "\nauth_mail rows:\n";
if ($auth->data) {
    foreach ($auth->data as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
