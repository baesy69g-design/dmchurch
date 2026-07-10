<?php
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();
require_once '/var/www/vhosts/localhost/html/modules/dmcadmin/dmcadmin.model.php';
require_once '/var/www/vhosts/localhost/html/modules/dmcadmin/dmcadmin.controller.php';

echo 'model_ok\n';
echo 'p92 tour: ' . (dmcadminModel::isTourPage('p92') ? 'yes' : 'no') . "\n";

$data = dmcadminModel::getTourPageData('p92');
echo 'photos: ' . count($data['photos'] ?? []) . "\n";

$src = '/var/www/vhosts/localhost/html/files/church/main_slide/slide1.jpg';
$dir = dmcadminModel::getTourPageUploadDir('p92');
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$dest = $dir . '/test_tour.jpg';
copy($src, $dest);
$photos = array_merge($data['photos'] ?? [], ['./files/church/tour/p92/test_tour.jpg']);
$out = dmcadminModel::publishTourPage('p92', [
    'page_title' => $data['page_title'] ?? '사랑의 쌀나누기',
    'description' => $data['description'] ?? 'test',
    'photos' => $photos,
]);
echo 'publish: ' . ($out->toBool() ? 'ok' : $out->getMessage()) . "\n";
