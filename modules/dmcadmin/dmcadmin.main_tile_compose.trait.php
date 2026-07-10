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
		[$fgR, $fgG, $fgB] = self::parseHexColor((string)($style['title_color'] ?? '#ffffff'));

		$photo = self::coverCropGdImage($src, imagesx($src), imagesy($src), $w, $h);
		imagecopy($canvas, $photo, 0, 0, 0, 0, $w, $h);
		imagedestroy($photo);
		imagedestroy($src);

		self::drawTileTitle($canvas, $title, 12, 16, $w - 24, $fgR, $fgG, $fgB, 14, true, true);

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
		return self::coverCropGdImageFocal($src, $srcW, $srcH, $dstW, $dstH, 0.5);
	}

	/** @param resource|\GdImage $src @return resource|\GdImage */
	protected static function coverCropGdImageFocal($src, int $srcW, int $srcH, int $dstW, int $dstH, float $focalX = 0.5)
	{
		$dst = imagecreatetruecolor($dstW, $dstH);
		$scale = max($dstW / max(1, $srcW), $dstH / max(1, $srcH));
		$nw = max(1, (int)round($srcW * $scale));
		$nh = max(1, (int)round($srcH * $scale));
		$resized = imagecreatetruecolor($nw, $nh);
		imagecopyresampled($resized, $src, 0, 0, 0, 0, $nw, $nh, $srcW, $srcH);
		$focalX = max(0.0, min(1.0, $focalX));
		$ox = max(0, min($nw - $dstW, (int)round(($nw - $dstW) * $focalX)));
		$oy = max(0, (int)floor(($nh - $dstH) / 2));
		imagecopy($dst, $resized, 0, 0, $ox, $oy, $dstW, $dstH);
		imagedestroy($resized);
		return $dst;
	}

	/** @param resource|\GdImage $canvas */
	protected static function drawTileTitle($canvas, string $title, int $x, int $y, int $maxW, int $r, int $g, int $b, int $fontSize, bool $withBackdrop = false, bool $singleLine = false): void
	{
		$font = self::getMainTileComposeFontPath();
		if ($font === '')
		{
			return;
		}

		$title = trim($title);
		if ($title === '')
		{
			return;
		}

		if ($singleLine)
		{
			$fontSize = self::fitTileFontSize($font, $title, $maxW, $fontSize, 9);
			$lines = [$title];
		}
		else
		{
			$lines = self::wrapTileTitle($title, 5);
		}

		$lineHeight = $fontSize + 6;
		$textH = count($lines) * $lineHeight + 4;

		if ($withBackdrop && $lines)
		{
			$backdrop = imagecolorallocatealpha($canvas, 0, 0, 0, 90);
			imagefilledrectangle($canvas, max(0, $x - 6), max(0, $y - 4), min(imagesx($canvas) - 1, $x + $maxW), $y + $textH, $backdrop);
		}

		foreach ($lines as $i => $line)
		{
			$lineY = $y + $fontSize + ($i * $lineHeight);
			$shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 60);
			imagettftext($canvas, $fontSize, 0, $x + 1, $lineY + 1, $shadow, $font, $line);
			$color = imagecolorallocate($canvas, $r, $g, $b);
			imagettftext($canvas, $fontSize, 0, $x, $lineY, $color, $font, $line);
		}
	}

	protected static function fitTileFontSize(string $font, string $title, int $maxW, int $maxSize = 14, int $minSize = 9): int
	{
		for ($size = $maxSize; $size >= $minSize; $size--)
		{
			$box = @imagettfbbox($size, 0, $font, $title);
			if ($box !== false && ($box[2] - $box[0]) <= $maxW)
			{
				return $size;
			}
		}
		return $minSize;
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
			return [255, 255, 255];
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
