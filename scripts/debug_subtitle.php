<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
getModel('dmcadmin');
$mid = 'p109';
echo "mid=$mid\n";
echo "isSchool: " . (dmcadminModel::isSchoolPage($mid) ? 'Y' : 'N') . "\n";
echo "SCHOOL: " . (dmcadminModel::SCHOOL_PAGE_MIDS[$mid] ?? 'none') . "\n";
echo "deepest: " . (dmcadminModel::detectDeepestSelectedMenuLabel() ?? 'null') . "\n";
