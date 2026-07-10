<?php
require dirname(__DIR__) . '/common/autoload.php';
Context::init();
getController('addon')->makeCacheFile(0, 'pc', 'site');
getController('addon')->makeCacheFile(0, 'mobile', 'site');
echo "addon cache rebuilt\n";
