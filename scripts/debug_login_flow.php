<?php
/**
 * 로그인 흐름 진단 (CLI / 서버 내부)
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';

function simulate(string $label, array $server): void
{
	$_SERVER = array_merge($_SERVER, $server);
	if (!defined('RX_SSL'))
	{
		// constants already loaded; skip
	}
	echo "=== {$label} ===\n";
	echo 'HOST=' . ($_SERVER['HTTP_HOST'] ?? '') . ' SSL=' . (defined('RX_SSL') && RX_SSL ? 'Y' : 'N') . "\n";
}

echo 'config url.default=' . config('url.default') . "\n";
echo 'config url.ssl=' . config('url.ssl') . "\n";
echo 'session.use_ssl=' . (config('session.use_ssl') ? 'Y' : 'N') . "\n";
echo 'session.use_ssl_cookies=' . (config('session.use_ssl_cookies') ? 'Y' : 'N') . "\n";

$member = MemberModel::getMemberInfoByUserID('dmc2241');
if ($member)
{
	echo 'dmc2241 exists srl=' . $member->member_srl . ' denied=' . ($member->denied ?? '') . "\n";
}
else
{
	echo "dmc2241 NOT FOUND\n";
}

$member2 = MemberModel::getMemberInfoByUserID('baesy69');
if ($member2)
{
	echo 'baesy69 exists srl=' . $member2->member_srl . ' denied=' . ($member2->denied ?? '') . "\n";
}
