<?php
/**
 * Refresh title scrim on existing photo tiles (soft gradient + title redraw).
 */
define('RX_BASEDIR', '/var/www/vhosts/localhost/html/');
require RX_BASEDIR . 'common/autoload.php';
Context::init();
require_once RX_BASEDIR . 'modules/dmcadmin/dmcadmin.model.php';

$keys = ['event_photo', 'rice_share', 'church_school', 'dongkeyday'];
$dir = RX_BASEDIR . 'files/church/main_tile';
$config = getModel('module')->getModuleConfig('church_write');
$tiles = is_array($config->main_tiles ?? null) ? $config->main_tiles : [];

foreach ($keys as $key)
{
	$path = $dir . '/' . $key . '.jpg';
	if (!is_file($path))
	{
		echo "skip missing: $key\n";
		continue;
	}

	$src = @imagecreatefromjpeg($path);
	if (!$src)
	{
		echo "skip unreadable: $key\n";
		continue;
	}

	$w = imagesx($src);
	$h = imagesy($src);
	$canvas = imagecreatetruecolor($w, $h);
	imagecopy($canvas, $src, 0, 0, 0, 0, $w, $h);
	imagedestroy($src);

	// Cover old hard title bar with a denser soft scrim, then redraw title.
	$band = 56;
	for ($y = 0; $y < $band; $y++)
	{
		$t = $y / max(1, $band - 1);
		$fade = 1.0 - ($t * $t * (3.0 - 2.0 * $t));
		$alpha = (int)round(127 - (88 * $fade));
		$alpha = max(40, min(127, $alpha));
		$col = imagecolorallocatealpha($canvas, 16, 24, 32, $alpha);
		imageline($canvas, 0, $y, $w - 1, $y, $col);
	}

	$title = dmcadminModel::getMainTileLabel($key);
	$font = '';
	foreach ([
		RX_BASEDIR . 'modules/dmcadmin/assets/fonts/NotoSansKR-Bold.otf',
		RX_BASEDIR . 'modules/dmcadmin/assets/fonts/NotoSansKR-Bold.ttf',
	] as $p)
	{
		if (is_file($p))
		{
			$font = $p;
			break;
		}
	}
	if ($font !== '')
	{
		$shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 45);
		$white = imagecolorallocate($canvas, 255, 255, 255);
		imagettftext($canvas, 14, 0, 13, 29, $shadow, $font, $title);
		imagettftext($canvas, 14, 0, 12, 28, $white, $font, $title);
	}

	$tmp = $path . '.refresh.jpg';
	imagejpeg($canvas, $tmp, 90);
	imagedestroy($canvas);
	@unlink($path);
	@rename($tmp, $path);
	@chmod($path, 0644);

	$row = is_array($tiles[$key] ?? null) ? $tiles[$key] : [];
	$tiles[$key] = [
		'image_url' => './files/church/main_tile/' . $key . '.jpg?t=' . time(),
		'link_url' => trim((string)($row['link_url'] ?? '')),
	];
	echo "refreshed: $key\n";
}

$config->main_tiles = $tiles;
$r = getController('module')->insertModuleConfig('church_write', $config);
echo ($r->toBool() ? 'config ok' : 'config fail') . "\n";
