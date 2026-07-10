<?php
$font = '/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf';
$img = imagecreatetruecolor(200, 60);
$white = imagecolorallocate($img, 255, 255, 255);
$blue = imagecolorallocate($img, 93, 173, 226);
imagefilledrectangle($img, 0, 0, 199, 59, $blue);
$bbox = imagettfbbox(14, 0, $font, '교회행사사진');
var_export($bbox);
echo "\n";
imagettftext($img, 14, 0, 10, 35, $white, $font, '교회행사사진');
imagejpeg($img, '/var/www/vhosts/localhost/html/files/church/main_tile/_font_test.jpg', 90);
echo "saved\n";
