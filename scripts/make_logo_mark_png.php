<?php
/**
 * 한반도+십자가 로고 마크: 흰 배경 제거 → 투명 PNG, 업스케일
 */
$src = $argv[1] ?? '/var/www/vhosts/localhost/html/files/church/logo_mark_src.png';
$dst = $argv[2] ?? '/var/www/vhosts/localhost/html/files/church/logo_mark.png';
$targetH = (int)($argv[3] ?? 256);

$im = @imagecreatefrompng($src);
if (!$im) {
	$im = @imagecreatefromjpeg($src);
}
if (!$im) {
	fwrite(STDERR, "Cannot open $src\n");
	exit(1);
}

$w = imagesx($im);
$h = imagesy($im);
imagealphablending($im, true);
imagesavealpha($im, true);

// 모서리 기준 배경색 샘플
$corners = [
	imagecolorat($im, 0, 0),
	imagecolorat($im, $w - 1, 0),
	imagecolorat($im, 0, $h - 1),
	imagecolorat($im, $w - 1, $h - 1),
];
$bg = $corners[0];
$br = ($bg >> 16) & 0xFF;
$bgG = ($bg >> 8) & 0xFF;
$bb = $bg & 0xFF;

$out = imagecreatetruecolor($w, $h);
imagealphablending($out, false);
imagesavealpha($out, true);
$transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
imagefilledrectangle($out, 0, 0, $w, $h, $transparent);

$minX = $w; $minY = $h; $maxX = 0; $maxY = 0;
for ($y = 0; $y < $h; $y++) {
	for ($x = 0; $x < $w; $x++) {
		$rgb = imagecolorat($im, $x, $y);
		$a = ($rgb >> 24) & 0x7F;
		$r = ($rgb >> 16) & 0xFF;
		$g = ($rgb >> 8) & 0xFF;
		$b = $rgb & 0xFF;
		$dist = abs($r - $br) + abs($g - $bgG) + abs($b - $bb);
		// 밝은 흰/거의 흰 배경 제거
		$isWhite = ($r > 235 && $g > 235 && $b > 235) || $dist < 40;
		if ($isWhite) {
			imagesetpixel($out, $x, $y, $transparent);
			continue;
		}
		// soft edge: near-white → partial alpha
		if ($r > 210 && $g > 210 && $b > 210) {
			$alpha = (int)min(127, max(0, (($r + $g + $b) / 3 - 210) / 45 * 127));
			$col = imagecolorallocatealpha($out, $r, $g, $b, $alpha);
			imagesetpixel($out, $x, $y, $col);
		} else {
			$col = imagecolorallocatealpha($out, $r, $g, $b, 0);
			imagesetpixel($out, $x, $y, $col);
		}
		if ($x < $minX) $minX = $x;
		if ($y < $minY) $minY = $y;
		if ($x > $maxX) $maxX = $x;
		if ($y > $maxY) $maxY = $y;
	}
}

if ($maxX >= $minX && $maxY >= $minY) {
	$cw = $maxX - $minX + 1;
	$ch = $maxY - $minY + 1;
	$pad = 2;
	$cropW = $cw + $pad * 2;
	$cropH = $ch + $pad * 2;
	$cropped = imagecreatetruecolor($cropW, $cropH);
	imagealphablending($cropped, false);
	imagesavealpha($cropped, true);
	imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);
	imagealphablending($cropped, true);
	imagecopy($cropped, $out, $pad, $pad, $minX, $minY, $cw, $ch);
	imagedestroy($out);
	$out = $cropped;
	$w = $cropW;
	$h = $cropH;
}

$scale = $targetH / max(1, $h);
$nw = max(1, (int)round($w * $scale));
$nh = max(1, (int)round($h * $scale));
$hi = imagecreatetruecolor($nw, $nh);
imagealphablending($hi, false);
imagesavealpha($hi, true);
imagefilledrectangle($hi, 0, 0, $nw, $nh, $transparent);
imagealphablending($hi, true);
imagecopyresampled($hi, $out, 0, 0, 0, 0, $nw, $nh, $w, $h);

imagepng($hi, $dst, 6);
echo "Wrote {$dst} ({$nw}x{$nh}) from {$src}\n";
