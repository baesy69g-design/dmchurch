<?php
$ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15';
$ch = curl_init('http://127.0.0.1/');
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_USERAGENT => $ua,
	CURLOPT_FOLLOWLOCATION => true,
]);
$html = curl_exec($ch);
curl_close($ch);

foreach (['layout', 'm.layout', 'xedition', 'church_main_home', 'use_mobile', 'mcontent', 'index'] as $k) {
	echo $k . '=' . (stripos($html, $k) !== false ? 'Y' : 'N') . PHP_EOL;
}
if (preg_match('/<title>([^<]+)</', $html, $m)) {
	echo 'title=' . trim($m[1]) . PHP_EOL;
}
if (preg_match('/class="([^"]*welcome[^"]*)"/i', $html, $m)) {
	echo 'welcome_class=' . $m[1] . PHP_EOL;
}
echo substr($html, 0, 1500) . PHP_EOL;
