<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$user_id = $argv[1] ?? 'dmc2241';
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member) { fwrite(STDERR, "not found\n"); exit(1); }
$temp_pass = 'ChurchFix!' . substr(md5((string)time()), 0, 6);
$hashed = Rhymix\Framework\Password::hashPassword($temp_pass);
$args = new stdClass();
$args->member_srl = $member->member_srl;
$args->password = $hashed;
executeQuery('member.updateMemberPassword', $args);
echo $temp_pass . PHP_EOL;
