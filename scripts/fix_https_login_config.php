<?php
/**
 * HTTPS 사이트 로그인 수정
 * - 기본 URL / SSL 모드
 * - 세션·쿠키 Secure 플래그
 */
define('__RX_BASEDIR__', dirname(__DIR__));
$config_path = __RX_BASEDIR__ . '/files/config/config.php';
if (!is_file($config_path))
{
	fwrite(STDERR, "missing config: {$config_path}\n");
	exit(1);
}

$config = include $config_path;
if (!is_array($config))
{
	fwrite(STDERR, "invalid config\n");
	exit(1);
}

$domain = getenv('CHURCH_SITE_URL') ?: 'https://dmchurch.kr/';
if (strpos($domain, 'http') !== 0)
{
	$domain = 'https://' . ltrim($domain, '/');
}
if (substr($domain, -1) !== '/')
{
	$domain .= '/';
}

$config['url']['default'] = $domain;
$config['url']['ssl'] = 'always';
$config['url']['http_port'] = null;
$config['url']['https_port'] = null;
$config['session']['use_ssl'] = true;
$config['session']['use_ssl_cookies'] = true;
$config['session']['samesite'] = 'Lax';
$config['cookie']['secure'] = true;
$config['cookie']['samesite'] = 'Lax';

$export = var_export($config, true);
$php = "<?php\n// Rhymix System Configuration\nreturn {$export};\n";
file_put_contents($config_path, $php);

echo "updated: {$config_path}\n";
echo "url.default={$domain}\n";
echo "url.ssl=always\n";
echo "session.use_ssl=true\n";
echo "\nNOTE: also run fix_site_https.php to update domains table.\n";
