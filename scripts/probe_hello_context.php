<?php
$ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15';
$ch = curl_init('http://127.0.0.1/');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_USERAGENT => $ua]);
$html = curl_exec($ch);
curl_close($ch);
$pos = stripos($html, 'HELLO');
echo $pos === false ? "no HELLO\n" : "HELLO at $pos\n";
if ($pos !== false) {
	echo substr($html, max(0, $pos - 200), 500) . PHP_EOL;
}
$pos2 = stripos($html, 'church-home-extra');
echo $pos2 === false ? "no church-home\n" : "church-home at $pos2\n";
