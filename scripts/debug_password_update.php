<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$user_id = $argv[1] ?? 'dmc2241';
$new_pass = $argv[2] ?? 'dmchurch2241';
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member) { echo "not_found\n"; exit(1); }
echo 'before_hash=' . substr($member->password, 0, 20) . '...' . PHP_EOL;
$hashed = Rhymix\Framework\Password::hashPassword($new_pass);
echo 'new_hash=' . substr($hashed, 0, 20) . '...' . PHP_EOL;
echo 'pre_check=' . (Rhymix\Framework\Password::checkPassword($new_pass, $hashed) ? 'Y' : 'N') . PHP_EOL;
$args = new stdClass();
$args->member_srl = $member->member_srl;
$args->password = $hashed;
$out = executeQuery('member.updateMemberPassword', $args);
echo 'update=' . ($out->toBool() ? 'Y' : 'N') . PHP_EOL;
$member2 = MemberModel::getMemberInfoByMemberSrl($member->member_srl);
echo 'after_hash=' . substr($member2->password, 0, 20) . '...' . PHP_EOL;
echo 'same=' . ($member->password === $member2->password ? 'Y' : 'N') . PHP_EOL;
echo 'db_check=' . (Rhymix\Framework\Password::checkPassword($new_pass, $member2->password) ? 'Y' : 'N') . PHP_EOL;
