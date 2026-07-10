<?php
define('RX_BASEDIR', '/var/www/vhosts/localhost/html/');
require RX_BASEDIR . 'common/autoload.php';
Context::init();
require_once RX_BASEDIR . 'modules/dmcadmin/dmcadmin.model.php';

$src = RX_BASEDIR . 'files/church/main_slide/slide1.jpg';
$dest = RX_BASEDIR . 'files/church/main_tile/_test_event_photo.jpg';
dmcadminModel::composeMainTileImage('event_photo', $src, $dest);
echo "ok: $dest\n";
echo filesize($dest) . " bytes\n";
