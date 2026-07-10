<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
chdir(__RX_BASEDIR__);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'dmchurch.kr';
$_SERVER['HTTPS'] = 'on';
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$user_id = $argv[1] ?? 'baesy69';
$password = $argv[2] ?? 'dkagh@6918';
getController('member')->doLogin($user_id, $password, false);
if (!Context::get('logged_info'))
{
	echo "login failed\n";
	exit(1);
}

$_GET['module'] = 'church_member';
$_GET['act'] = 'dispChurchMemberProfile';
Context::set('module', 'church_member');
Context::set('act', 'dispChurchMemberProfile');
Context::set('mid', null, true);

ob_start();
$oModuleHandler = new ModuleHandler();
$oModuleHandler->init();
$proc = $oModuleHandler->procModule();
$out = ob_get_clean();

ob_start();
$oModuleHandler->displayContent($proc);
$out .= ob_get_clean();

echo 'proc class: ' . (is_object($proc) ? get_class($proc) : gettype($proc)) . "\n";
if (is_object($proc) && method_exists($proc, 'getMessage') && $proc->getMessage())
{
	echo 'proc msg: ' . $proc->getMessage() . "\n";
}
if ($oModuleHandler->error)
{
	echo 'handler error: ' . $oModuleHandler->error . ' detail=' . ($oModuleHandler->error_detail ?? '') . "\n";
}

if (is_object($proc) && method_exists($proc, 'getRedirectUrl') && $proc->getRedirectUrl())
{
	echo 'REDIRECT: ' . $proc->getRedirectUrl() . "\n";
	exit(1);
}

if (is_object($proc) && method_exists($proc, 'display'))
{
	ob_start();
	$proc->display();
	$out .= ob_get_clean();
}

$html = Context::get('content') ?: $out;
if (is_object($proc) && method_exists($proc, 'getTemplateBuffer'))
{
	$html = (string)$proc->getTemplateBuffer() ?: $html;
}

echo 'len=' . strlen((string)$html) . "\n";
foreach (['church-member-heading', 'church_profile_form', 'msg_invalid_request', 'Error'] as $k)
{
	if (strpos((string)$html, $k) !== false)
	{
		echo "found: {$k}\n";
	}
}
echo (strpos((string)$html, 'church-member-heading') !== false ? "WEB FLOW OK\n" : "WEB FLOW FAIL\n");
if (strpos((string)$html, 'church-member-heading') === false)
{
	echo substr(strip_tags((string)$html), 0, 500) . "\n";
}
exit(strpos((string)$html, 'church-member-heading') !== false ? 0 : 1);
