<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();
getModel('dmcadmin');

$mid = $argv[1] ?? 'p8';
Context::set('mid', $mid);

$ref = new ReflectionMethod('dmcadminModel', 'detectSubTopMenuKey');
$ref->setAccessible(true);
$key = $ref->invoke(null);

echo "mid={$mid} key=" . ($key ?? 'NULL') . "\n";
print_r(dmcadminModel::getSubTopBannerUrls());
