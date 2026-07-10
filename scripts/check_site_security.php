<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$site = Context::get('site_module_info');
echo 'site.domain=' . ($site->domain ?? '') . PHP_EOL;
echo 'site.security=' . ($site->security ?? '') . PHP_EOL;
echo 'url.ssl=' . config('url.ssl') . PHP_EOL;
echo 'enforce_ssl_js=' . (($site->security ?? '') === 'always' ? 'true' : 'false') . PHP_EOL;
