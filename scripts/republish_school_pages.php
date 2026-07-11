<?php
define('RX_BASEDIR', '/var/www/vhosts/localhost/html/');
require RX_BASEDIR . 'common/autoload.php';
Context::init();
require_once RX_BASEDIR . 'modules/dmcadmin/dmcadmin.model.php';

foreach (array_keys(dmcadminModel::SCHOOL_PAGE_MIDS) as $mid)
{
	$data = dmcadminModel::getSchoolPageData($mid);
	$r = dmcadminModel::publishSchoolPage($mid, $data);
	echo $mid . ': ' . ($r->toBool() ? 'ok' : $r->getMessage()) . "\n";
}
