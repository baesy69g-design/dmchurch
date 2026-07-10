<?php

trait dmcadminMainTileComposeTrait
{
	private static function mainTileWidth(): int
	{
		return 275;
	}

	private static function mainTileHeight(): int
	{
		return 190;
	}

	/** @return array<string,array<string,mixed>> */
	public static function getMainTileComposeStyles(): array
	{
		$styles = self::uiLabels()['main_tile_styles'] ?? [];
		return is_array($styles) ? $styles : [];
	}

	public static function shouldComposeMainTile(string $key): bool
	{
		$style = self::getMainTileComposeStyles()[$key] ?? [];
		$mode = (string)($style['mode'] ?? 'none');
		return $mode !== '' && $mode !== 'none';
	}

	public static function composeMainTileImage(string $key, string $sourcePath, string $destPath): void
	{
		if (!function_exists('imagecreatetruecolor'))
		{
			throw new Rhymix\Framework\Exception('서버 GD 확장이 설치되어 있지 않습니다.');
		}
		if (!is_file($sourcePath))
		{
			throw new Rhymix\Framework\Exception('원본 사진을 읽을 수 없습니다.');
		}

		$style = self::getMainTileComposeStyles()[$key] ?? ['mode' => 'none'];
		$mode = (string)($style['mode'] ?? 'none');
		if ($mode === '' || $mode === 'none')
		{
			self::normalizeMainTilePhoto($sourcePath, $destPath);
			return;
		}

		$src = self::loadGdImageFromPath($sourcePath);
		if (!$src)
		{
			throw new Rhymix\Framework\Exception('이미지 파일을 읽을 수 없습니다.');
		}

		$w = self::mainTileWidth();
		$h = self::mainTileHeight();
		$canvas = imagecreatetruecolor($w, $h);
		imagealphablending($canvas, true);
		imagesavealpha($canvas, false);

		$title = self::getMainTileLabel($key);
		if ($mode === 'split')
		{
			self::composeMainTileSplit($canvas, $src, $w, $h, $title, $style);
		}
		elseif ($mode === 'fullbleed')
		{
			self::composeMainTileFullbleed($canvas, $src, $w, $h, $title, $style);
		}
		elseif ($mode === 'photo_banner')
		{
			self::composeMainTilePhotoBanner($canvas, $src, $w, $h, $title, $style);
		}
		else
		{
			self::composeMainTileSplit($canvas, $src, $w, $h, $title, $style);
		}

		imagedestroy($src);
		self::saveGdImageAsJpeg($canvas, $destPath, 88);
		imagedestroy($canvas);
		@chmod($destPath, 0644);
	}

	protected static function normalizeMainTilePhoto(string $sourcePath, string $destPath): void
	{
		$src = self::loadGdImageFromPath($sourcePath);
		if (!$src)
		{
			if ($sourcePath !== $destPath)
			{
				@copy($sourcePath, $destPath);
			}
			return;
		}

		$cropped = self::coverCropGdImage(
			$src,
			imagesx($src),
			imagesy($src),
			self::mainTileWidth(),
			self::mainTileHeight()
		);
		imagedestroy($src);
		self::saveGdImageAsJpeg($cropped, $destPath, 88);
		imagedestroy($cropped);
		@chmod($destPath, 0644);
	}

	/** @param resource|\GdImage $canvas @param resource|\GdImage $src @param array<string,mixed> $style */
	protected static function composeMainTileSplit($canvas, $src, int $w, int $h, string $title, array $style): void
	{
		[$bgR, $bgG, $bgB] = self::parseHexColor((string)($style['bg'] ?? '#4a90c2'));
		[$fgR, $fgG, $fgB] = self::parseHexColor((string)($style['title_color'] ?? '#ffffff'));
		$photoShare = (float)($style['photo_share'] ?? 0.58);
		$photoShare = max(0.38, min(0.68, $photoShare));
		$blendW = (int)($style['feather'] ?? 36);
		$blendW = max(18, min(72, $blendW));
		$photoSide = (string)($style['photo_side'] ?? 'right');

		$panelW = (int)round($w * (1.0 - $photoShare));
		$photoW = $w - $panelW + $blendW;
		$photoX = $photoSide === 'left' ? 0 : $panelW - $blendW;
		$panelX = $photoSide === 'left' ? $photoW - $blendW : 0;

		$bg = imagecolorallocate($canvas, $bgR, $bgG, $bgB);
		imagefilledrectangle($canvas, 0, 0, $w - 1, $h - 1, $bg);

		$photo = self::coverCropGdImage($src, imagesx($src), imagesy($src), $photoW, $h);
		imagecopy($canvas, $photo, $photoX, 0, 0, 0, $photoW, $h);
		imagedestroy($photo);

		$blendEdge = $photoSide === 'left' ? 'right' : 'left';
		$blendStart = $blendEdge === 'left'
			? $photoX + $photoW - $blendW
			: $photoX;
		self::applyGradientBlend($canvas, $blendStart, 0, $blendW, $h, $bgR, $bgG, $bgB, $blendEdge);

		self::drawTileTitle($canvas, $title, $panelX + 10, 12, $panelW - 16, $fgR, $fgG, $fgB, 13);
	}

	/** @param resource|\GdImage $canvas @param resource|\GdImage $src @param array<string,mixed> $style */
	protected static function composeMainTileFullbleed($canvas, $src, int $w, int $h, string $title, array $style): void
	{
		[$fgR, $fgG, $fgB] = self::parseHexColor((string)($style['title_color'] ?? '#ffffff'));
		$overlay = (float)($style['overlay'] ?? 0.42);

		$photo = self::coverCropGdImage($src, imagesx($src), imagesy($src), $w, $h);
		imagecopy($canvas, $photo, 0, 0, 0, 0, $w, $h);
		imagedestroy($photo);

		$shade = imagecolorallocatealpha($canvas, 0, 0, 0, (int)round((1.0 - $overlay) * 127));
		imagefilledrectangle($canvas, 0, 0, $w - 1, $h - 1, $shade);
		self::drawTileTitle($canvas, $title, 12, 14, $w - 24, $fgR, $fgG, $fgB, 15);
	}

	/** @param resource|\GdImage $canvas @param resource|\GdImage $src @param array<string,mixed> $style */
	protected static function composeMainTilePhotoBanner($canvas, $src, int $w, int $h, string $title, array $style): void
	{
		[$fgR, $fgG, $fgB] = self::parseHexColor((string)($style['title_color'] ?? '#ffffff'));

		$photo = self::coverCropGdImage($src, imagesx($src), imagesy($src), $w, $h);
		imagecopy($canvas, $photo, 0, 0, 0, 0, $w, $h);
		imagedestroy($photo);

		for ($y = 0; $y < (int)round($h * 0.45); $y++)
		{
			$alpha = (int)round(127 * (1.0 - ($y / max(1, $h * 0.45))));
			$line = imagecolorallocatealpha($canvas, 0, 0, 0, min(127, $alpha));
			imageline($canvas, 0, $y, $w - 1, $y, $line);
		}

		self::drawTileTitle($canvas, $title, 12, 14, $w - 24, $fgR, $fgG, $fgB, 15);
	}

	/** @return resource|\GdImage|false */
	protected static function loadGdImageFromPath(string $path)
	{
		$info = @getimagesize($path);
		if (!$info || empty($info[0]) || empty($info[1]))
		{
			return false;
		}

		switch ((int)$info[2])
		{
			case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path);
			case IMAGETYPE_PNG: return @imagecreatefrompng($path);
			case IMAGETYPE_GIF: return @imagecreatefromgif($path);
			case IMAGETYPE_WEBP: return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
			default: return false;
		}
	}

	/** @param resource|\GdImage $src @return resource|\GdImage */
	protected static function coverCropGdImage($src, int $srcW, int $srcH, int $dstW, int $dstH)
	{
		$dst = imagecreatetruecolor($dstW, $dstH);
		$scale = max($dstW / max(1, $srcW), $dstH / max(1, $srcH));
		$nw = max(1, (int)round($srcW * $scale));
		$nh = max(1, (int)round($srcH * $scale));
		$resized = imagecreatetruecolor($nw, $nh);
		imagecopyresampled($resized, $src, 0, 0, 0, 0, $nw, $nh, $srcW, $srcH);
		$ox = max(0, (int)floor(($nw - $dstW) / 2));
		$oy = max(0, (int)floor(($nh - $dstH) / 2));
		imagecopy($dst, $resized, 0, 0, $ox, $oy, $dstW, $dstH);
		imagedestroy($resized);
		return $dst;
	}

	/** @param resource|\GdImage $canvas */
	protected static function applyGradientBlend($canvas, int $startX, int $y0, int $blendW, int $h, int $bgR, int $bgG, int $bgB, string $edge): void
	{
		if ($blendW < 2)
		{
			return;
		}

		for ($row = 0; $row < $h; $row++)
		{
			$wave = (int)round(sin($row / 14.0) * 1.5);
			for ($i = 0; $i < $blendW; $i++)
			{
				$t = $i / max(1, $blendW - 1);
				$mix = $edge === 'left'
					? self::smoothstep($t)
					: self::smoothstep(1.0 - $t);
				$x = $startX + $i + $wave;
				if ($x < 0 || $x >= imagesx($canvas))
				{
					continue;
				}
				$y = $y0 + $row;
				$rgb = imagecolorat($canvas, $x, $y);
				$pr = ($rgb >> 16) & 0xFF;
				$pg = ($rgb >> 8) & 0xFF;
				$pb = $rgb & 0xFF;
				$nr = (int)round($bgR * (1.0 - $mix) + $pr * $mix);
				$ng = (int)round($bgG * (1.0 - $mix) + $pg * $mix);
				$nb = (int)round($bgB * (1.0 - $mix) + $pb * $mix);
				$col = imagecolorallocate($canvas, $nr, $ng, $nb);
				imagesetpixel($canvas, $x, $y, $col);
			}
		}
	}

	protected static function smoothstep(float $t): float
	{
		$t = max(0.0, min(1.0, $t));
		return $t * $t * (3.0 - 2.0 * $t);
	}

	/** @param resource|\GdImage $canvas */
	protected static function drawTileTitle($canvas, string $title, int $x, int $y, int $maxW, int $r, int $g, int $b, int $fontSize): void
	{
		$font = self::getMainTileComposeFontPath();
		if ($font === '')
		{
			return;
		}

		$lines = self::wrapTileTitle($title, 6);
		$lineHeight = $fontSize + 5;
		foreach ($lines as $i => $line)
		{
			$lineY = $y + $fontSize + ($i * $lineHeight);
			$shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 70);
			imagettftext($canvas, $fontSize, 0, $x + 1, $lineY + 1, $shadow, $font, $line);
			$color = imagecolorallocate($canvas, $r, $g, $b);
			imagettftext($canvas, $fontSize, 0, $x, $lineY, $color, $font, $line);
		}
	}

	protected static function getMainTileComposeFontPath(): string
	{
		$candidates = [
			__DIR__ . '/assets/fonts/NotoSansKR-Bold.otf',
			__DIR__ . '/assets/fonts/NotoSansKR-Bold.ttf',
			'/usr/share/fonts/truetype/droid/DroidSansFallbackFull.ttf',
		];
		foreach ($candidates as $path)
		{
			if (!is_file($path) || !function_exists('imagettftext'))
			{
				continue;
			}
			$box = @imagettfbbox(14, 0, $path, '가');
			if ($box !== false && ($box[2] - $box[0]) > 4)
			{
				return $path;
			}
		}
		return '';
	}

	/** @return list<string> */
	protected static function wrapTileTitle(string $title, int $maxChars): array
	{
		$title = trim($title);
		if ($title === '')
		{
			return [];
		}
		$lines = [];
		$len = mb_strlen($title, 'UTF-8');
		for ($i = 0; $i < $len; $i += $maxChars)
		{
			$lines[] = mb_substr($title, $i, $maxChars, 'UTF-8');
		}
		return $lines;
	}

	/** @return array{0:int,1:int,2:int} */
	protected static function parseHexColor(string $hex): array
	{
		$hex = ltrim(trim($hex), '#');
		if (strlen($hex) === 3)
		{
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex))
		{
			return [74, 144, 194];
		}
		return [
			(int)hexdec(substr($hex, 0, 2)),
			(int)hexdec(substr($hex, 2, 2)),
			(int)hexdec(substr($hex, 4, 2)),
		];
	}

	/** @param resource|\GdImage $image */
	protected static function saveGdImageAsJpeg($image, string $path, int $quality = 88): void
	{
		$tmp = $path . '.compose.jpg';
		imagejpeg($image, $tmp, $quality);
		if (!is_file($tmp))
		{
			throw new Rhymix\Framework\Exception('타일 이미지 저장에 실패했습니다.');
		}
		if (is_file($path))
		{
			@unlink($path);
		}
		if (!@rename($tmp, $path))
		{
			@copy($tmp, $path);
			@unlink($tmp);
		}
	}
}
