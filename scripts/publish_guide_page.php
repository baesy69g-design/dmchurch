<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();

$mid = $argv[1] ?? 'p8';
if (!dmcadminModel::isGuidePage($mid))
{
	fwrite(STDERR, "not a guide page: {$mid}\n");
	exit(1);
}

$data = dmcadminModel::getGuidePageData($mid);
$out = dmcadminModel::publishGuidePage($mid, $data);
echo $mid . ': ' . ($out->toBool() ? 'OK' : $out->getMessage()) . "\n";
exit($out->toBool() ? 0 : 1);
