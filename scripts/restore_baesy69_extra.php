<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$member_srl = 130;
$extra = church_memberModel::loadExtraBySrl($member_srl);
$extra->email_verified = 'Y';
$extra->email_original = $extra->email_original ?? 'baesy69@hotmail.com';
$extra->birthday = $extra->birthday ?? '19690129';
$extra->gender = $extra->gender ?? '1';
$extra->rankup_level = $extra->rankup_level ?? 5;
church_memberModel::saveExtraBySrl($member_srl, $extra);
MemberController::clearMemberCache($member_srl);
echo "restored baesy69 extra_vars\n";
