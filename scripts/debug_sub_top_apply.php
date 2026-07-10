<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();

$_GET['mid'] = $argv[1] ?? 'p8';
$_REQUEST['mid'] = $_GET['mid'];

$oContext = Context::getInstance();
$oContext->init();

getModel('dmcadmin');
dmcadminModel::applySubTopBannerToLayout();

$headers = Context::getHtmlHeader();
echo "html_header contains church-sub-top: " . (strpos($headers, 'church-sub-top') !== false ? 'YES' : 'NO') . "\n";
if (strpos($headers, 'church-sub-top') !== false) {
    preg_match('/church-sub-top-banner.*?<\\/style>/s', $headers, $m);
    echo ($m[0] ?? '') . "\n";
}
