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

$form = dmcadminModel::getMemberFormData((int)$member->member_srl);
echo "user_id={$form->user_id}\n";
echo "gender={$form->gender}\n";
echo "zipcode={$form->zipcode}\n";
echo "address1={$form->address1}\n";
echo "address2={$form->address2}\n";
echo "phone={$form->phone}\n";

$input = new stdClass;
$input->phone = $form->phone;
$input->zipcode = $form->zipcode;
$input->address1 = $form->address1;
$input->address2 = $form->address2;
$input->gender = $form->gender;
$input->birthday = $form->birthday;
$extra = dmcadminModel::buildExtraVars($input, dmcadminModel::getMemberExtra($member));
echo 'extra_gender=' . ($extra->gender ?? '') . "\n";
echo 'extra_zipcode=' . ($extra->zipcode ?? '') . "\n";
echo "save_prep_ok\n";
exit(($form->zipcode && $form->gender) ? 0 : 1);
