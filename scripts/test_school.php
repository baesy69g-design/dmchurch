<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
getModel('dmcadmin');
echo 'isSchoolPage p109: ' . (dmcadminModel::isSchoolPage('p109') ? 'YES' : 'NO') . PHP_EOL;
echo 'isSchoolPage empty: ' . (dmcadminModel::isSchoolPage('') ? 'YES' : 'NO') . PHP_EOL;
echo 'MIDS: ' . implode(',', array_keys(dmcadminModel::SCHOOL_PAGE_MIDS)) . PHP_EOL;
