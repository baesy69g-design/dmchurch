<?php
/**
 * Rhymix domains 테이블 HTTPS 설정 수정
 * - 기본 도메인을 dmchurch.kr 로 변경
 * - security=always (enforce_ssl JS 동기화)
 */
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$domain = getenv('CHURCH_SITE_DOMAIN') ?: 'dmchurch.kr';
$domain_srl = 0;

$before = executeQuery('module.getDomainInfo', (object)['domain_srl' => $domain_srl]);
if (!$before->data)
{
	fwrite(STDERR, "domain_srl {$domain_srl} not found\n");
	exit(1);
}

echo 'before domain=' . $before->data->domain . ' security=' . $before->data->security . PHP_EOL;

$args = new stdClass();
$args->domain_srl = $domain_srl;
$args->domain = $domain;
$args->security = 'always';
$args->http_port = null;
$args->https_port = null;
$output = executeQuery('module.updateDomain', $args);
if (!$output->toBool())
{
	fwrite(STDERR, 'updateDomain failed: ' . $output->getMessage() . PHP_EOL);
	exit(1);
}

// config.php 동기화
$config_path = __RX_BASEDIR__ . '/files/config/config.php';
$config = include $config_path;
$config['url']['default'] = 'https://' . $domain . '/';
$config['url']['ssl'] = 'always';
$config['url']['http_port'] = null;
$config['url']['https_port'] = null;
$config['session']['use_ssl'] = true;
$config['session']['use_ssl_cookies'] = true;
$config['session']['samesite'] = 'Lax';
$config['cookie']['secure'] = true;
$config['cookie']['samesite'] = 'Lax';
file_put_contents($config_path, "<?php\n// Rhymix System Configuration\nreturn " . var_export($config, true) . ";\n");

// 캐시 삭제
Rhymix\Framework\Cache::clearAll();
if (class_exists('FileHandler'))
{
	FileHandler::removeDir(__RX_BASEDIR__ . '/files/cache/template');
	FileHandler::removeDir(__RX_BASEDIR__ . '/files/cache/js_filter_compiled');
}

$info = ModuleModel::getSiteInfoByDomain($domain);
echo 'after domain=' . ($info->domain ?? '') . ' security=' . ($info->security ?? '') . PHP_EOL;
echo 'config url.default=' . config('url.default') . PHP_EOL;
echo "done\n";
