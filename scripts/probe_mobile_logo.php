<?php
$ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15';
$ch = curl_init('http://127.0.0.1/');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERAGENT => $ua]);
$html = curl_exec($ch);
curl_close($ch);
foreach (['files/church/logo.jpg', 'Mobile XE', 'church-home-extra'] as $k) {
	echo $k . '=' . (strpos($html, $k) !== false ? 'Y' : 'N') . PHP_EOL;
}
if (preg_match('/class="h1"[^>]*>.*?<\/h1>/s', $html, $m)) {
	echo 'h1=' . trim(preg_replace('/\s+/', ' ', $m[0])) . PHP_EOL;
}
