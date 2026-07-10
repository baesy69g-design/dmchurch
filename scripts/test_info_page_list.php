<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();
getModel('dmcadmin');
foreach (dmcadminModel::getInfoPageList() as $p) {
    echo $p->mid . "\t" . $p->label . "\t" . $p->kind . "\n";
}
