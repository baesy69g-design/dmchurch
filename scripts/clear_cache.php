<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
if (is_dir(__RX_BASEDIR__ . '/files/cache')) {
	FileHandler::removeDir(__RX_BASEDIR__ . '/files/cache');
}
@mkdir(__RX_BASEDIR__ . '/files/cache/template', 0775, true);
@mkdir(__RX_BASEDIR__ . '/files/cache/queries', 0775, true);
@mkdir(__RX_BASEDIR__ . '/files/cache/assets', 0775, true);
$cacheRoot = __RX_BASEDIR__ . '/files/cache';
@chown($cacheRoot, 'nobody');
@chgrp($cacheRoot, 'nogroup');
@chmod($cacheRoot, 0775);
foreach (['template', 'queries', 'assets'] as $sub)
{
	$path = $cacheRoot . '/' . $sub;
	if (is_dir($path))
	{
		@chown($path, 'nobody');
		@chgrp($path, 'nogroup');
		@chmod($path, 0775);
	}
}
echo "cache cleared\n";
