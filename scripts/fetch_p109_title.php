<?php
$url = 'http://127.0.0.1:8080/p109';
$html = @file_get_contents($url);
if (!$html) { exit("fetch fail\n"); }
if (preg_match('/<div class="sub_title[^"]*">.*?<h1[^>]*>(.*?)<\/h1>/s', $html, $m)) {
	echo "banner h1: " . trim(strip_tags($m[1])) . "\n";
}
if (preg_match('/sub_header_title|church_sub_title/', $html)) {
	echo "has sub_header markers\n";
}
// content area headings
preg_match_all('/<h[12][^>]*>([^<]{1,40})<\/h[12]>/', $html, $hs);
foreach (array_unique($hs[1]) as $h) {
	$h = trim(html_entity_decode($h));
	if ($h !== '') echo "heading: $h\n";
}
