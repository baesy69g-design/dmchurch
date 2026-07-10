<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';

$_SERVER['HTTP_HOST'] = '49.247.205.159:8080';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

Context::init();
$h = ModuleHandler::getInstance();
$h->init();

$log = [
	'mid' => Context::get('mid'),
	'module' => Context::get('module'),
	'module_info_mid' => Context::get('module_info')->mid ?? null,
	'current_module_info_mid' => Context::get('current_module_info')->mid ?? null,
	'content_len' => strlen((string)Context::get('content')),
];
file_put_contents(__RX_BASEDIR__ . 'files/church/tiles_debug.json', json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo json_encode($log, JSON_UNESCAPED_UNICODE);
