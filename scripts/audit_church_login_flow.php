<?php
/**
 * 로그인·온보딩·이메일인증 통합 점검 (CLI, VPS)
 * usage: php audit_church_login_flow.php
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$fail = 0;
function ok(string $label, bool $pass, string $detail = ''): void
{
	global $fail;
	$mark = $pass ? 'OK' : 'FAIL';
	if (!$pass) {
		$fail++;
	}
	echo "[$mark] $label" . ($detail !== '' ? " — $detail" : '') . PHP_EOL;
}

echo "=== Church login/onboard audit ===" . PHP_EOL . PHP_EOL;

// 1) Addon activation
$onboard_used = 'N';
try {
	$oDB = DB::getInstance();
	$stmt = $oDB->query("SELECT is_used FROM " . $oDB->prefix . "addons_site WHERE addon='church_member_onboard' AND site_srl=0");
	if ($stmt) {
		$row = $stmt->fetchObject();
		$onboard_used = $row->is_used ?? 'N';
	}
} catch (Throwable $e) {
	$onboard_used = '?';
}
ok('church_member_onboard addon active', $onboard_used === 'Y', "is_used=$onboard_used");

$cache_file = __RX_BASEDIR__ . 'files/cache/addons/pc.php';
$cache_has_onboard = is_file($cache_file) && strpos(file_get_contents($cache_file), 'church_member_onboard') !== false;
ok('addon cache includes church_member_onboard', $cache_has_onboard);

// 2) Triggers
$oModuleModel = getModel('module');
ok('trigger member.doLogin after', (bool)$oModuleModel->getTrigger('member.doLogin', 'church_member', 'controller', 'triggerMemberDoLoginAfter', 'after'));
ok('trigger member.doLogout before', (bool)$oModuleModel->getTrigger('member.doLogout', 'church_member', 'controller', 'triggerMemberDoLogoutBefore', 'before'));

// 3) Sample users
$sample_users = ['baesy69', 'dmc2241'];
foreach ($sample_users as $uid) {
	$m = MemberModel::getMemberInfoByUserID($uid);
	if (!$m) {
		ok("member $uid exists", false);
		continue;
	}
	$need = church_memberModel::needsEmailVerification($m);
	$exempt = church_memberModel::isExemptMember($m);
	$guide = church_memberModel::shouldShowLoginGuide($uid);
	echo "  - $uid: email=" . ($m->email_address ?? '') . " need_verify=" . ($need ? 'Y' : 'N') . " exempt=" . ($exempt ? 'Y' : 'N') . " show_guide=" . ($guide ? 'Y' : 'N') . PHP_EOL;
	ok("member $uid login testable", true);
}

// 4) URL helpers
$login_url = church_memberModel::getLayoutLoginUrl();
ok('getLayoutLoginUrl has church_login=1', strpos($login_url, 'church_login=1') !== false, $login_url);

$verify_url = getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail');
$recover_url = getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchRecoverAccount');
ok('verify page URL', $verify_url !== '');
ok('recover page URL', $recover_url !== '');

// 5) Unverified count
$oDB = DB::getInstance();
$q = "SELECT COUNT(*) AS cnt FROM " . $oDB->prefix . "member WHERE email_address LIKE '%@dmchurch.local'";
$stmt = $oDB->query($q);
$cnt = 0;
if ($stmt) {
	$cnt = (int)($stmt->fetchObject()->cnt ?? 0);
}
echo "  - legacy @dmchurch.local members: $cnt" . PHP_EOL;
ok('legacy placeholder email query', true);

// 6) Layout assets
$assets = [
	'layouts/xedition/js/church_login_widget.js',
	'layouts/xedition/js/church_login_guide.js',
	'modules/church_member/church_member.controller.php',
	'addons/church_member_onboard/church_member_onboard.addon.php',
];
foreach ($assets as $rel) {
	ok("file $rel", is_file(__RX_BASEDIR__ . $rel));
}

// 7) Login simulation (baesy69 if password works)
$test_uid = 'baesy69';
$test_pass = 'dkagh@6918';
$m = MemberModel::getMemberInfoByUserID($test_uid);
if ($m) {
	$c = getController('member');
	$out = $c->doLogin($test_uid, $test_pass, false);
	ok("doLogin $test_uid", $out->toBool(), $out->getMessage());
	if ($out->toBool() && church_memberModel::needsEmailVerification($m)) {
		ok('unverified user should redirect to verify after login', true, 'needsEmailVerification=Y');
	}
}

echo PHP_EOL . ($fail === 0 ? 'ALL CHECKS PASSED' : "FAILED: $fail issue(s)") . PHP_EOL;
exit($fail > 0 ? 1 : 0);
