<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$base = \RX_BASEDIR;

$config = ModuleModel::getModuleConfig('church_write');
if (!$config)
{
    $config = new stdClass;
}

$tile_keys = [
    'worship_time', 'event_photo', 'rice_share', 'church_school',
    'pastoral_schedule', 'weekly_bulletin', 'new_family', 'scholarship',
];
$tiles = [];
foreach ($tile_keys as $key)
{
    $path = $base . 'files/church/main_tile/' . $key . '.jpg';
    if (!is_file($path))
    {
        echo "missing tile file {$key}\n";
        continue;
    }
    $tiles[$key] = [
        'image_url' => './files/church/main_tile/' . $key . '.jpg',
        'link_url' => '',
    ];
    echo "tile {$key}\n";
}
$config->main_tiles = $tiles;

$slides = [];
for ($i = 1; $i <= 4; $i++)
{
    $path = $base . 'files/church/main_slide/slide' . $i . '.jpg';
    $slides[] = is_file($path) ? './files/church/main_slide/slide' . $i . '.jpg' : '';
}
$config->main_slide_urls = $slides;
echo 'slides ' . count(array_filter($slides)) . "\n";

$hero = [];
foreach ([
    'pastor' => 'pastor.png',
    'quick_1' => 'quick_1.png',
    'quick_2' => 'quick_2.png',
    'quick_3' => 'quick_3.png',
    'quick_4' => 'quick_4.png',
] as $key => $file)
{
    $path = $base . 'files/church/main_hero/' . $file;
    if (is_file($path))
    {
        $hero[$key] = './files/church/main_hero/' . $file;
        echo "hero {$key}\n";
    }
}
$config->main_hero_images = $hero;

$output = getController('module')->insertModuleConfig('church_write', $config);
echo $output->toBool() ? "saved ok\n" : ('save failed: ' . $output->getMessage() . "\n");

if (is_dir($base . 'files/cache'))
{
    FileHandler::removeDir($base . 'files/cache');
    @mkdir($base . 'files/cache/template', 0755, true);
    @chown($base . 'files/cache', 'nobody');
    @chgrp($base . 'files/cache', 'nogroup');
}
