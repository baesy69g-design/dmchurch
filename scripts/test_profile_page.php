<?php
/**
 * 로그인 후 사용자정보변경 페이지 실제 응답 점검
 * 사용: docker exec church-rhymix php scripts/test_profile_page.php [user_id] [password]
 */
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$user_id = $argv[1] ?? 'baesy69';
$password = $argv[2] ?? 'dkagh@6918';

echo "=== Profile page live test ===\n";
echo "user: {$user_id}\n\n";

$oMemberController = getController('member');
$login = $oMemberController->doLogin($user_id, $password, false);
echo 'login: ' . ($login->toBool() ? 'OK' : 'FAIL ' . $login->getMessage()) . "\n";

$logged = Context::get('logged_info');
if (!$logged)
{
	echo "logged_info missing after login\n";
	exit(1);
}
echo 'logged: ' . $logged->user_id . ' srl=' . $logged->member_srl . "\n";
echo 'needs_verify: ' . (church_memberModel::needsEmailVerification($logged) ? 'Y' : 'N') . "\n";

$profile_url = church_memberModel::getMemberProfileUrl();
echo 'profile_url: ' . $profile_url . "\n\n";

// 내부 디스패치로 프로필 뷰 실행
Context::set('act', 'dispChurchMemberProfile');
Context::set('module', 'church_member');
Context::set('mid', null);
$_GET['act'] = 'dispChurchMemberProfile';
$_GET['module'] = 'church_member';
unset($_GET['mid']);

$oModuleHandler = new ModuleHandler('church_member', 'dispChurchMemberProfile');
if (!$oModuleHandler->init())
{
	echo "init returned false (redirect?)\n";
	exit(1);
}

ob_start();
$oModule = $oModuleHandler->procModule();
$out = ob_get_clean();

if (is_object($oModule) && method_exists($oModule, 'getRedirectUrl'))
{
	$redir = $oModule->getRedirectUrl();
	if ($redir)
	{
		echo "REDIRECT: {$redir}\n";
		exit(1);
	}
}

$html = '';
if (is_object($oModule) && method_exists($oModule, 'getTemplateBuffer'))
{
	$html = (string)$oModule->getTemplateBuffer();
}
if ($html === '' && $out !== '')
{
	$html = $out;
}

$checks = [
	'church-member-heading' => 'heading',
	'church_profile_form' => 'form',
	'procChurchSaveProfile' => 'save action',
	'church_user_name' => 'name field',
];
foreach ($checks as $needle => $label)
{
	echo ($html !== '' && strpos($html, $needle) !== false ? '[OK]' : '[FAIL]') . " {$label}\n";
}

if ($html === '')
{
	echo "\nNo HTML output. Module class: " . (is_object($oModule) ? get_class($oModule) : 'null') . "\n";
	if (is_object($oModule) && !empty($oModule->getMessage()))
	{
		echo 'message: ' . $oModule->getMessage() . "\n";
	}
	exit(1);
}

echo "\nHTML length: " . strlen($html) . "\n";
echo "PASS\n";
