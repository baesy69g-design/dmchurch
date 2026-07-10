<?php
$_SERVER['HTTP_HOST'] = '49.247.205.159:8080';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

// mobile UA
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15';
$oModuleModel = getModel('module');
$site_module_info = Context::get('site_module_info');
echo 'mobile=' . (Mobile::isFromMobilePhone() ? 'Y' : 'N') . PHP_EOL;
echo 'mid=' . Context::get('mid') . PHP_EOL;
echo 'act=' . Context::get('act') . PHP_EOL;
echo 'module=' . Context::get('module') . PHP_EOL;
if ($site_module_info) {
    echo 'site_mid=' . ($site_module_info->mid ?? '') . PHP_EOL;
    echo 'site_module=' . ($site_module_info->module ?? '') . PHP_EOL;
}
