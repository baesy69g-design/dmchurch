<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
getModel('dmcadmin');

$user_id = $argv[1] ?? 'baesy69';
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member)
{
	echo "member not found\n";
	exit(1);
}

$extra = dmcadminModel::buildExtraVars((object)[
	'phone' => '',
	'zipcode' => '04129',
	'address1' => '서울 마포구 마포대로 195',
	'address2' => '(아현동, 마포 래미안 푸르지오) 303-1004호',
	'gender' => '1',
	'birthday' => '19690129',
], dmcadminModel::getMemberExtra($member));

$args = new stdClass;
$args->member_srl = (int)$member->member_srl;
$args->user_id = $member->user_id;
$args->user_name = $member->user_name;
$args->nick_name = $member->nick_name;
$args->email_address = $member->email_address;
$args->phone_number = preg_replace('/\D/', '', (string)($member->phone_number ?? ''));
$args->birthday = '19690129';
$args->denied = $member->denied ?? 'N';
$args->status = (($member->denied ?? 'N') === 'Y') ? 'DENIED' : 'APPROVED';

$out = MemberController::getInstance()->updateMember($args);
echo 'toBool=' . ($out->toBool() ? 'Y' : 'N') . PHP_EOL;
echo 'message=' . ($out->getMessage() ?? '') . PHP_EOL;
echo 'error=' . ($out->getError() ?? '') . PHP_EOL;
if ($out->toBool())
{
	getModel('church_member');
	church_memberModel::saveExtraBySrl((int)$member->member_srl, $extra);
	echo "extra_saved=Y\n";
}
exit($out->toBool() ? 0 : 1);
