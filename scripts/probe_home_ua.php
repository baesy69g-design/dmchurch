<?php
$ua = $argv[1] ?? 'desktop';
$url = 'http://127.0.0.1/';
$ch = curl_init($url);
$agents = [
    'mobile' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
    'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120',
];
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => $agents[$ua] ?? $agents['desktop'],
    CURLOPT_FOLLOWLOCATION => true,
]);
$html = curl_exec($ch);
curl_close($ch);
$keys = ['church-home-extra', 'dmcadmin', '동명지킴이', 'dispDmcMgr', 'Welcome to Rhymix', 'church-main-tiles'];
foreach ($keys as $k) {
    echo $k . '=' . (strpos($html, $k) !== false ? 'Y' : 'N') . PHP_EOL;
}
foreach (['welcomeXE', 'church-home', 'dmc-mgr', 'dmcadmin-page', 'HELLO, WORLD'] as $k) {
    echo $k . '=' . (stripos($html, $k) !== false ? 'Y' : 'N') . PHP_EOL;
}
if (preg_match('/<title>([^<]+)</', $html, $m)) {
    echo 'title=' . trim($m[1]) . PHP_EOL;
}
echo 'len=' . strlen($html) . PHP_EOL;
