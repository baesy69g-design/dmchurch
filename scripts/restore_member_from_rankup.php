<?php
/**
 * 구홈피(rankup_src) 데이터로 회원 extra_vars 복원
 * 사용: php restore_member_from_rankup.php [user_id]
 */
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$user_id = strtolower(trim($argv[1] ?? 'baesy69'));
$member = MemberModel::getMemberInfoByUserID($user_id);
if (!$member)
{
	echo "member not found: {$user_id}\n";
	exit(1);
}

$db_config = Rhymix\Framework\Config::get('db.master');
$pdo = new PDO(
	'mysql:host=' . $db_config['host'] . ';port=' . ($db_config['port'] ?? 3306) . ';dbname=' . $db_config['database'] . ';charset=utf8mb4',
	$db_config['user'],
	$db_config['pass'],
	[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$stmt = $pdo->prepare(
	'SELECT m.uid, m.passwd, e.nickname, e.birthday, e.gender, e.email, e.phone, e.hphone,
	        e.zipcode, e.address1, e.address2, e.baptismalname, e.level
	 FROM rankup_src.rankup_member m
	 LEFT JOIN rankup_src.rankup_member_extend e ON e.uid = m.uid
	 WHERE m.uid = ?'
);
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row)
{
	echo "rankup data not found for {$user_id}\n";
	exit(1);
}

$member_srl = (int)$member->member_srl;
$extra = church_memberModel::loadExtraBySrl($member_srl);
$backup = clone $extra;

$phone = trim((string)($row['phone'] ?? ''));
$hphone = trim((string)($row['hphone'] ?? ''));
$extra->phone = $phone;
$extra->gender = trim((string)($row['gender'] ?? ''));
$extra->zipcode = trim((string)($row['zipcode'] ?? ''));
$extra->address1 = trim((string)($row['address1'] ?? ''));
$extra->address2 = trim((string)($row['address2'] ?? ''));
$extra->baptismalname = trim((string)($row['baptismalname'] ?? ''));
$extra->birthday = preg_replace('/\D/', '', (string)($row['birthday'] ?? ''));
$extra->rankup_level = (int)($row['level'] ?? ($extra->rankup_level ?? 5));
$extra->rankup_passwd = trim((string)($row['passwd'] ?? ''));
$email_original = trim((string)($row['email'] ?? ''));
if ($email_original !== '')
{
	$extra->email_original = $email_original;
}

$extra = church_memberModel::mergePreservedExtra($extra, $backup);
church_memberModel::saveExtraBySrl($member_srl, $extra);

$phone_number = preg_replace('/\D/', '', $hphone) ?: preg_replace('/\D/', '', $phone);
if ($phone_number !== '')
{
	$args = new stdClass;
	$args->member_srl = $member_srl;
	$args->phone_number = $phone_number;
	if (!empty($extra->birthday))
	{
		$args->birthday = $extra->birthday;
	}
	MemberController::getInstance()->updateMember($args);
}

MemberController::clearMemberCache($member_srl);
echo "restored {$user_id} (srl={$member_srl}) from rankup_src\n";
echo "gender={$extra->gender} zip={$extra->zipcode} addr={$extra->address1}\n";
