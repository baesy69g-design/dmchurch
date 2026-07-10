<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

$_SERVER['HTTP_HOST'] = '49.247.205.159:8080';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

$oContext = Context::getInstance();
$oContext->setRequestMethod('GET');
$oContext->setRequestURI('/');
$oContext->setRequestArguments();

$moduleHandler = ModuleHandler::getInstance();
$moduleHandler->init();

echo 'mid=' . Context::get('mid') . PHP_EOL;
echo 'module=' . Context::get('module') . PHP_EOL;
$mi = Context::get('module_info');
echo 'module_info.mid=' . ($mi->mid ?? 'null') . PHP_EOL;

getModel('dmcadmin');
$html = dmcadminModel::renderMainHomeHtml();
echo 'html_len=' . strlen($html) . PHP_EOL;
echo substr($html, 0, 200) . PHP_EOL;
