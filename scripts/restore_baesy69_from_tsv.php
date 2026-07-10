<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$path = $argv[1] ?? '';
if ($path === '' || !is_file($path))
{
	echo "usage: php restore_baesy69_from_tsv.php <tsv-file>\n";
	exit(1);
}

$line = trim(file_get_contents($path));
if ($line === '')
{
	echo "empty rankup data\n";
	exit(1);
}

$cols = explode("\t", $line);
[$gender, $zipcode, $address1, $address2, $phone, $hphone, $passwd] = array_pad($cols, 7, '');

$member = MemberModel::getMemberInfoByUserID('baesy69');
if (!$member)
{
	echo "baesy69 not found\n";
	exit(1);
}

$member_srl = (int)$member->member_srl;
$extra = church_memberModel::loadExtraBySrl($member_srl);
$backup = clone $extra;

$extra->gender = trim($gender) ?: ($extra->gender ?? '1');
$extra->zipcode = trim($zipcode);
$extra->address1 = trim($address1);
$extra->address2 = trim($address2);
$extra->phone = trim($phone);
$extra->email_verified = 'Y';
if (trim($passwd) !== '')
{
	$extra->rankup_passwd = trim($passwd);
}

$extra = church_memberModel::mergePreservedExtra($extra, $backup);
church_memberModel::saveExtraBySrl($member_srl, $extra);

$phone_number = preg_replace('/\D/', '', $hphone) ?: preg_replace('/\D/', '', $phone);
if ($phone_number !== '')
{
	$args = new stdClass;
	$args->member_srl = $member_srl;
	$args->phone_number = $phone_number;
	MemberController::getInstance()->updateMember($args);
}

MemberController::clearMemberCache($member_srl);
echo "restored baesy69 gender={$extra->gender} zip={$extra->zipcode} addr={$extra->address1}\n";
