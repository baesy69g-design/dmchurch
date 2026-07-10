<?php
/**
 * Generate worship_time main tile: 1·2·3부 예배시간 한눈에.
 */
$base = '/var/www/vhosts/localhost/html/';
$out = $base . 'files/church/main_tile/worship_time.jpg';
$fontCandidates = [
	$base . 'modules/dmcadmin/assets/fonts/NotoSansKR-Bold.otf',
	$base . 'modules/dmcadmin/assets/fonts/NotoSansKR-Bold.ttf',
	'/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf',
];
$font = '';
foreach ($fontCandidates as $p)
{
	if (is_file($p))
	{
		$font = $p;
		break;
	}
}
if ($font === '')
{
	fwrite(STDERR, "font missing\n");
	exit(1);
}

$w = 275;
$h = 190;
$im = imagecreatetruecolor($w, $h);
imagealphablending($im, true);

$bg = imagecolorallocate($im, 244, 249, 246);
$header = imagecolorallocate($im, 45, 106, 79);
$rowAlt = imagecolorallocate($im, 232, 245, 238);
$line = imagecolorallocate($im, 200, 216, 208);
$white = imagecolorallocate($im, 255, 255, 255);
$ink = imagecolorallocate($im, 27, 67, 50);
$muted = imagecolorallocate($im, 55, 90, 72);

imagefilledrectangle($im, 0, 0, $w - 1, $h - 1, $bg);
imagefilledrectangle($im, 0, 0, $w - 1, 36, $header);

imagettftext($im, 13, 0, 12, 25, $white, $font, '예배시간');

$rows = [
	['주일 1부', '오전 08:50'],
	['주일 2부', '오전 11:00'],
	['주일 3부', '오후 01:20'],
];

$top = 42;
$rowH = 46;
for ($i = 0; $i < 3; $i++)
{
	$y0 = $top + $i * $rowH;
	$y1 = $y0 + $rowH - 1;
	if ($i % 2 === 1)
	{
		imagefilledrectangle($im, 0, $y0, $w - 1, $y1, $rowAlt);
	}
	imageline($im, 10, $y1, $w - 10, $y1, $line);

	[$name, $time] = $rows[$i];
	imagettftext($im, 12, 0, 14, $y0 + 28, $ink, $font, $name);
	$box = imagettfbbox(13, 0, $font, $time);
	$tw = $box[2] - $box[0];
	imagettftext($im, 13, 0, $w - 14 - $tw, $y0 + 28, $muted, $font, $time);
}

$tmp = $out . '.tmp.jpg';
imagejpeg($im, $tmp, 90);
imagedestroy($im);
if (!@rename($tmp, $out))
{
	@copy($tmp, $out);
	@unlink($tmp);
}
@chmod($out, 0644);
echo "ok: $out (" . filesize($out) . " bytes)\n";
