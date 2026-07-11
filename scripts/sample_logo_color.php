<?php
$path = '/var/www/vhosts/localhost/html/files/church/logo.jpg';
$im = imagecreatefromjpeg($path);
$w = imagesx($im);
$h = imagesy($im);
echo "$w x $h\n";
$pts = [[0,0],[$w-1,0],[0,$h-1],[$w-1,$h-1],[2,2],[(int)($w/2),1],[1,(int)($h/2)]];
foreach ($pts as $p) {
	$rgb = imagecolorat($im, $p[0], $p[1]);
	printf("%d,%d => %d %d %d\n", $p[0], $p[1], ($rgb>>16)&255, ($rgb>>8)&255, $rgb&255);
}
$counts = [];
for ($x = 0; $x < $w; $x++) {
	foreach ([0,1,2,$h-1,$h-2,$h-3] as $y) {
		$rgb = imagecolorat($im, $x, $y);
		$counts[$rgb] = ($counts[$rgb] ?? 0) + 1;
	}
}
arsort($counts);
$i = 0;
foreach ($counts as $rgb => $n) {
	printf("edge #%d: %d %d %d (n=%d)\n", $i, ($rgb>>16)&255, ($rgb>>8)&255, $rgb&255, $n);
	if (++$i >= 5) break;
}
