<?php
$base = '/var/www/vhosts/localhost/html/';
$path = $base . 'files/church/main_tile/worship_time.jpg';
echo 'path=' . $path . "\n";
echo 'exists=' . (file_exists($path) ? 'yes' : 'no') . "\n";
echo 'is_file=' . (is_file($path) ? 'yes' : 'no') . "\n";
echo 'dir=' . __DIR__ . "\n";
echo 'rx=' . (defined('__RX_BASEDIR__') ? __RX_BASEDIR__ : 'undef') . "\n";
