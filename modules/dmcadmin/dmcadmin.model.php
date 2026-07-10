<?php
/**
 * @class  dmcadminModel
 */
require_once __DIR__ . '/dmcadmin.overseas_mission.trait.php';

class dmcadminModel extends dmcadmin
{
	use dmcadminOverseasMissionTrait;
	public const ADMIN_USER_ID = 'dmc2241';
	public const SESSION_KEY = 'dmcadmin_authenticated';
	public const SESSION_TTL = 7200;
	public const MAX_PRAYER_READERS = 2;
	public const MAIN_SLIDE_COUNT = 4;
	public const SUB_TOP_BANNER_WIDTH = 1900;
	public const SUB_TOP_BANNER_HEIGHT = 220;
	public const SUB_TOP_STITCH_MAX = 4;

	/** @var array<string,mixed>|null */
	private static ?array $uiLabels = null;

	/** @return array<string,mixed> */
	public static function uiLabels(): array
	{
		if (self::$uiLabels === null)
		{
			$path = __DIR__ . '/dmcadmin.labels.php';
			self::$uiLabels = is_file($path) ? require $path : [];
		}
		return self::$uiLabels;
	}

	/** @return array<string,string> */
	public static function getDomesticMissionCategories(): array
	{
		$cats = self::uiLabels()['domestic_mission']['categories'] ?? null;
		if (is_array($cats) && $cats)
		{
			return $cats;
		}
		return [
			'church' => '??? ?? ??',
			'org' => '??? ?? ??',
		];
	}

	/** @var array<string,array{label:string,target:string,id:string}> */
	public const MAIN_TILES = [
		'worship_time' => ['label' => '????', 'target' => 'page', 'id' => '78'],
		'event_photo' => ['label' => '??????', 'target' => 'mid', 'id' => 'picture'],
		'rice_share' => ['label' => '??? ????', 'target' => 'page', 'id' => '92'],
		'church_school' => ['label' => '????', 'target' => 'page', 'id' => '109'],
		'pastoral_schedule' => ['label' => '????', 'target' => 'page', 'id' => '84'],
		'weekly_bulletin' => ['label' => '??? ??', 'target' => 'mid', 'id' => 'jubo'],
		'new_family' => ['label' => '?????', 'target' => 'mid', 'id' => 'newface'],
		'scholarship' => ['label' => '?????', 'target' => 'page', 'id' => '146'],
	];

	/** @var array<int,array{label:string,target:string,id:string}> */
	public const MAIN_QUICK_LINKS = [
		['label' => '?? ?? ?? ???', 'target' => 'mid', 'id' => 'sermon'],
		['label' => '?? ?? ???', 'target' => 'mid', 'id' => 'choir'],
		['label' => '??? ???', 'target' => 'mid', 'id' => 'peniel'],
		['label' => '???? ???', 'target' => 'mid', 'id' => 'eventvideo'],
	];

	public const SUB_TOP_MENUS = [
		'info' => ['label' => '????', 'legacy' => 'top.15014945664036.jpg'],
		'news' => ['label' => '????', 'legacy' => 'top.15014945609550.jpg'],
		'mission' => ['label' => '??? ??', 'legacy' => 'top.15014945691236.jpg'],
		'school' => ['label' => '????', 'legacy' => 'top.15022460688328.jpg'],
		'broadcast' => ['label' => '????', 'legacy' => 'top.15022460663461.jpg'],
		'community' => ['label' => '????', 'legacy' => 'top.15014945708645.jpg'],
	];

	/** page mid ? ?? TOP ?? ? (MENU_TREE ??) */
	public const SUB_TOP_PAGE_MIDS = [
		'p8' => 'info',
		'p9' => 'info',
		'p79' => 'info',
		'p154' => 'info',
		'p155' => 'info',
		'p78' => 'info',
		'p12' => 'info',
		'p108' => 'info',
		'p147' => 'info',
		'p84' => 'news',
		'p25' => 'mission',
		'p26' => 'mission',
		'p91' => 'mission',
		'p92' => 'mission',
		'p93' => 'mission',
		'p146' => 'mission',
		'p109' => 'school',
		'p110' => 'school',
		'p111' => 'school',
		'p112' => 'school',
		'p113' => 'school',
		'p114' => 'school',
		'p115' => 'school',
		'p116' => 'school',
		'p117' => 'school',
		'p118' => 'school',
		'p119' => 'school',
		'p120' => 'school',
	];

	public static function adminMemberSrl(): int
	{
		$info = MemberModel::getMemberInfoByUserID(self::ADMIN_USER_ID);
		return $info ? (int)$info->member_srl : 0;
	}

	public static function isAuthenticated(): bool
	{
		if (empty($_SESSION[self::SESSION_KEY]))
		{
			return false;
		}
		$ts = (int)$_SESSION[self::SESSION_KEY];
		return (time() - $ts) < self::SESSION_TTL;
	}

	public static function setAuthenticated(bool $ok): void
	{
		if ($ok)
		{
			$_SESSION[self::SESSION_KEY] = time();
			$_SESSION['dmcadmin_member_srl'] = self::adminMemberSrl();
		}
		else
		{
			unset($_SESSION[self::SESSION_KEY], $_SESSION['dmcadmin_member_srl']);
		}
	}

	public static function requireAuth(): void
	{
		if (!self::isAuthenticated())
		{
			header('Location: ' . getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrLogin'));
			Context::close();
			exit;
		}
	}

	public static function verifyAdminCredentials(string $user_id, string $password): bool
	{
		$user_id = strtolower(trim($user_id));
		if ($user_id !== self::ADMIN_USER_ID)
		{
			return false;
		}

		$member = MemberModel::getMemberInfoByUserID(self::ADMIN_USER_ID);
		if (!$member)
		{
			return false;
		}

		if (MemberModel::isValidPassword($member->password, $password, $member->member_srl))
		{
			return true;
		}

		return self::verifyLegacyPassword($member, $password);
	}

	public static function getChurchConfig(): stdClass
	{
		$config = ModuleModel::getModuleConfig('church_write');
		if (!$config)
		{
			$config = new stdClass;
		}
		if (empty($config->prayer_reader_srls))
		{
			$config->prayer_reader_srls = [];
		}
		elseif (is_string($config->prayer_reader_srls))
		{
			$config->prayer_reader_srls = array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', $config->prayer_reader_srls))));
		}
		if (empty($config->prayer_notify_email))
		{
			$config->prayer_notify_email = '';
		}
		if (empty($config->main_slide_urls))
		{
			$config->main_slide_urls = [];
		}
		elseif (is_string($config->main_slide_urls))
		{
			$decoded = json_decode($config->main_slide_urls, true);
			$config->main_slide_urls = is_array($decoded) ? $decoded : [];
		}
		if (empty($config->sub_top_banner_urls))
		{
			$config->sub_top_banner_urls = [];
		}
		elseif (is_string($config->sub_top_banner_urls))
		{
			$decoded = json_decode($config->sub_top_banner_urls, true);
			$config->sub_top_banner_urls = is_array($decoded) ? $decoded : [];
		}
		if (empty($config->main_tiles))
		{
			$config->main_tiles = [];
		}
		elseif (is_string($config->main_tiles))
		{
			$decoded = json_decode($config->main_tiles, true);
			$config->main_tiles = is_array($decoded) ? $decoded : [];
		}
		if (empty($config->main_hero_images))
		{
			$config->main_hero_images = [];
		}
		elseif (is_string($config->main_hero_images))
		{
			$decoded = json_decode($config->main_hero_images, true);
			$config->main_hero_images = is_array($decoded) ? $decoded : [];
		}
		return $config;
	}

	/** @return string[] ????? ???? 4? URL (0~3) */
	public static function getMainSlideUrls(): array
	{
		$config = self::getChurchConfig();
		$urls = is_array($config->main_slide_urls) ? $config->main_slide_urls : [];
		$out = [];
		for ($i = 0; $i < self::MAIN_SLIDE_COUNT; $i++)
		{
			$out[$i] = isset($urls[$i]) ? trim((string)$urls[$i]) : '';
		}
		return $out;
	}

	public static function getMainSlideUploadDir(): string
	{
		return \RX_BASEDIR . 'files/church/main_slide';
	}

	public static function saveMainSlideUrls(array $urls): BaseObject
	{
		$config = self::getChurchConfig();
		$clean = [];
		for ($i = 0; $i < self::MAIN_SLIDE_COUNT; $i++)
		{
			$clean[$i] = isset($urls[$i]) ? trim((string)$urls[$i]) : '';
		}
		$config->main_slide_urls = $clean;
		$oModuleController = getController('module');
		return $oModuleController->insertModuleConfig('church_write', $config);
	}

	public static function applyMainSlidesToLayout(): void
	{
		$layout_info = Context::get('layout_info');
		if (!$layout_info)
		{
			return;
		}

		$slides = self::getMainSlideUrls();
		$has_any = false;
		for ($i = 0; $i < self::MAIN_SLIDE_COUNT; $i++)
		{
			$key = 'slide_img' . ($i + 1);
			if (!empty($slides[$i]))
			{
				$layout_info->$key = $slides[$i];
				$has_any = true;
			}
		}

		if ($has_any)
		{
			// 메인 대표사진은 church-main-slide 영역만 사용. 상단 visual 슬라이드는 비활성.
			$layout_info->use_slide = 'N';
			$layout_info->use_demo = 'N';
			Context::set('layout_info', $layout_info);
		}
	}

	public static function applyChurchLogoToLayout(): void
	{
		$logo = './files/church/logo.jpg';
		$path = self::urlToLocalPath($logo);
		if (!$path || !is_file($path))
		{
			return;
		}

		$title = Context::getSiteTitle() ?: '????';
		$layout_info = Context::get('layout_info');
		if ($layout_info)
		{
			$layout_info->logo_img = $logo;
			$layout_info->logo_image = $logo;
			$layout_info->logo_text = $title;
			Context::set('layout_info', $layout_info);
		}

		$safe = htmlspecialchars($logo, ENT_QUOTES, 'UTF-8');
		$safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
		Context::addHtmlFooter(
			'<script>(function(){function setLogo(){var src="' . $safe . '",alt="' . $safeTitle . '";'
			. 'var img=document.querySelector(".header .logo-item img")||document.querySelector(".hd .h1 img");'
			. 'if(img){img.src=src;img.alt=alt;return;}'
			. 'var link=document.querySelector(".hd .h1 a");'
			. 'if(link){link.innerHTML=\'<img src="\'+src+\'" alt="\'+alt+\'" />\';}}'
			. 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",setLogo);}else{setLogo();}})();</script>'
		);
	}

	/** @return array<string,string> ?? TOP ?? URL (menu key => url) */
	public static function getSubTopBannerUrls(): array
	{
		$config = self::getChurchConfig();
		$urls = is_array($config->sub_top_banner_urls) ? $config->sub_top_banner_urls : [];
		$out = [];
		foreach (self::SUB_TOP_MENUS as $key => $meta)
		{
			$out[$key] = isset($urls[$key]) ? trim((string)$urls[$key]) : '';
		}
		return $out;
	}

	public static function getSubTopUploadDir(): string
	{
		return \RX_BASEDIR . 'files/church/sub_top';
	}

	public static function getSubTopBannerSize(): array
	{
		$dir = self::getSubTopUploadDir();
		foreach (self::getSubTopBannerUrls() as $url)
		{
			$path = self::urlToLocalPath($url);
			if ($path && is_file($path))
			{
				$info = @getimagesize($path);
				if ($info)
				{
					return [(int)$info[0], (int)$info[1]];
				}
			}
		}

		foreach (self::SUB_TOP_MENUS as $meta)
		{
			$legacy = self::findLegacyBannerPath($meta['legacy']);
			if ($legacy && is_file($legacy))
			{
				$info = @getimagesize($legacy);
				if ($info)
				{
					return [(int)$info[0], (int)$info[1]];
				}
			}
		}

		return [self::SUB_TOP_BANNER_WIDTH, self::SUB_TOP_BANNER_HEIGHT];
	}

	public static function saveSubTopBannerUrls(array $urls): BaseObject
	{
		$config = self::getChurchConfig();
		$clean = [];
		foreach (self::SUB_TOP_MENUS as $key => $meta)
		{
			$clean[$key] = isset($urls[$key]) ? trim((string)$urls[$key]) : '';
		}
		$config->sub_top_banner_urls = $clean;
		$oModuleController = getController('module');
		return $oModuleController->insertModuleConfig('church_write', $config);
	}

	public static function importLegacySubTopBanners(bool $overwrite = false): array
	{
		$urls = self::getSubTopBannerUrls();
		$imported = [];
		$dir = self::getSubTopUploadDir();
		FileHandler::makeDir($dir);

		foreach (self::SUB_TOP_MENUS as $key => $meta)
		{
			if (!$overwrite && !empty($urls[$key]))
			{
				continue;
			}

			$src = self::findLegacyBannerPath($meta['legacy']);
			if (!$src || !is_file($src))
			{
				continue;
			}

			$ext = strtolower(pathinfo($src, PATHINFO_EXTENSION)) ?: 'jpg';
			$dest = $dir . '/' . $key . '.' . $ext;
			if (!@copy($src, $dest))
			{
				continue;
			}
			@chmod($dest, 0644);
			$urls[$key] = './files/church/sub_top/' . $key . '.' . $ext;
			$imported[] = $key;
		}

		if ($imported)
		{
			self::saveSubTopBannerUrls($urls);
		}

		return $imported;
	}

	public static function findLegacyBannerPath(string $filename): ?string
	{
		$candidates = [
			\RX_BASEDIR . 'files/church/sub_top_legacy/' . $filename,
			\RX_BASEDIR . '../rankup_backup/design/page/' . $filename,
			\RX_BASEDIR . '../../rankup_backup/design/page/' . $filename,
			'/root/church-web/rankup_backup/design/page/' . $filename,
		];
		foreach ($candidates as $path)
		{
			$real = realpath($path);
			if ($real && is_file($real))
			{
				return $real;
			}
		}
		return null;
	}

	public static function urlToLocalPath(string $url): ?string
	{
		if (!$url)
		{
			return null;
		}
		$path = preg_replace('/\?.*$/', '', $url);
		if (strpos($path, './') === 0)
		{
			return \RX_BASEDIR . substr($path, 2);
		}
		if (strpos($path, '/files/') === 0)
		{
			return \RX_BASEDIR . ltrim($path, '/');
		}
		return null;
	}

	public static function deleteSubTopBannerFile(string $url): void
	{
		$path = self::urlToLocalPath($url);
		if (!$path || !is_file($path))
		{
			return;
		}
		$dir = realpath(self::getSubTopUploadDir());
		$real = realpath($path);
		if ($dir && $real && strpos($real, $dir) === 0)
		{
			@unlink($real);
		}
	}

	/**
	 * @param string[] $sourcePaths
	 */
	public static function stitchSubTopImages(array $sourcePaths, string $destPath): void
	{
		if (!function_exists('imagecreatetruecolor'))
		{
			throw new Rhymix\Framework\Exception('?? GD ??? ???? ?? ????.');
		}

		$sourcePaths = array_values(array_filter($sourcePaths, function ($p) {
			return $p && is_file($p);
		}));
		$count = count($sourcePaths);
		if ($count < 2)
		{
			throw new Rhymix\Framework\Exception('?? TOP? ?? 2? ?? ?????.');
		}
		if ($count > self::SUB_TOP_STITCH_MAX)
		{
			throw new Rhymix\Framework\Exception('? ?? ?? ' . self::SUB_TOP_STITCH_MAX . '??? ?? ?? ? ????.');
		}

		[$targetW, $targetH] = self::getSubTopBannerSize();
		$sliceW = (int)floor($targetW / $count);
		$canvas = imagecreatetruecolor($targetW, $targetH);
		$white = imagecolorallocate($canvas, 255, 255, 255);
		imagefill($canvas, 0, 0, $white);

		foreach ($sourcePaths as $i => $src)
		{
			$img = self::loadImageResource($src);
			if (!$img)
			{
				imagedestroy($canvas);
				throw new Rhymix\Framework\Exception('??? ??? ??: ' . basename($src));
			}

			$sw = imagesx($img);
			$sh = imagesy($img);
			$scale = max($sliceW / $sw, $targetH / $sh);
			$nw = (int)round($sw * $scale);
			$nh = (int)round($sh * $scale);
			$resized = imagecreatetruecolor($nw, $nh);
			imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
			imagedestroy($img);

			$ox = (int)max(0, floor(($nw - $sliceW) / 2));
			$oy = (int)max(0, floor(($nh - $targetH) / 2));
			$dstX = $i * $sliceW;
			imagecopy($canvas, $resized, $dstX, 0, $ox, $oy, min($sliceW, $targetW - $dstX), $targetH);
			imagedestroy($resized);
		}

		$dir = dirname($destPath);
		FileHandler::makeDir($dir);
		if (!self::saveImageResource($canvas, $destPath))
		{
			imagedestroy($canvas);
			throw new Rhymix\Framework\Exception('?? ??? ??? ??????.');
		}
		imagedestroy($canvas);
		@chmod($destPath, 0644);
	}

	public static function saveUploadedSubTopSingle(string $tmpPath, string $destPath): void
	{
		$info = @getimagesize($tmpPath);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('??? ??? ?? ? ????.');
		}

		[$targetW, $targetH] = self::getSubTopBannerSize();
		$img = self::loadImageResource($tmpPath);
		if (!$img)
		{
			throw new Rhymix\Framework\Exception('??? ??? ??????.');
		}

		$sw = imagesx($img);
		$sh = imagesy($img);
		if ($sw === $targetW && $sh === $targetH)
		{
			$dir = dirname($destPath);
			FileHandler::makeDir($dir);
			if (!@copy($tmpPath, $destPath))
			{
				imagedestroy($img);
				throw new Rhymix\Framework\Exception('?? ??? ??????.');
			}
			imagedestroy($img);
			@chmod($destPath, 0644);
			return;
		}

		$scale = max($targetW / $sw, $targetH / $sh);
		$nw = (int)round($sw * $scale);
		$nh = (int)round($sh * $scale);
		$resized = imagecreatetruecolor($nw, $nh);
		imagecopyresampled($resized, $img, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
		imagedestroy($img);

		$canvas = imagecreatetruecolor($targetW, $targetH);
		$white = imagecolorallocate($canvas, 255, 255, 255);
		imagefill($canvas, 0, 0, $white);
		$ox = (int)max(0, floor(($nw - $targetW) / 2));
		$oy = (int)max(0, floor(($nh - $targetH) / 2));
		imagecopy($canvas, $resized, 0, 0, $ox, $oy, $targetW, $targetH);
		imagedestroy($resized);

		$dir = dirname($destPath);
		FileHandler::makeDir($dir);
		if (!self::saveImageResource($canvas, $destPath))
		{
			imagedestroy($canvas);
			throw new Rhymix\Framework\Exception('?? ??? ??????.');
		}
		imagedestroy($canvas);
		@chmod($destPath, 0644);
	}

	protected static function loadImageResource(string $path)
	{
		$info = @getimagesize($path);
		if (!$info)
		{
			return null;
		}
		switch ($info[2])
		{
			case IMAGETYPE_JPEG:
				return @imagecreatefromjpeg($path);
			case IMAGETYPE_PNG:
				$img = @imagecreatefrompng($path);
				if ($img)
				{
					imagealphablending($img, true);
				}
				return $img;
			case IMAGETYPE_GIF:
				return @imagecreatefromgif($path);
			case IMAGETYPE_WEBP:
				if (function_exists('imagecreatefromwebp'))
				{
					return @imagecreatefromwebp($path);
				}
				return null;
			default:
				return null;
		}
	}

	protected static function saveImageResource($img, string $path): bool
	{
		$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		switch ($ext)
		{
			case 'png':
				return imagepng($img, $path, 8);
			case 'gif':
				return imagegif($img, $path);
			case 'webp':
				if (function_exists('imagewebp'))
				{
					return imagewebp($img, $path, 90);
				}
				return false;
			default:
				return imagejpeg($img, $path, 90);
		}
	}

	public static function getCurrentPageMid(): string
	{
		$mid = trim((string)Context::get('mid'));
		if ($mid !== '')
		{
			return $mid;
		}

		$module_info = Context::get('module_info');
		if ($module_info && !empty($module_info->mid))
		{
			return trim((string)$module_info->mid);
		}

		$path = parse_url(Context::getRequestUri(), PHP_URL_PATH);
		$path = trim((string)$path, '/');
		if ($path !== '' && $path !== 'index' && preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $path))
		{
			return $path;
		}

		return '';
	}

	public static function detectSubTopMenuKey(): ?string
	{
		$mid = self::getCurrentPageMid();
		if ($mid !== '' && (isset(self::SUB_TOP_PAGE_MIDS[$mid]) || self::isDomesticMissionSubMid($mid) || self::isOverseasMissionSubMid($mid)))
		{
			if (self::isDomesticMissionSubMid($mid) || self::isOverseasMissionSubMid($mid))
			{
				return 'mission';
			}
			return self::SUB_TOP_PAGE_MIDS[$mid];
		}

		$label = self::detectSelectedTopMenuLabel();
		if ($label)
		{
			$key = self::menuLabelToKey($label);
			if ($key)
			{
				return $key;
			}
		}

		$mid_map = [
			'jubo' => 'news',
			'newface' => 'news',
			'sermon' => 'broadcast',
			'eventvideo' => 'broadcast',
			'choir' => 'broadcast',
			'peniel' => 'broadcast',
			'community' => 'community',
			'pray' => 'community',
			'picture' => 'community',
		];
		return $mid_map[$mid] ?? null;
	}

	public static function subTopBannerUrlForCss(string $url): string
	{
		$url = preg_replace('/\?.*$/', '', trim($url));
		if ($url === '')
		{
			return '';
		}
		if (strpos($url, './') === 0)
		{
			return substr($url, 1);
		}
		if (strpos($url, '/files/') === 0)
		{
			return $url;
		}
		return $url;
	}

	public static function getMemberPageBannerUrl(): string
	{
		$urls = self::getSubTopBannerUrls();
		$url = (string)($urls['info'] ?? './files/church/sub_top/info.jpg');
		return self::subTopBannerUrlForCss($url);
	}

	public static function getSubTopBannerUrlForLayout(?string $mid): string
	{
		$mid = trim((string)$mid);
		if ($mid === '' || $mid === 'index' || $mid === 'dmcadmin')
		{
			return '';
		}
		if (!isset(self::SUB_TOP_PAGE_MIDS[$mid]) && !self::isDomesticMissionSubMid($mid) && !self::isOverseasMissionSubMid($mid))
		{
			$board_map = [
				'jubo' => 'news',
				'newface' => 'news',
				'sermon' => 'broadcast',
				'eventvideo' => 'broadcast',
				'choir' => 'broadcast',
				'peniel' => 'broadcast',
				'community' => 'community',
				'pray' => 'community',
				'picture' => 'community',
			];
			if (!isset($board_map[$mid]))
			{
				return '';
			}
		}
		Context::set('mid', $mid);
		return self::getSubTopBannerUrlForCurrentMid();
	}

	public static function getSubTopBannerUrlForCurrentMid(): string
	{
		$key = self::detectSubTopMenuKey();
		if (!$key)
		{
			return '';
		}
		$urls = self::getSubTopBannerUrls();
		return self::subTopBannerUrlForCss((string)($urls[$key] ?? ''));
	}

	protected static function detectSelectedTopMenuLabel(): ?string
	{
		foreach (['global_menu', 'main_menu'] as $var)
		{
			$menu = Context::get($var);
			if (!$menu || empty($menu->list) || !is_array($menu->list))
			{
				continue;
			}
			foreach ($menu->list as $item)
			{
				if (!empty($item['selected']))
				{
					return self::normalizeMenuLabel($item['link'] ?? '');
				}
				if (!empty($item['list']) && is_array($item['list']))
				{
					foreach ($item['list'] as $child)
					{
						if (!empty($child['selected']))
						{
							return self::normalizeMenuLabel($item['link'] ?? '');
						}
					}
				}
			}
		}
		return null;
	}

	/** ?? ???? ???? ?? ?? ?? ?? (?? ????) */
	public static function detectDeepestSelectedMenuLabel(): ?string
	{
		foreach (['global_menu', 'main_menu'] as $var)
		{
			$menu = Context::get($var);
			if (!$menu || empty($menu->list) || !is_array($menu->list))
			{
				continue;
			}
			foreach ($menu->list as $item1)
			{
				if (empty($item1['list']) || !is_array($item1['list']))
				{
					if (!empty($item1['selected']))
					{
						return self::stripMenuLabel($item1['link'] ?? '');
					}
					continue;
				}
				foreach ($item1['list'] as $item2)
				{
					if (!empty($item2['list']) && is_array($item2['list']))
					{
						foreach ($item2['list'] as $item3)
						{
							if (!empty($item3['selected']))
							{
								$deepest = self::stripMenuLabel($item3['link'] ?? '');
								if ($deepest === '??')
								{
									$parent = self::stripMenuLabel($item2['link'] ?? '');
									if ($parent !== '')
									{
										return $parent . ' ' . $deepest;
									}
								}
								return $deepest;
							}
						}
					}
					if (!empty($item2['selected']))
					{
						return self::stripMenuLabel($item2['link'] ?? '');
					}
				}
				if (!empty($item1['selected']))
				{
					return self::stripMenuLabel($item1['link'] ?? '');
				}
			}
		}
		return null;
	}

	protected static function stripMenuLabel(string $label): string
	{
		$label = html_entity_decode(strip_tags($label), ENT_QUOTES, 'UTF-8');
		return trim($label);
	}

	protected static function normalizeMenuLabel(string $label): string
	{
		$label = html_entity_decode(strip_tags($label), ENT_QUOTES, 'UTF-8');
		return trim(preg_replace('/\s+/u', '', $label));
	}

	protected static function menuLabelToKey(string $label): ?string
	{
		$label = self::normalizeMenuLabel($label);
		foreach (self::SUB_TOP_MENUS as $key => $meta)
		{
			$candidate = self::normalizeMenuLabel($meta['label']);
			if ($label === $candidate || mb_strpos($label, $candidate) !== false || mb_strpos($candidate, $label) !== false)
			{
				return $key;
			}
		}
		return null;
	}

	public static function applySubTopBannerToLayout(): void
	{
		$mid = self::getCurrentPageMid();
		if (!$mid || $mid === 'index' || $mid === 'dmcadmin')
		{
			return;
		}

		$key = self::detectSubTopMenuKey();
		if (!$key)
		{
			return;
		}

		$urls = self::getSubTopBannerUrls();
		$url = self::subTopBannerUrlForCss($urls[$key] ?? '');
		if ($url === '')
		{
			return;
		}

		$safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
		Context::set('church_sub_top_banner_url', $safe);
	}

	/** ????? ????? ?? ?? (layout church_sub_title.inc.html ? ?? ??) */
	public static function getSubPageTitleForMid(?string $mid = null): ?string
	{
		if ($mid === null || $mid === '')
		{
			$mid = self::getCurrentPageMid();
		}
		$mid = trim((string)$mid);
		if ($mid === '' || $mid === 'index' || $mid === 'dmcadmin')
		{
			return null;
		}
		if (self::isDongkeydayPage($mid))
		{
			return self::getDongkeydayPageLabel($mid);
		}
		if (isset(self::SCHOOL_PAGE_MIDS[$mid]))
		{
			$L = self::uiLabels();
			$school_mids = (array)($L['school_page_mids'] ?? self::SCHOOL_PAGE_MIDS);
			$suffix = (string)($L['misc']['school_intro_suffix'] ?? ' ??');
			$dept = (string)($school_mids[$mid] ?? self::SCHOOL_PAGE_MIDS[$mid] ?? '');
			return $dept . $suffix;
		}
		$dm_title = self::getDomesticMissionSubTitle($mid);
		if ($dm_title)
		{
			return $dm_title;
		}
		$om_title = self::getOverseasMissionSubTitle($mid);
		if ($om_title)
		{
			return $om_title;
		}
		return self::detectDeepestSelectedMenuLabel();
	}

	public static function applySubPageTitle(): void
	{
		$mid = self::getCurrentPageMid();
		if (!$mid || $mid === 'index' || $mid === 'dmcadmin')
		{
			return;
		}

		$title = self::getSubPageTitleForMid($mid);
		if (!$title)
		{
			return;
		}

		$module_info = Context::get('module_info');
		if ($module_info)
		{
			$module_info->browser_title = $title;
			Context::set('module_info', $module_info);
		}
		Context::set('sub_header_title', $title);
	}

	public static function saveChurchConfig(array $data): BaseObject
	{
		$config = self::getChurchConfig();
		if (isset($data['prayer_notify_email']))
		{
			$config->prayer_notify_email = trim($data['prayer_notify_email']);
		}
		if (isset($data['prayer_reader_srls']))
		{
			$readers = array_values(array_unique(array_filter(array_map('intval', $data['prayer_reader_srls']))));
			if (count($readers) > self::MAX_PRAYER_READERS)
			{
				return new BaseObject(-1, '??? ID? ?? ' . self::MAX_PRAYER_READERS . '??? ??? ? ????.');
			}
			$config->prayer_reader_srls = $readers;
		}

		$oModuleController = getController('module');
		return $oModuleController->insertModuleConfig('church_write', $config);
	}

	public static function mysqlOldPassword(string $password): string
	{
		$nr = 1345345333;
		$add = 7;
		$nr2 = 0x12345671;
		$len = strlen($password);
		for ($i = 0; $i < $len; $i++)
		{
			$byte = ord($password[$i]);
			if ($byte === 0 || $byte === 32)
			{
				continue;
			}
			$nr ^= ((($nr & 63) + $add) * $byte) + (($nr << 8) & 0xFFFFFFFF);
			$nr2 += (($nr2 << 8) & 0xFFFFFFFF) ^ $nr;
			$add += $byte;
		}
		return sprintf('%08x%08x', $nr & 0x7FFFFFFF, $nr2 & 0x7FFFFFFF);
	}

	public static function getMemberExtra($member): stdClass
	{
		if ($member && !empty($member->member_srl))
		{
			getModel('church_member');
			return church_memberModel::loadExtraBySrl((int)$member->member_srl);
		}

		$extra = new stdClass;
		if (!$member || empty($member->extra_vars))
		{
			return $extra;
		}
		if (is_string($member->extra_vars))
		{
			$parsed = @unserialize($member->extra_vars);
			if ($parsed instanceof stdClass)
			{
				return $parsed;
			}
			$json = json_decode($member->extra_vars);
			if ($json instanceof stdClass)
			{
				return $json;
			}
		}
		elseif ($member->extra_vars instanceof stdClass)
		{
			return $member->extra_vars;
		}
		return $extra;
	}

	public static function verifyLegacyPassword($member, string $password): bool
	{
		$extra = self::getMemberExtra($member);
		if (empty($extra->rankup_passwd))
		{
			return false;
		}
		return strtolower($extra->rankup_passwd) === strtolower(self::mysqlOldPassword($password));
	}

	public static function upgradePassword(int $member_srl, string $password): void
	{
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->password = $password;
		MemberController::getInstance()->updateMemberPassword($args);
	}

	public static function computeAge(?string $birthday): string
	{
		$birthday = preg_replace('/\D/', '', (string)$birthday);
		if (strlen($birthday) < 8)
		{
			return '';
		}
		$y = (int)substr($birthday, 0, 4);
		$m = (int)substr($birthday, 4, 2);
		$d = (int)substr($birthday, 6, 2);
		if ($y < 1900 || $y > (int)date('Y'))
		{
			return '';
		}
		$now = (int)date('Ymd');
		$bd = $y * 10000 + $m * 100 + $d;
		$age = intdiv($now - $bd, 10000);
		return $age >= 0 ? (string)$age : '';
	}

	public static function genderLabel($gender): string
	{
		$g = self::normalizeGenderValue($gender);
		if ($g === '1')
		{
			return '남';
		}
		if ($g === '2')
		{
			return '여';
		}
		$raw = trim((string)$gender);
		return $raw !== '' ? $raw : '-';
	}

	public static function normalizeGenderValue($gender): string
	{
		$g = trim((string)$gender);
		if ($g === '1' || strtoupper($g) === 'M' || $g === '남')
		{
			return '1';
		}
		if ($g === '2' || strtoupper($g) === 'F' || $g === '여')
		{
			return '2';
		}
		return $g;
	}

	public static function emailVerifyStatus($member, $extra = null): stdClass
	{
		getModel('church_member');
		$o = new stdClass;
		if (church_memberModel::isExemptMember($member))
		{
			$o->code = 'exempt';
			$o->label = '해당없음';
			return $o;
		}
		if (church_memberModel::needsEmailVerification($member))
		{
			$o->code = 'pending';
			$o->label = '미완료';
			return $o;
		}
		$o->code = 'done';
		$o->label = '완료';
		return $o;
	}

	public static function canViewMemberSecrets(): bool
	{
		if (!self::isAuthenticated())
		{
			return false;
		}
		$logged = Context::get('logged_info');
		if ($logged && strtolower((string)($logged->user_id ?? '')) === self::ADMIN_USER_ID)
		{
			return true;
		}
		return self::isAuthenticated();
	}

	public static function getMemberList(string $search = ''): array
	{
		$args = new stdClass;
		$args->list_count = 500;
		$args->page = 1;
		$args->sort_index = 'user_id';
		$args->sort_order = 'asc';

		$search = trim($search);
		if ($search !== '')
		{
			$like = '%' . $search . '%';
			$args->s_user_id = $like;
			$args->s_user_name = $like;
			$args->s_nick_name = $like;
			$args->s_email_address = $like;
			$args->s_phone_number = $like;
			$args->s_extra_vars = $like;
		}

		$output = executeQueryArray('member.getMemberList', $args);
		if (!$output->toBool() || empty($output->data))
		{
			return [];
		}

		$list = [];
		foreach ($output->data as $member)
		{
			if (!$member || empty($member->member_srl))
			{
				continue;
			}
			$extra = self::getMemberExtra($member);
			$item = new stdClass;
			$item->member_srl = (int)$member->member_srl;
			$item->user_id = $member->user_id;
			$item->user_name = $member->user_name;
			$item->nick_name = $member->nick_name;
			$item->email_address = $member->email_address;
			$item->phone_number = $member->phone_number ?? '';
			$item->phone = $extra->phone ?? '';
			$item->birthday = $member->birthday ?? ($extra->birthday ?? '');
			$item->age = self::computeAge($item->birthday);
			$item->gender = self::genderLabel($extra->gender ?? '');
			$item->zipcode = $extra->zipcode ?? '';
			$item->address1 = $extra->address1 ?? '';
			$item->address2 = $extra->address2 ?? '';
			$item->status = $member->status ?? '';
			$item->denied = $member->denied ?? 'N';
			$item->legacy_passwd = $extra->rankup_passwd ?? '';
			$item->pending_email = $extra->pending_email ?? '';
			$verify = self::emailVerifyStatus($member, $extra);
			$item->email_verify_code = $verify->code;
			$item->email_verify_label = $verify->label;
			$item->groups = MemberModel::getMemberGroups($member->member_srl);
			$list[] = $item;
		}
		return $list;
	}

	public static function getMemberFormData(int $member_srl = 0): ?stdClass
	{
		if (!$member_srl)
		{
			$o = new stdClass;
			$o->member_srl = 0;
			$o->user_id = '';
			$o->password = '';
			$o->user_name = '';
			$o->nick_name = '';
			$o->email_address = '';
			$o->phone_number = '';
			$o->phone = '';
			$o->birthday = '';
			$o->gender = '';
			$o->zipcode = '';
			$o->address1 = '';
			$o->address2 = '';
			$o->group_srl = 3;
			$o->denied = 'N';
			$o->status = 'APPROVED';
			return $o;
		}

		$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
		if (!$member)
		{
			return null;
		}
		$extra = self::getMemberExtra($member);
		$groups = MemberModel::getMemberGroups($member_srl);
		$group_srl = 3;
		foreach ([2, 4, 3] as $g)
		{
			if (isset($groups[$g]))
			{
				$group_srl = $g;
				break;
			}
		}

		$o = new stdClass;
		$o->member_srl = (int)$member->member_srl;
		$o->user_id = $member->user_id;
		$o->password = '';
		$o->user_name = $member->user_name;
		$o->nick_name = $member->nick_name;
		$o->email_address = $member->email_address;
		$o->phone_number = $member->phone_number ?? '';
		$o->phone = $extra->phone ?? '';
		$o->birthday = $member->birthday ?? ($extra->birthday ?? '');
		$o->gender = self::normalizeGenderValue($extra->gender ?? '');
		$o->zipcode = $extra->zipcode ?? '';
		$o->address1 = $extra->address1 ?? '';
		$o->address2 = $extra->address2 ?? '';
		$o->group_srl = $group_srl;
		$o->denied = $member->denied ?? 'N';
		$o->status = $member->status ?? 'APPROVED';
		$o->legacy_passwd = $extra->rankup_passwd ?? '';
		$o->email_verified = ($extra->email_verified ?? '') === 'Y' ? 'Y' : 'N';
		$o->pending_email = $extra->pending_email ?? '';
		$verify = self::emailVerifyStatus($member, $extra);
		$o->email_verify_code = $verify->code;
		$o->email_verify_label = $verify->label;
		return $o;
	}

	public static function buildExtraVars($input, $existing = null): stdClass
	{
		$extra = $existing instanceof stdClass ? clone $existing : new stdClass;
		foreach (['phone', 'zipcode', 'address1', 'address2', 'gender', 'baptismalname'] as $key)
		{
			if (isset($input->$key))
			{
				$extra->$key = trim($input->$key);
			}
		}
		if (!empty($input->birthday))
		{
			$extra->birthday = preg_replace('/\D/', '', $input->birthday);
		}
		return $extra;
	}

	public static function getReaderMemberOptions(): array
	{
		$list = self::getMemberList('');
		$options = [];
		foreach ($list as $m)
		{
			if ($m->user_id === self::ADMIN_USER_ID)
			{
				continue;
			}
			$options[] = $m;
		}
		return $options;
	}

	public static function buildMainTargetUrl(string $target, string $id): string
	{
		$target = strtolower(trim($target));
		$id = trim($id);
		if ($id === '')
		{
			return getNotEncodedUrl('', 'mid', 'index');
		}
		if ($target === 'mid')
		{
			return getNotEncodedUrl('', 'mid', $id);
		}
		if ($target === 'page')
		{
			return getNotEncodedUrl('', 'mid', 'p' . $id);
		}
		if (preg_match('#^https?://#i', $id))
		{
			return $id;
		}
		return getNotEncodedUrl('', 'mid', $id);
	}

	public static function getMainTileUploadDir(): string
	{
		return \RX_BASEDIR . 'files/church/main_tile';
	}

	/** @return array<string,array{image_url:string,link_url:string}> */
	public static function getMainTileData(): array
	{
		$config = self::getChurchConfig();
		$stored = is_array($config->main_tiles) ? $config->main_tiles : [];
		$out = [];
		foreach (self::MAIN_TILES as $key => $meta)
		{
			$row = is_array($stored[$key] ?? null) ? $stored[$key] : [];
			$out[$key] = [
				'image_url' => trim((string)($row['image_url'] ?? '')),
				'link_url' => trim((string)($row['link_url'] ?? '')),
			];
		}
		return $out;
	}

	public static function saveMainTileData(array $tiles): BaseObject
	{
		$config = self::getChurchConfig();
		$clean = [];
		foreach (self::MAIN_TILES as $key => $meta)
		{
			$row = is_array($tiles[$key] ?? null) ? $tiles[$key] : [];
			$clean[$key] = [
				'image_url' => trim((string)($row['image_url'] ?? '')),
				'link_url' => trim((string)($row['link_url'] ?? '')),
			];
		}
		$config->main_tiles = $clean;
		$oModuleController = getController('module');
		return $oModuleController->insertModuleConfig('church_write', $config);
	}

	public static function resolveMainTileLink(string $key, array $meta, array $row): string
	{
		if (!empty($row['link_url']))
		{
			return $row['link_url'];
		}
		return self::buildMainTargetUrl($meta['target'], $meta['id']);
	}

	/** @return array<string,string> */
	public static function getMainHeroImages(): array
	{
		$config = self::getChurchConfig();
		$stored = is_array($config->main_hero_images) ? $config->main_hero_images : [];
		$defaults = [
			'pastor' => './files/church/main_hero/pastor.png',
			'quick_1' => './files/church/main_hero/quick_1.png',
			'quick_2' => './files/church/main_hero/quick_2.png',
			'quick_3' => './files/church/main_hero/quick_3.png',
			'quick_4' => './files/church/main_hero/quick_4.png',
		];
		$out = [];
		foreach ($defaults as $key => $fallback)
		{
			$url = trim((string)($stored[$key] ?? ''));
			$out[$key] = $url !== '' ? $url : $fallback;
		}
		return $out;
	}

	public static function normalizePublicUrl(string $url): string
	{
		$url = trim($url);
		if ($url === '')
		{
			return '';
		}
		if (preg_match('#^https?://#i', $url))
		{
			return $url;
		}
		if (strpos($url, './') === 0)
		{
			$url = substr($url, 1);
		}
		if ($url !== '' && $url[0] !== '/')
		{
			$url = '/' . $url;
		}
		return $url;
	}

	public static function renderMainSlideHtml(): string
	{
		$slides = array_values(array_filter(self::getMainSlideUrls()));
		if (!$slides)
		{
			return '<div class="church-main-slide church-main-slide--empty"><span>사진 미등록</span></div>';
		}

		$html = '<div class="church-main-slide" id="churchMainSlide">';
		foreach ($slides as $idx => $url)
		{
			$safe = htmlspecialchars(self::normalizePublicUrl($url), ENT_QUOTES, 'UTF-8');
			$active = $idx === 0 ? ' is-active' : '';
			$style = 'background-image:url(\'' . $safe . '\')';
			if ($idx === 0)
			{
				$style .= ';opacity:1;z-index:2';
			}
			$html .= '<div class="church-slide-frame' . $active . '" style="' . $style . '"'
				. ' role="img" aria-label="대표사진 ' . ($idx + 1) . '"></div>';
		}
		$html .= '<div class="church-slide-dots">';
		foreach ($slides as $idx => $url)
		{
			$active = $idx === 0 ? ' is-active' : '';
			$html .= '<button type="button" class="church-slide-dot' . $active . '" data-index="' . (int)$idx . '" aria-label="사진 ' . ($idx + 1) . '"></button>';
		}
		$html .= '</div></div>';
		$html .= '<script>(function(){var root=document.getElementById("churchMainSlide");if(!root)return;var frames=root.querySelectorAll(".church-slide-frame");var dots=root.querySelectorAll(".church-slide-dot");if(frames.length<2)return;var idx=0,timer=null,paused=false;function show(n){idx=((n%frames.length)+frames.length)%frames.length;for(var i=0;i<frames.length;i++){frames[i].classList.toggle("is-active",i===idx);if(dots[i])dots[i].classList.toggle("is-active",i===idx);}}function next(){if(!paused)show(idx+1);}function start(){if(timer)clearInterval(timer);timer=setInterval(next,2000);}for(var d=0;d<dots.length;d++){(function(di){dots[di].addEventListener("click",function(){show(di);start();});})(d);}root.addEventListener("mouseenter",function(){paused=true;});root.addEventListener("mouseleave",function(){paused=false;});start();})();</script>';
		return $html;
	}

	public static function renderMainHomeHtml(): string
	{
		$tiles = self::getMainTileData();
		$hero = self::getMainHeroImages();
		$html = '<div class="church-home-extra">';
		$html .= '<div class="church-main-hero-row">';
		$html .= '<div class="church-main-slide-col">' . self::renderMainSlideHtml() . '</div>';
		$html .= '<div class="church-main-quicklinks">';
		$i = 0;
		foreach (self::MAIN_QUICK_LINKS as $link)
		{
			$i++;
			$url = self::buildMainTargetUrl($link['target'], $link['id']);
			$safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
			$img_key = 'quick_' . $i;
			$img_url = $hero[$img_key] ?? '';
			$img_path = self::urlToLocalPath($img_url);
			if ($img_path && is_file($img_path))
			{
				$img = htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8');
				$html .= '<a class="church-main-quicklink church-main-quicklink--img" href="' . $safe_url . '">'
					. '<img src="' . $img . '" alt="' . htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '" /></a>';
			}
			else
			{
				$html .= '<a class="church-main-quicklink" href="' . $safe_url . '">'
					. htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') . '</a>';
			}
		}
		$html .= '</div>';
		$pastor_url = self::buildMainTargetUrl('page', '8');
		$pastor_img = $hero['pastor'] ?? '';
		$pastor_path = self::urlToLocalPath($pastor_img);
		$html .= '<a class="church-main-pastor' . ($pastor_path && is_file($pastor_path) ? ' church-main-pastor--img' : '') . '" href="'
			. htmlspecialchars($pastor_url, ENT_QUOTES, 'UTF-8') . '"';
		if ($pastor_path && is_file($pastor_path))
		{
			$html .= ' style="background-image:url(\'' . htmlspecialchars($pastor_img, ENT_QUOTES, 'UTF-8') . '\')"';
		}
		$html .= '><span class="church-main-pastor-title">???? ?? ? ????</span>'
			. '<span class="church-main-pastor-sub">????? ?????.</span></a>';
		$html .= '</div>';
		$html .= '<div class="church-main-tiles-grid">';
		foreach (self::MAIN_TILES as $key => $meta)
		{
			$row = $tiles[$key] ?? ['image_url' => '', 'link_url' => ''];
			$url = self::resolveMainTileLink($key, $meta, $row);
			$label = htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8');
			$safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
			$html .= '<a class="church-main-tile church-main-tile--' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" href="' . $safe_url . '">';
			if (!empty($row['image_url']))
			{
				$img = htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8');
				$html .= '<span class="church-main-tile-bg" style="background-image:url(\'' . $img . '\')"></span>';
				$html .= '<span class="church-main-tile-label church-main-tile-label--sr">' . $label . '</span>';
			}
			else
			{
				$html .= '<span class="church-main-tile-label">' . $label . '</span>';
			}
			$html .= '</a>';
		}
		$html .= '</div></div>';
		return $html;
	}

	public static function injectMainHomeContent(): void
	{
		Context::loadFile('./addons/church_main_tiles/church_main_tiles.css');
		$html = self::renderMainHomeHtml();
		Context::addHtmlFooter($html);
	}

	/** dmcadmin ?? ??? ?? ?? (??? ??) */
	public const GUIDE_PAGE_MIDS = [
		'p8' => '???? ??',
	];

	public const GUIDE_PAGE_MAX_SECTIONS = 24;

	public static function isGuidePage(string $mid): bool
	{
		return isset(self::GUIDE_PAGE_MIDS[$mid]);
	}

	public static function getGuidePageUploadDir(string $mid): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return \RX_BASEDIR . 'files/church/guide/' . $safe;
	}

	public static function getGuidePagesFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/guide_pages.json';
	}

	/** @return array<string,array> */
	public static function getAllGuidePages(): array
	{
		$path = self::getGuidePagesFilePath();
		if (!is_file($path))
		{
			return [];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function saveAllGuidePages(array $all): bool
	{
		$path = self::getGuidePagesFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		@chmod($path, 0644);
		return true;
	}

	public static function normalizeGuidePhotoUrl(string $url): string
	{
		$url = trim($url);
		if ($url === '')
		{
			return '';
		}
		if (strpos($url, './files/') === 0)
		{
			return $url;
		}
		if (strpos($url, '/files/') === 0)
		{
			return '.' . $url;
		}
		return $url;
	}

	/** @return array{page_title:string,hero_photo:string,sections:array<int,array{subtitle:string,summary:string,body:string}>} */
	public static function getGuidePageData(string $mid): array
	{
		$all = self::getAllGuidePages();
		$row = is_array($all[$mid] ?? null) ? $all[$mid] : [];
		$sections = [];
		foreach ((array)($row['sections'] ?? []) as $section)
		{
			if (!is_array($section))
			{
				continue;
			}
			$subtitle = trim((string)($section['subtitle'] ?? ''));
			$summary = trim((string)($section['summary'] ?? ''));
			$body = trim((string)($section['body'] ?? ''));
			if ($subtitle === '' && $summary === '' && $body === '')
			{
				continue;
			}
			$sections[] = [
				'subtitle' => $subtitle,
				'summary' => $summary,
				'body' => $body,
			];
		}
		if (!$sections)
		{
			$sections[] = ['subtitle' => '', 'summary' => '', 'body' => ''];
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::GUIDE_PAGE_MIDS[$mid] ?? '')),
			'hero_photo' => self::normalizeGuidePhotoUrl((string)($row['hero_photo'] ?? '')),
			'catchphrase' => trim((string)($row['catchphrase'] ?? '')),
			'sections' => $sections,
		];
	}

	public static function saveGuidePageData(string $mid, array $data): BaseObject
	{
		if (!self::isGuidePage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$all = self::getAllGuidePages();
		$sections = [];
		foreach ((array)($data['sections'] ?? []) as $section)
		{
			if (!is_array($section))
			{
				continue;
			}
			$subtitle = trim((string)($section['subtitle'] ?? ''));
			$summary = trim((string)($section['summary'] ?? ''));
			$body = trim((string)($section['body'] ?? ''));
			if ($subtitle === '' && $summary === '' && $body === '')
			{
				continue;
			}
			$sections[] = [
				'subtitle' => $subtitle,
				'summary' => $summary,
				'body' => $body,
			];
		}
		$all[$mid] = [
			'page_title' => trim((string)($data['page_title'] ?? '')),
			'hero_photo' => self::normalizeGuidePhotoUrl((string)($data['hero_photo'] ?? '')),
			'catchphrase' => trim((string)($data['catchphrase'] ?? '')),
			'sections' => $sections,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveAllGuidePages($all))
		{
			return new BaseObject(-1, '?? ??? ??? ??? ??????.');
		}
		return new BaseObject();
	}

	public static function renderGuidePage(array $data): string
	{
		$photo = trim((string)($data['hero_photo'] ?? ''));

		// ??? ?? ??(visual.sub)?? ?? ????? ????? ??(?? ??)
		$html = '<article class="church-guide-page">';
		$portrait_html = '';
		if ($photo !== '')
		{
			$safe = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
			$html .= '<div class="church-guide-bg" style="background-image:url(\'' . $safe . '\')" aria-hidden="true"></div>';
			$portrait_html = '<figure class="church-guide-portrait"><img src="' . $safe . '" alt="" loading="lazy" /></figure>';
		}

		$html .= '<div class="church-guide-sections">';
		$portrait_injected = false;
		foreach ((array)($data['sections'] ?? []) as $section)
		{
			if (!is_array($section))
			{
				continue;
			}
			$subtitle = trim((string)($section['subtitle'] ?? ''));
			$summary = trim((string)($section['summary'] ?? ''));
			$body = trim((string)($section['body'] ?? ''));
			if ($subtitle === '' && $summary === '' && $body === '')
			{
				continue;
			}
			$html .= '<section class="church-guide-section">';
			if ($subtitle !== '')
			{
				$html .= '<h2 class="church-guide-subtitle">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</h2>';
			}
			if ($summary !== '')
			{
				$html .= '<p class="church-guide-summary">' . nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')) . '</p>';
			}
			if ($body !== '')
			{
				$chunks = preg_split('/\n{2,}/', $body) ?: [$body];
				$html .= '<div class="church-guide-body">';
				// ?? ??? ? ??? ?? ?? ? ???? ?? (?? ?????? ??)
				if ($portrait_html !== '' && !$portrait_injected)
				{
					$html .= $portrait_html;
					$portrait_injected = true;
				}
				foreach ($chunks as $chunk)
				{
					$chunk = trim($chunk);
					if ($chunk === '')
					{
						continue;
					}
					$html .= '<p>' . nl2br(htmlspecialchars($chunk, ENT_QUOTES, 'UTF-8')) . '</p>';
				}
				$html .= '</div>';
			}
			$html .= '</section>';
		}
		$html .= '</div>';

		$catchphrase = trim((string)($data['catchphrase'] ?? ''));
		if ($catchphrase !== '')
		{
			$html .= '<p class="church-guide-catchphrase">' . nl2br(htmlspecialchars($catchphrase, ENT_QUOTES, 'UTF-8')) . '</p>';
		}

		$html .= '</article>';
		return $html;
	}

	public static function getPageModuleSrl(string $mid): int
	{
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByMid($mid);
		if (!$module_info || $module_info->module !== 'page')
		{
			return 0;
		}
		return (int)$module_info->module_srl;
	}

	public static function publishGuidePage(string $mid, array $data): BaseObject
	{
		if (!self::isGuidePage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ?? ? ????.');
		}

		$output = self::saveGuidePageData($mid, $data);
		if (!$output->toBool())
		{
			return $output;
		}

		$html = self::renderGuidePage($data);
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}

		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	/** @return array<int,object> */
	public static function getInfoPageList(): array
	{
		$L = self::uiLabels();
		$kinds = is_array($L['info_page_kinds'] ?? null) ? $L['info_page_kinds'] : [];
		$out = [];

		foreach ((array)($L['guide_page_mids'] ?? self::GUIDE_PAGE_MIDS) as $mid => $label)
		{
			$o = new stdClass;
			$o->mid = $mid;
			$o->label = $label;
			$o->kind = (string)($kinds['guide'] ?? '???? ???');
			$o->view_url = getNotEncodedUrl('', 'mid', $mid);
			$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrInfoPageEdit', 'page_mid', $mid);
			$out[] = $o;
		}
		foreach ((array)($L['history_page_mids'] ?? self::HISTORY_PAGE_MIDS) as $mid => $label)
		{
			$o = new stdClass;
			$o->mid = $mid;
			$o->label = $label;
			$o->kind = (string)($kinds['history'] ?? '??? (????????)');
			$o->view_url = getNotEncodedUrl('', 'mid', $mid);
			$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrHistoryPageEdit', 'page_mid', $mid);
			$out[] = $o;
		}
		foreach ((array)($L['people_page_mids'] ?? self::PEOPLE_PAGE_MIDS) as $mid => $label)
		{
			$o = new stdClass;
			$o->mid = $mid;
			$o->label = $label;
			$o->kind = (string)($kinds['people'] ?? '??? (?????? ??)');
			$o->view_url = getNotEncodedUrl('', 'mid', $mid);
			$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrPeoplePageEdit', 'page_mid', $mid);
			$out[] = $o;
		}
		foreach ((array)($L['worship_page_mids'] ?? self::WORSHIP_PAGE_MIDS) as $mid => $label)
		{
			$o = new stdClass;
			$o->mid = $mid;
			$o->label = $label;
			$o->kind = (string)($kinds['worship'] ?? '????? (??? ?)');
			$o->view_url = getNotEncodedUrl('', 'mid', $mid);
			$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrWorshipPageEdit', 'page_mid', $mid);
			$out[] = $o;
		}
		foreach ((array)($L['newfamily_page_mids'] ?? self::NEWFAMILY_PAGE_MIDS) as $mid => $label)
		{
			$o = new stdClass;
			$o->mid = $mid;
			$o->label = $label;
			$o->kind = (string)($kinds['newfamily'] ?? '???? (?? 2? + ????)');
			$o->view_url = getNotEncodedUrl('', 'mid', $mid);
			$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrNewfamilyPageEdit', 'page_mid', $mid);
			$out[] = $o;
		}
		foreach ((array)($L['tour_page_mids'] ?? self::TOUR_PAGE_MIDS) as $mid => $label)
		{
			$o = new stdClass;
			$o->mid = $mid;
			$o->label = $label;
			$o->kind = (string)($kinds['tour'] ?? '???? (?? ?? 7?)');
			$o->view_url = getNotEncodedUrl('', 'mid', $mid);
			$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrTourPageEdit', 'page_mid', $mid);
			$out[] = $o;
		}
		$school_mids = (array)($L['school_page_mids'] ?? self::SCHOOL_PAGE_MIDS);
		$school_first = (string)array_key_first($school_mids);
		$o = new stdClass;
		$o->mid = $school_first;
		$o->label = (string)($L['domestic_mission']['list_label_school'] ?? '????');
		$o->kind = (string)($kinds['school'] ?? '????? (?? ???????? 4?)');
		$o->view_url = getNotEncodedUrl('', 'mid', $school_first);
		$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrSchoolPageEdit', 'page_mid', $school_first);
		$out[] = $o;
		$dkd_mid = self::DONGKEYDAY_PAGE_MID;
		$o = new stdClass;
		$o->mid = $dkd_mid;
		$o->label = self::getDongkeydayPageLabel($dkd_mid);
		$o->kind = (string)($kinds['dongkeyday'] ?? '????? (?? 9???? ??????)');
		$o->view_url = getNotEncodedUrl('', 'mid', $dkd_mid);
		$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDongkeydayPageEdit');
		$out[] = $o;
		$o = new stdClass;
		$o->mid = self::DOMESTIC_MISSION_LIST_MID;
		$o->label = (string)($L['domestic_mission']['page_title'] ?? '????');
		$o->kind = (string)($kinds['domestic'] ?? '????? (????? ?? + ?? sub)');
		$o->view_url = getNotEncodedUrl('', 'mid', self::DOMESTIC_MISSION_LIST_MID);
		$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDomesticMissionListEdit');
		$out[] = $o;
		$o = new stdClass;
		$o->mid = self::OVERSEAS_MISSION_LIST_MID;
		$o->label = (string)($L['overseas_mission']['page_title'] ?? '????');
		$o->kind = (string)($kinds['overseas'] ?? '????? (????????? + ?? sub)');
		$o->view_url = getNotEncodedUrl('', 'mid', self::OVERSEAS_MISSION_LIST_MID);
		$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrOverseasMissionListEdit');
		$out[] = $o;
		return $out;
	}

	public static function getGuidePageForEdit(string $mid): ?object
	{
		$mid = trim($mid);
		if (!self::isGuidePage($mid))
		{
			return null;
		}
		$data = self::getGuidePageData($mid);
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::GUIDE_PAGE_MIDS[$mid];
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->hero_photo = $data['hero_photo'];
		$o->catchphrase = $data['catchphrase'];
		$o->sections = $data['sections'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	public static function updatePageModuleContent(int $module_srl, string $html): BaseObject
	{
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		if (!$module_info)
		{
			return new BaseObject(-1, '??? ??? ?? ? ????.');
		}

		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->module = $module_info->module;
		$args->mid = $module_info->mid;
		$args->browser_title = $module_info->browser_title;
		$args->module_category_srl = $module_info->module_category_srl ?? 0;
		$args->domain_srl = $module_info->domain_srl ?? -1;
		$args->layout_srl = $module_info->layout_srl ?? -1;
		$args->mlayout_srl = $module_info->mlayout_srl ?? -1;
		$args->skin = $module_info->skin ?? '/USE_DEFAULT/';
		$args->is_skin_fix = $module_info->is_skin_fix ?? 'N';
		$args->mskin = $module_info->mskin ?? '/USE_DEFAULT/';
		$args->is_mskin_fix = $module_info->is_mskin_fix ?? 'N';
		$args->menu_srl = $module_info->menu_srl ?? 0;
		$args->description = $module_info->description ?? '';
		$args->is_default = $module_info->is_default ?? 'N';
		$args->open_rss = $module_info->open_rss ?? 'Y';
		$args->use_mobile = $module_info->use_mobile ?? 'N';
		$args->content = $html;
		$args->mcontent = '';

		$output = executeQuery('module.updateModule', $args);
		if (!$output->toBool())
		{
			return $output;
		}
		return new BaseObject();
	}

	public static function clearPageModuleCache(int $module_srl, string $mid): void
	{
		// ?? ??(content)? site_and_module:mid_info ??? ??? ? Rhymix ?? ???? ???
		Rhymix\Framework\Cache::delete('site_and_module:mid_info:' . $module_srl);
		Rhymix\Framework\Cache::clearGroup('site_and_module');

		// page ??(WIDGET/OUTSIDE)? ?? ??? ?? ??? ????? ?? ??
		foreach (['page', 'opage'] as $dir)
		{
			$base = \RX_BASEDIR . 'files/cache/' . $dir . '/';
			foreach ((array)glob($base . $module_srl . '.*') as $file)
			{
				if (is_file($file))
				{
					@unlink($file);
				}
			}
		}

		// ???/?? ??? ??? ?? ??
		Rhymix\Framework\Cache::clearGroup('template');
		Rhymix\Framework\Cache::clearGroup('widget');
	}

	/* ===================== ?? ?? ??? ===================== */

	/** ??? ?? ?? (?????????? ??) */
	public const HISTORY_PAGE_MIDS = [
		'p9' => '?? ??',
	];

	public const HISTORY_PAGE_MAX_BLOCKS = 60;

	public static function isHistoryPage(string $mid): bool
	{
		return isset(self::HISTORY_PAGE_MIDS[$mid]);
	}

	public static function getHistoryPageUploadDir(string $mid): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return \RX_BASEDIR . 'files/church/history/' . $safe;
	}

	public static function getHistoryPagesFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/history_pages.json';
	}

	/** @return array<string,array> */
	public static function getAllHistoryPages(): array
	{
		$path = self::getHistoryPagesFilePath();
		if (!is_file($path))
		{
			return [];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function saveAllHistoryPages(array $all): bool
	{
		$path = self::getHistoryPagesFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		@chmod($path, 0644);
		return true;
	}

	/** @return array{page_title:string,blocks:array<int,array{era:string,photo:string,body:string}>} */
	public static function getHistoryPageData(string $mid): array
	{
		$all = self::getAllHistoryPages();
		$row = is_array($all[$mid] ?? null) ? $all[$mid] : [];
		$blocks = [];
		foreach ((array)($row['blocks'] ?? []) as $block)
		{
			if (!is_array($block))
			{
				continue;
			}
			$era = trim((string)($block['era'] ?? ''));
			$photo = self::normalizeGuidePhotoUrl((string)($block['photo'] ?? ''));
			$body = trim((string)($block['body'] ?? ''));
			if ($era === '' && $photo === '' && $body === '')
			{
				continue;
			}
			$blocks[] = ['era' => $era, 'photo' => $photo, 'body' => $body];
		}
		if (!$blocks)
		{
			$blocks[] = ['era' => '', 'photo' => '', 'body' => ''];
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::HISTORY_PAGE_MIDS[$mid] ?? '')),
			'blocks' => $blocks,
		];
	}

	public static function saveHistoryPageData(string $mid, array $data): BaseObject
	{
		if (!self::isHistoryPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$all = self::getAllHistoryPages();
		$blocks = [];
		foreach ((array)($data['blocks'] ?? []) as $block)
		{
			if (!is_array($block))
			{
				continue;
			}
			$era = trim((string)($block['era'] ?? ''));
			$photo = self::normalizeGuidePhotoUrl((string)($block['photo'] ?? ''));
			$body = trim((string)($block['body'] ?? ''));
			if ($era === '' && $photo === '' && $body === '')
			{
				continue;
			}
			$blocks[] = ['era' => $era, 'photo' => $photo, 'body' => $body];
		}
		$all[$mid] = [
			'page_title' => trim((string)($data['page_title'] ?? self::HISTORY_PAGE_MIDS[$mid] ?? '')),
			'blocks' => $blocks,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveAllHistoryPages($all))
		{
			return new BaseObject(-1, '?? ??? ??? ??? ??????.');
		}
		return new BaseObject();
	}

	public static function renderHistoryPage(array $data): string
	{
		$html = '<div class="church-history">';
		foreach ((array)($data['blocks'] ?? []) as $block)
		{
			if (!is_array($block))
			{
				continue;
			}
			$era = trim((string)($block['era'] ?? ''));
			$photo = trim((string)($block['photo'] ?? ''));
			$body = trim((string)($block['body'] ?? ''));
			if ($era === '' && $photo === '' && $body === '')
			{
				continue;
			}

			$html .= '<article class="church-history-row">';
			$html .= '<div class="church-history-side">';
			if ($era !== '')
			{
				$html .= '<div class="church-history-era">' . nl2br(htmlspecialchars($era, ENT_QUOTES, 'UTF-8')) . '</div>';
			}
			if ($photo !== '')
			{
				$safe = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
				$html .= '<figure class="church-history-photo"><img src="' . $safe . '" alt="" loading="lazy" /></figure>';
			}
			$html .= '</div>';

			$html .= '<div class="church-history-content">';
			if ($body !== '')
			{
				$lines = preg_split('/\r\n|\r|\n/', $body) ?: [$body];
				foreach ($lines as $line)
				{
					$line = trim($line);
					if ($line === '')
					{
						$html .= '<div class="church-history-gap"></div>';
						continue;
					}
					// ?? ??(?.?.? / ?.? ? ???? ??)? ??? ???? ???
					// ?? ??? ??? ?? ?? ??? ?? ???.
					$date = '';
					$text = $line;
					if (preg_match('/^(\d{1,4}(?:\.\d{1,2}){1,2}\.?)\s+(.+)$/u', $line, $m))
					{
						$date = $m[1];
						$text = $m[2];
					}
					$html .= '<p class="church-history-item">'
						. '<span class="church-history-date">' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '</span>'
						. '<span class="church-history-text">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>'
						. '</p>';
				}
			}
			$html .= '</div>';
			$html .= '</article>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function publishHistoryPage(string $mid, array $data): BaseObject
	{
		if (!self::isHistoryPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ?? ? ????.');
		}

		$output = self::saveHistoryPageData($mid, $data);
		if (!$output->toBool())
		{
			return $output;
		}

		$html = self::renderHistoryPage($data);
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}

		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	public static function getHistoryPageForEdit(string $mid): ?object
	{
		$mid = trim($mid);
		if (!self::isHistoryPage($mid))
		{
			return null;
		}
		$data = self::getHistoryPageData($mid);
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::HISTORY_PAGE_MIDS[$mid];
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->blocks = $data['blocks'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	/* ===================== ??? ? ??? ===================== */

	public const PEOPLE_PAGE_MIDS = [
		'p79' => '??? ?',
	];

	public const PEOPLE_PAGE_MAX = 300;

	public const PEOPLE_CATEGORIES = ['???', '????', '????'];

	/**
	 * ?? ??(p79) ???? ???? ?? ???? ????.
	 * - ??? ?(p79) = ??? ???? ??(??? ??)
	 * - ???(p154) = ???
	 * - ??(p155) = ???? + ????
	 */
	public const PEOPLE_DISPLAY_VIEWS = [
		'p79' => ['???'],
		'p154' => ['???'],
		'p155' => ['????', '????'],
	];

	public static function isPeoplePage(string $mid): bool
	{
		return isset(self::PEOPLE_PAGE_MIDS[$mid]);
	}

	public static function getPeoplePageUploadDir(string $mid): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return \RX_BASEDIR . 'files/church/people/' . $safe;
	}

	public static function getPeoplePagesFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/people_pages.json';
	}

	/** @return array<string,array> */
	public static function getAllPeoplePages(): array
	{
		$path = self::getPeoplePagesFilePath();
		if (!is_file($path))
		{
			return [];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function saveAllPeoplePages(array $all): bool
	{
		$path = self::getPeoplePagesFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		@chmod($path, 0644);
		return true;
	}

	public static function normalizePersonCategory(string $cat): string
	{
		$cat = trim($cat);
		return in_array($cat, self::PEOPLE_CATEGORIES, true) ? $cat : self::PEOPLE_CATEGORIES[0];
	}

	/** @return array{page_title:string,people:array<int,array{category:string,name:string,title:string,photo:string,memo:string}>} */
	public static function getPeoplePageData(string $mid): array
	{
		$all = self::getAllPeoplePages();
		$row = is_array($all[$mid] ?? null) ? $all[$mid] : [];
		$people = [];
		foreach ((array)($row['people'] ?? []) as $p)
		{
			if (!is_array($p))
			{
				continue;
			}
			$name = trim((string)($p['name'] ?? ''));
			$title = trim((string)($p['title'] ?? ''));
			$photo = self::normalizeGuidePhotoUrl((string)($p['photo'] ?? ''));
			$memo = trim((string)($p['memo'] ?? ''));
			if ($name === '' && $title === '' && $photo === '' && $memo === '')
			{
				continue;
			}
			$people[] = [
				'category' => self::normalizePersonCategory((string)($p['category'] ?? '')),
				'name' => $name,
				'title' => $title,
				'photo' => $photo,
				'memo' => $memo,
				'order' => (int)($p['order'] ?? 0),
			];
		}
		if (!$people)
		{
			$people[] = ['category' => self::PEOPLE_CATEGORIES[0], 'name' => '', 'title' => '', 'photo' => '', 'memo' => '', 'order' => 0];
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::PEOPLE_PAGE_MIDS[$mid] ?? '')),
			'people' => $people,
		];
	}

	public static function savePeoplePageData(string $mid, array $data): BaseObject
	{
		if (!self::isPeoplePage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$all = self::getAllPeoplePages();
		$people = [];
		foreach ((array)($data['people'] ?? []) as $p)
		{
			if (!is_array($p))
			{
				continue;
			}
			$name = trim((string)($p['name'] ?? ''));
			$title = trim((string)($p['title'] ?? ''));
			$photo = self::normalizeGuidePhotoUrl((string)($p['photo'] ?? ''));
			$memo = trim((string)($p['memo'] ?? ''));
			if ($name === '' && $title === '' && $photo === '' && $memo === '')
			{
				continue;
			}
			$people[] = [
				'category' => self::normalizePersonCategory((string)($p['category'] ?? '')),
				'name' => $name,
				'title' => $title,
				'photo' => $photo,
				'memo' => $memo,
				'order' => (int)($p['order'] ?? 0),
			];
		}
		$all[$mid] = [
			'page_title' => trim((string)($data['page_title'] ?? self::PEOPLE_PAGE_MIDS[$mid] ?? '')),
			'people' => $people,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveAllPeoplePages($all))
		{
			return new BaseObject(-1, '??? ? ??? ??? ??????.');
		}
		return new BaseObject();
	}

	public static function renderPeoplePage(array $data): string
	{
		$people = (array)($data['people'] ?? []);
		$groups = [];
		foreach (self::PEOPLE_CATEGORIES as $cat)
		{
			$groups[$cat] = [];
		}
		foreach ($people as $p)
		{
			if (!is_array($p))
			{
				continue;
			}
			$cat = self::normalizePersonCategory((string)($p['category'] ?? ''));
			$groups[$cat][] = $p;
		}

		$html = '<div class="church-people">';
		foreach (self::PEOPLE_CATEGORIES as $cat)
		{
			$list = $groups[$cat];
			if (!$list)
			{
				continue;
			}
			// ??(order) ???? ??. 0(???)? ? ??, ??? ?? ?? ??.
			$decorated = [];
			foreach ($list as $pos => $p)
			{
				$ord = (int)($p['order'] ?? 0);
				$decorated[] = ['p' => $p, 'ord' => $ord > 0 ? $ord : PHP_INT_MAX, 'pos' => $pos];
			}
			usort($decorated, function ($a, $b) {
				if ($a['ord'] !== $b['ord'])
				{
					return $a['ord'] <=> $b['ord'];
				}
				return $a['pos'] <=> $b['pos'];
			});
			$list = array_column($decorated, 'p');
			$html .= '<section class="church-people-group">';
			$html .= '<h2 class="church-people-heading">' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . '</h2>';
			$html .= '<div class="church-staff-grid">';
			foreach ($list as $p)
			{
				$name = trim((string)($p['name'] ?? ''));
				$title = trim((string)($p['title'] ?? ''));
				$photo = trim((string)($p['photo'] ?? ''));
				$memo = trim((string)($p['memo'] ?? ''));

				$html .= '<article class="church-staff-card">';
				if ($photo !== '')
				{
					$safe = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
					$alt = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
					$html .= '<figure><img src="' . $safe . '" alt="' . $alt . '" loading="lazy" /></figure>';
				}
				else
				{
					$html .= '<figure class="church-staff-noimg"></figure>';
				}
				if ($name !== '' || $title !== '')
				{
					$html .= '<h3>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
					if ($title !== '')
					{
						$html .= ' <span>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</span>';
					}
					$html .= '</h3>';
				}
				if ($memo !== '')
				{
					$lines = preg_split('/\r\n|\r|\n/', $memo) ?: [$memo];
					foreach ($lines as $line)
					{
						$line = trim($line);
						if ($line === '')
						{
							continue;
						}
						$html .= '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
					}
				}
				$html .= '</article>';
			}
			$html .= '</div>';
			$html .= '</section>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function publishPeoplePage(string $mid, array $data): BaseObject
	{
		if (!self::isPeoplePage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ?? ? ????.');
		}

		$output = self::savePeoplePageData($mid, $data);
		if (!$output->toBool())
		{
			return $output;
		}

		// ?? ???(??? ?/???/??)? ???? ?? ??
		self::publishPeopleDisplayPages($data);

		return new BaseObject();
	}

	/** p79 ???? ???? ?? ? ?? ???(p79/p154/p155)? ?? */
	public static function publishPeopleDisplayPages(array $data): void
	{
		$people = (array)($data['people'] ?? []);
		foreach (self::PEOPLE_DISPLAY_VIEWS as $view_mid => $cats)
		{
			$view_srl = self::getPageModuleSrl($view_mid);
			if ($view_srl < 1)
			{
				continue;
			}
			$subset = [];
			foreach ($people as $p)
			{
				if (is_array($p) && in_array(self::normalizePersonCategory((string)($p['category'] ?? '')), $cats, true))
				{
					$subset[] = $p;
				}
			}
			$html = self::renderPeoplePage(['people' => $subset]);
			self::updatePageModuleContent($view_srl, $html);
			self::clearPageModuleCache($view_srl, $view_mid);
		}
	}

	public static function getPeoplePageForEdit(string $mid): ?object
	{
		$mid = trim($mid);
		if (!self::isPeoplePage($mid))
		{
			return null;
		}
		$data = self::getPeoplePageData($mid);
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::PEOPLE_PAGE_MIDS[$mid];
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->people = $data['people'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	/* ===================== ???? ??? ===================== */

	public const WORSHIP_PAGE_MIDS = [
		'p78' => '????',
	];

	public const WORSHIP_PAGE_MAX = 200;

	public const WORSHIP_CATEGORIES = ['??', '???', '????', '??'];

	public static function isWorshipPage(string $mid): bool
	{
		return isset(self::WORSHIP_PAGE_MIDS[$mid]);
	}

	public static function getWorshipPagesFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/worship_pages.json';
	}

	public static function getAllWorshipPages(): array
	{
		$path = self::getWorshipPagesFilePath();
		if (!is_file($path))
		{
			return [];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function saveAllWorshipPages(array $all): bool
	{
		$path = self::getWorshipPagesFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		@chmod($path, 0644);
		return true;
	}

	public static function normalizeWorshipCategory(string $cat): string
	{
		$cat = trim($cat);
		return in_array($cat, self::WORSHIP_CATEGORIES, true) ? $cat : self::WORSHIP_CATEGORIES[0];
	}

	/** @return array{page_title:string,items:array<int,array{category:string,name:string,time:string,place:string}>} */
	public static function getWorshipPageData(string $mid): array
	{
		$all = self::getAllWorshipPages();
		$row = is_array($all[$mid] ?? null) ? $all[$mid] : [];
		$items = [];
		foreach ((array)($row['items'] ?? []) as $it)
		{
			if (!is_array($it))
			{
				continue;
			}
			$name = trim((string)($it['name'] ?? ''));
			$time = trim((string)($it['time'] ?? ''));
			$place = trim((string)($it['place'] ?? ''));
			if ($name === '' && $time === '' && $place === '')
			{
				continue;
			}
			$items[] = [
				'category' => self::normalizeWorshipCategory((string)($it['category'] ?? '')),
				'name' => $name,
				'time' => $time,
				'place' => $place,
			];
		}
		if (!$items)
		{
			$items[] = ['category' => self::WORSHIP_CATEGORIES[0], 'name' => '', 'time' => '', 'place' => ''];
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::WORSHIP_PAGE_MIDS[$mid] ?? '')),
			'items' => $items,
		];
	}

	public static function saveWorshipPageData(string $mid, array $data): BaseObject
	{
		if (!self::isWorshipPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$all = self::getAllWorshipPages();
		$items = [];
		foreach ((array)($data['items'] ?? []) as $it)
		{
			if (!is_array($it))
			{
				continue;
			}
			$name = trim((string)($it['name'] ?? ''));
			$time = trim((string)($it['time'] ?? ''));
			$place = trim((string)($it['place'] ?? ''));
			if ($name === '' && $time === '' && $place === '')
			{
				continue;
			}
			$items[] = [
				'category' => self::normalizeWorshipCategory((string)($it['category'] ?? '')),
				'name' => $name,
				'time' => $time,
				'place' => $place,
			];
		}
		$all[$mid] = [
			'page_title' => trim((string)($data['page_title'] ?? self::WORSHIP_PAGE_MIDS[$mid] ?? '')),
			'items' => $items,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveAllWorshipPages($all))
		{
			return new BaseObject(-1, '???? ??? ??? ??????.');
		}
		return new BaseObject();
	}

	public static function renderWorshipPage(array $data): string
	{
		$items = (array)($data['items'] ?? []);
		$groups = [];
		foreach (self::WORSHIP_CATEGORIES as $cat)
		{
			$groups[$cat] = [];
		}
		foreach ($items as $it)
		{
			if (!is_array($it))
			{
				continue;
			}
			$cat = self::normalizeWorshipCategory((string)($it['category'] ?? ''));
			$groups[$cat][] = $it;
		}

		$html = '<div class="church-worship">';
		foreach (self::WORSHIP_CATEGORIES as $cat)
		{
			$list = array_values($groups[$cat]);
			$count = count($list);
			if ($count === 0)
			{
				continue;
			}
			$html .= '<section class="church-worship-group">';
			$html .= '<h2 class="church-worship-heading">' . htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') . '</h2>';
			$html .= '<table class="church-worship-table"><thead><tr>';
			$html .= '<th class="cw-name">???</th><th class="cw-time">????</th><th class="cw-place">??</th>';
			$html .= '</tr></thead><tbody>';

			$i = 0;
			while ($i < $count)
			{
				$place = (string)($list[$i]['place'] ?? '');
				// ?? ??? ???? ??? ? ?? ??
				$span = 1;
				while ($i + $span < $count && (string)($list[$i + $span]['place'] ?? '') === $place)
				{
					$span++;
				}
				for ($j = $i; $j < $i + $span; $j++)
				{
					$name = htmlspecialchars((string)($list[$j]['name'] ?? ''), ENT_QUOTES, 'UTF-8');
					$time = htmlspecialchars((string)($list[$j]['time'] ?? ''), ENT_QUOTES, 'UTF-8');
					$html .= '<tr><td class="cw-name">' . $name . '</td><td class="cw-time">' . $time . '</td>';
					if ($j === $i)
					{
						$rowspan = $span > 1 ? ' rowspan="' . $span . '"' : '';
						$html .= '<td class="cw-place"' . $rowspan . '>' . htmlspecialchars($place, ENT_QUOTES, 'UTF-8') . '</td>';
					}
					$html .= '</tr>';
				}
				$i += $span;
			}

			$html .= '</tbody></table>';
			$html .= '</section>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function publishWorshipPage(string $mid, array $data): BaseObject
	{
		if (!self::isWorshipPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ?? ? ????.');
		}

		$output = self::saveWorshipPageData($mid, $data);
		if (!$output->toBool())
		{
			return $output;
		}

		$html = self::renderWorshipPage($data);
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}

		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	public static function getWorshipPageForEdit(string $mid): ?object
	{
		$mid = trim($mid);
		if (!self::isWorshipPage($mid))
		{
			return null;
		}
		$data = self::getWorshipPageData($mid);
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::WORSHIP_PAGE_MIDS[$mid];
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->items = $data['items'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	/* ===================== ??? ?? ??? ===================== */

	public const NEWFAMILY_PAGE_MIDS = [
		'p108' => '??? ??',
	];

	public const NEWFAMILY_PHOTO_COUNT = 2;

	public static function isNewfamilyPage(string $mid): bool
	{
		return isset(self::NEWFAMILY_PAGE_MIDS[$mid]);
	}

	public static function getNewfamilyPageUploadDir(string $mid): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return \RX_BASEDIR . 'files/church/newfamily/' . $safe;
	}

	/** ???? ??? ? ?? ?? ??(3?) ??? ?? */
	public static function getNewfamilyFixedImageUrl(string $mid): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return './files/church/newfamily/' . $safe . '/fixed_section3.jpg';
	}

	public static function getNewfamilyPagesFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/newfamily_pages.json';
	}

	/** @return array<string,array> */
	public static function getAllNewfamilyPages(): array
	{
		$path = self::getNewfamilyPagesFilePath();
		if (!is_file($path))
		{
			return [];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function saveAllNewfamilyPages(array $all): bool
	{
		$path = self::getNewfamilyPagesFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		@chmod($path, 0644);
		return true;
	}

	/** @return array{page_title:string,photos:array<int,string>} */
	public static function getNewfamilyPageData(string $mid): array
	{
		$all = self::getAllNewfamilyPages();
		$row = is_array($all[$mid] ?? null) ? $all[$mid] : [];
		$photos = [];
		for ($i = 0; $i < self::NEWFAMILY_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($row['photos'][$i] ?? ''));
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::NEWFAMILY_PAGE_MIDS[$mid] ?? '')),
			'photos' => $photos,
		];
	}

	public static function saveNewfamilyPageData(string $mid, array $data): BaseObject
	{
		if (!self::isNewfamilyPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$all = self::getAllNewfamilyPages();
		$photos = [];
		for ($i = 0; $i < self::NEWFAMILY_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($data['photos'][$i] ?? ''));
		}
		$all[$mid] = [
			'page_title' => trim((string)($data['page_title'] ?? self::NEWFAMILY_PAGE_MIDS[$mid] ?? '')),
			'photos' => $photos,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveAllNewfamilyPages($all))
		{
			return new BaseObject(-1, '??? ?? ??? ??????.');
		}
		return new BaseObject();
	}

	public static function renderNewfamilyPage(string $mid, array $data): string
	{
		$photos = (array)($data['photos'] ?? []);
		$fixed = htmlspecialchars(self::getNewfamilyFixedImageUrl($mid), ENT_QUOTES, 'UTF-8');

		$html = '<div class="church-newfamily">';
		$html .= '<div class="church-nf-photos">';
		for ($i = 0; $i < self::NEWFAMILY_PHOTO_COUNT; $i++)
		{
			$photo = trim((string)($photos[$i] ?? ''));
			if ($photo === '')
			{
				continue;
			}
			$safe = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
			$html .= '<figure class="church-nf-photo"><img src="' . $safe . '" alt="??? ?? ?? ' . ($i + 1) . '" loading="lazy" /></figure>';
		}
		$html .= '</div>';
		$html .= '<div class="church-nf-fixed"><img src="' . $fixed . '" alt="??? ?? ?? ???" /></div>';
		$html .= '</div>';
		return $html;
	}

	public static function publishNewfamilyPage(string $mid, array $data): BaseObject
	{
		if (!self::isNewfamilyPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ?? ? ????.');
		}

		$output = self::saveNewfamilyPageData($mid, $data);
		if (!$output->toBool())
		{
			return $output;
		}

		$html = self::renderNewfamilyPage($mid, self::getNewfamilyPageData($mid));
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}

		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	public static function getNewfamilyPageForEdit(string $mid): ?object
	{
		$mid = trim($mid);
		if (!self::isNewfamilyPage($mid))
		{
			return null;
		}
		$data = self::getNewfamilyPageData($mid);
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::NEWFAMILY_PAGE_MIDS[$mid];
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->photos = $data['photos'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	/* ===================== ??????(???) ??? ===================== */

	public const TOUR_PAGE_MIDS = [
		'p147' => '??????',
		'p92' => '??? ????',
		'p146' => '????',
	];

	public const TOUR_PAGE_MAX = 7;

	public static function isTourPage(string $mid): bool
	{
		return isset(self::TOUR_PAGE_MIDS[$mid]);
	}

	public static function getTourPageUploadDir(string $mid): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return \RX_BASEDIR . 'files/church/tour/' . $safe;
	}

	public static function getTourPagesFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/tour_pages.json';
	}

	/** @return array<string,array> */
	public static function getAllTourPages(): array
	{
		$path = self::getTourPagesFilePath();
		if (!is_file($path))
		{
			return [];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function saveAllTourPages(array $all): bool
	{
		$path = self::getTourPagesFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		self::fixDomesticMissionFilePermissions($path);
		return true;
	}

	/** @return array{page_title:string,description:string,photos:array<int,string>} */
	public static function getTourPageData(string $mid): array
	{
		$all = self::getAllTourPages();
		$row = is_array($all[$mid] ?? null) ? $all[$mid] : [];
		$photos = [];
		foreach ((array)($row['photos'] ?? []) as $photo)
		{
			$url = self::normalizeGuidePhotoUrl((string)$photo);
			if ($url === '')
			{
				continue;
			}
			$photos[] = $url;
			if (count($photos) >= self::TOUR_PAGE_MAX)
			{
				break;
			}
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::TOUR_PAGE_MIDS[$mid] ?? '')),
			'description' => trim((string)($row['description'] ?? '')),
			'photos' => $photos,
		];
	}

	public static function saveTourPageData(string $mid, array $data): BaseObject
	{
		if (!self::isTourPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$all = self::getAllTourPages();
		$photos = [];
		foreach ((array)($data['photos'] ?? []) as $photo)
		{
			$url = self::normalizeGuidePhotoUrl((string)$photo);
			if ($url === '')
			{
				continue;
			}
			$photos[] = $url;
			if (count($photos) >= self::TOUR_PAGE_MAX)
			{
				break;
			}
		}
		$all[$mid] = [
			'page_title' => trim((string)($data['page_title'] ?? self::TOUR_PAGE_MIDS[$mid] ?? '')),
			'description' => trim((string)($data['description'] ?? '')),
			'photos' => $photos,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveAllTourPages($all))
		{
			return new BaseObject(-1, '?????? ??? ??????.');
		}
		return new BaseObject();
	}

	public static function getTourPageLabel(string $mid): string
	{
		$mid = trim($mid);
		$L = self::uiLabels()['tour_page_mids'] ?? [];
		if (is_array($L) && !empty($L[$mid]))
		{
			return (string)$L[$mid];
		}
		return (string)(self::TOUR_PAGE_MIDS[$mid] ?? $mid);
	}

	public static function getTourPageDescClass(string $mid): string
	{
		switch (trim($mid))
		{
			case 'p92':
				return 'church-tour-desc church-tour-desc--rice';
			case 'p146':
				return 'church-tour-desc church-tour-desc--scholarship';
			default:
				return 'church-tour-desc';
		}
	}

	public static function renderTourPage(string $mid, array $data): string
	{
		$page_title = trim((string)($data['page_title'] ?? '???'));
		$photos = [];
		foreach ((array)($data['photos'] ?? []) as $photo)
		{
			$photo = trim((string)$photo);
			if ($photo === '' || count($photos) >= self::TOUR_PAGE_MAX)
			{
				continue;
			}
			$photos[] = $photo;
		}

		$count = count($photos);
		$html = '';
		if ($count > 0)
		{
			$html .= '<div class="church-tour church-carousel" data-interval="4000">';
			$html .= '<div class="church-carousel-viewport">';
			$html .= '<div class="church-carousel-track">';
			foreach ($photos as $i => $photo)
			{
				$safe = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
				$alt = htmlspecialchars($page_title . ' ?? ' . ($i + 1), ENT_QUOTES, 'UTF-8');
				$active = $i === 0 ? ' is-active' : '';
				$html .= '<figure class="church-carousel-slide' . $active . '"><img src="' . $safe . '" alt="' . $alt . '" loading="' . ($i === 0 ? 'eager' : 'lazy') . '" /></figure>';
			}
			$html .= '</div>';

			if ($count > 1)
			{
				$html .= '<button type="button" class="church-carousel-arrow church-carousel-prev" aria-label="?? ??"><span></span></button>';
				$html .= '<button type="button" class="church-carousel-arrow church-carousel-next" aria-label="?? ??"><span></span></button>';
			}
			$html .= '</div>';

			if ($count > 1)
			{
				$html .= '<div class="church-carousel-thumbs">';
				foreach ($photos as $i => $photo)
				{
					$safe = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
					$active = $i === 0 ? ' is-active' : '';
					$html .= '<button type="button" class="church-carousel-thumb' . $active . '" data-index="' . $i . '" aria-label="' . ($i + 1) . '? ??"><img src="' . $safe . '" alt="" loading="lazy" /></button>';
				}
				$html .= '</div>';
			}

			$html .= '</div>';
		}
		else
		{
			$html .= '<div class="church-tour"></div>';
		}

		$description = trim((string)($data['description'] ?? ''));
		if ($description !== '')
		{
			$desc_class = self::getTourPageDescClass($mid);
			$html .= '<div class="' . htmlspecialchars($desc_class, ENT_QUOTES, 'UTF-8') . '">';
			foreach (preg_split('/\r\n|\r|\n/', $description) as $line)
			{
				$line = trim($line);
				if ($line === '')
				{
					$html .= '<br />';
					continue;
				}
				$html .= '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
			}
			$html .= '</div>';
		}

		return $html;
	}

	public static function publishTourPage(string $mid, array $data): BaseObject
	{
		if (!self::isTourPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ?? ? ????.');
		}

		$output = self::saveTourPageData($mid, $data);
		if (!$output->toBool())
		{
			return $output;
		}

		$html = self::renderTourPage($mid, self::getTourPageData($mid));
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}

		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	public static function getTourPageForEdit(string $mid): ?object
	{
		$mid = trim($mid);
		if (!self::isTourPage($mid))
		{
			return null;
		}
		$data = self::getTourPageData($mid);
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::getTourPageLabel($mid);
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->description = $data['description'];
		$o->photos = $data['photos'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	/** ??? ?? ???? ??? ????? */
	public static function ensureMissionTourMenuItem(string $mid, int $listorder): BaseObject
	{
		$mid = trim($mid);
		$page_name = self::getTourPageLabel($mid);
		if ($page_name === '' || $page_name === $mid)
		{
			return new BaseObject(-1, '??? ???? ?? ? ????.');
		}

		$oDB = Rhymix\Framework\DB::getInstance();
		$L = self::uiLabels();
		$mission_name = (string)($L['sub_top_menus']['mission'] ?? '??? ??');

		$mission_grp = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND parent_srl = 0 AND name = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mission_name
		)->fetch(\PDO::FETCH_OBJ);
		if (!$mission_grp || empty($mission_grp->menu_item_srl))
		{
			return new BaseObject(-1, '??? ?? ?? ??? ?? ? ????.');
		}
		$mission_grp_srl = (int)$mission_grp->menu_item_srl;

		$row = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND url = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mid
		)->fetch(\PDO::FETCH_OBJ);

		if ($row && !empty($row->menu_item_srl))
		{
			$oDB->query(
				'UPDATE menu_item SET parent_srl = ?, name = ?, listorder = ? WHERE menu_item_srl = ?',
				$mission_grp_srl,
				$page_name,
				$listorder,
				(int)$row->menu_item_srl
			);
		}
		else
		{
			$srl = getNextSequence();
			$oDB->query(
				'INSERT INTO menu_item (menu_item_srl, parent_srl, menu_srl, name, url, is_shortcut, open_window, expand, listorder, regdate) VALUES (?,?,?,?,?,?,?,?,?,?)',
				$srl,
				$mission_grp_srl,
				self::DOMESTIC_MISSION_MAIN_MENU_SRL,
				$page_name,
				$mid,
				'N',
				'N',
				'N',
				$listorder,
				date('YmdHis')
			);
		}

		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl > 0)
		{
			$oDB->query('UPDATE modules SET browser_title = ? WHERE module_srl = ?', $page_name, $module_srl);
		}

		Rhymix\Framework\Cache::clearGroup('menu');
		return new BaseObject();
	}

	/** @deprecated use ensureMissionTourMenuItem */
	public static function ensureMissionRiceShareMenu(): BaseObject
	{
		return self::ensureMissionTourMenuItem('p92', -99980);
	}

	/** ???? ?? ?? mid */
	public const SCHOOL_PAGE_MIDS = [
		'p109' => '???',
		'p112' => '???',
		'p115' => '????',
		'p118' => '???',
	];

	public const SCHOOL_PHOTO_COUNT = 4;

	public static function isSchoolPage(string $mid): bool
	{
		return isset(self::SCHOOL_PAGE_MIDS[$mid]);
	}

	public static function getSchoolPageUploadDir(string $mid): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return \RX_BASEDIR . 'files/church/school/' . $safe;
	}

	public static function getSchoolPagesFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/school_pages.json';
	}

	/** @return array<string,array> */
	public static function getAllSchoolPages(): array
	{
		$path = self::getSchoolPagesFilePath();
		if (!is_file($path))
		{
			return [];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		return is_array($decoded) ? $decoded : [];
	}

	public static function saveAllSchoolPages(array $all): bool
	{
		$path = self::getSchoolPagesFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		@chmod($path, 0644);
		return true;
	}

	/** @return array{page_title:string,theme:string,verse:string,goal:string,worship:string,staff:string,photos:array<int,string>} */
	public static function getSchoolPageData(string $mid): array
	{
		$all = self::getAllSchoolPages();
		$row = is_array($all[$mid] ?? null) ? $all[$mid] : [];
		$photos = [];
		for ($i = 0; $i < self::SCHOOL_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($row['photos'][$i] ?? ''));
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::SCHOOL_PAGE_MIDS[$mid] ?? '')),
			'theme' => trim((string)($row['theme'] ?? '')),
			'verse' => trim((string)($row['verse'] ?? '')),
			'goal' => trim((string)($row['goal'] ?? '')),
			'worship' => trim((string)($row['worship'] ?? '')),
			'staff' => trim((string)($row['staff'] ?? '')),
			'photos' => $photos,
		];
	}

	public static function saveSchoolPageData(string $mid, array $data): BaseObject
	{
		if (!self::isSchoolPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$all = self::getAllSchoolPages();
		$photos = [];
		for ($i = 0; $i < self::SCHOOL_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($data['photos'][$i] ?? ''));
		}
		$all[$mid] = [
			'page_title' => trim((string)($data['page_title'] ?? self::SCHOOL_PAGE_MIDS[$mid] ?? '')),
			'theme' => trim((string)($data['theme'] ?? '')),
			'verse' => trim((string)($data['verse'] ?? '')),
			'goal' => trim((string)($data['goal'] ?? '')),
			'worship' => trim((string)($data['worship'] ?? '')),
			'staff' => trim((string)($data['staff'] ?? '')),
			'photos' => $photos,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveAllSchoolPages($all))
		{
			return new BaseObject(-1, '???? ??? ??? ??????.');
		}
		return new BaseObject();
	}

	public static function renderSchoolPage(string $mid, array $data): string
	{
		$safe_mid = preg_replace('/[^a-z0-9_]/i', '', $mid);
		$L = self::uiLabels();
		$school_mids = (array)($L['school_page_mids'] ?? self::SCHOOL_PAGE_MIDS);
		$dept = (string)($school_mids[$mid] ?? self::SCHOOL_PAGE_MIDS[$mid] ?? '');
		$dept_safe = htmlspecialchars($dept, ENT_QUOTES, 'UTF-8');

		$theme = trim((string)($data['theme'] ?? ''));
		$verse = trim((string)($data['verse'] ?? ''));

		$photos = [];
		foreach ((array)($data['photos'] ?? []) as $p)
		{
			$p = trim((string)$p);
			if ($p === '')
			{
				continue;
			}
			$photos[] = $p;
			if (count($photos) >= self::SCHOOL_PHOTO_COUNT)
			{
				break;
			}
		}

		$h = '<div class="church-school church-school--' . $safe_mid . '">';

		// ??? ?? ??? (CSS ??? church-school--{mid} ? ??)
		if ($photos)
		{
			$h .= '<div class="cs-photos">';
			$h .= '<span class="cs-deco cs-deco-1" aria-hidden="true"></span>';
			$h .= '<span class="cs-deco cs-deco-2" aria-hidden="true"></span>';
			$h .= '<span class="cs-deco cs-deco-3" aria-hidden="true"></span>';
			foreach ($photos as $i => $p)
			{
				$safe = htmlspecialchars($p, ENT_QUOTES, 'UTF-8');
				$h .= '<figure class="cs-photo cs-photo-' . ($i + 1) . '"><img src="' . $safe . '" alt="' . $dept_safe . ' ?? ' . ($i + 1) . '" loading="lazy" /></figure>';
			}
			$h .= '</div>';
		}

		$h .= '<div class="cs-info">';
		if ($theme !== '')
		{
			$h .= '<div class="cs-theme"><span class="cs-theme-label">????</span>'
				. '<strong class="cs-theme-text">' . nl2br(htmlspecialchars($theme, ENT_QUOTES, 'UTF-8')) . '</strong></div>';
		}
		if ($verse !== '')
		{
			$h .= '<blockquote class="cs-verse">' . nl2br(htmlspecialchars($verse, ENT_QUOTES, 'UTF-8')) . '</blockquote>';
		}

		$blocks = [
			['goal', '???? ? ??', trim((string)($data['goal'] ?? ''))],
			['worship', '?? ??', trim((string)($data['worship'] ?? ''))],
			['staff', '?? ??? ? ??', trim((string)($data['staff'] ?? ''))],
		];
		foreach ($blocks as $b)
		{
			if ($b[2] === '')
			{
				continue;
			}
			$h .= '<section class="cs-block cs-' . $b[0] . '">';
			$h .= '<h3 class="cs-block-title">' . htmlspecialchars($b[1], ENT_QUOTES, 'UTF-8') . '</h3>';
			$h .= '<div class="cs-block-body">' . nl2br(htmlspecialchars($b[2], ENT_QUOTES, 'UTF-8')) . '</div>';
			$h .= '</section>';
		}
		$h .= '</div>'; // cs-info
		$h .= '</div>'; // church-school
		return $h;
	}

	public static function publishSchoolPage(string $mid, array $data): BaseObject
	{
		if (!self::isSchoolPage($mid))
		{
			return new BaseObject(-1, '??? ? ?? ??????.');
		}
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ?? ? ????.');
		}

		$output = self::saveSchoolPageData($mid, $data);
		if (!$output->toBool())
		{
			return $output;
		}

		$html = self::renderSchoolPage($mid, self::getSchoolPageData($mid));
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}

		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	public static function getSchoolPageForEdit(string $mid): ?object
	{
		$mid = trim($mid);
		if (!self::isSchoolPage($mid))
		{
			return null;
		}
		$data = self::getSchoolPageData($mid);
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::SCHOOL_PAGE_MIDS[$mid];
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->theme = $data['theme'];
		$o->verse = $data['verse'];
		$o->goal = $data['goal'];
		$o->worship = $data['worship'];
		$o->staff = $data['staff'];
		$o->photos = $data['photos'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	/* ===================== ???? ??? ===================== */

	public const DONGKEYDAY_PAGE_MID = 'p93';
	public const DONGKEYDAY_PHOTO_COUNT = 9;

	public static function isDongkeydayPage(string $mid): bool
	{
		return trim($mid) === self::DONGKEYDAY_PAGE_MID;
	}

	public static function getDongkeydayPageLabel(string $mid = ''): string
	{
		return self::getDongkeydayUiStrings()['label'];
	}

	/** @return array{label:string,apply_label:string,apply_note:string,photo_alt_suffix:string,star_primary:string,star_secondary:string} */
	public static function getDongkeydayUiStrings(): array
	{
		$L = self::uiLabels()['dongkeyday_page'] ?? [];
		if (!is_array($L))
		{
			$L = [];
		}
		return [
			'label' => (string)($L['label'] ?? "\u{B3D9}\u{D0A4}\u{B370}\u{C774}"),
			'apply_label' => (string)($L['apply_label'] ?? "\u{B3D9}\u{D0A4}\u{B370}\u{C774} \u{CC38}\u{AC00} \u{C2E0}\u{CCAD}"),
			'apply_note' => (string)($L['apply_note'] ?? "\u{AD6C}\u{AE00} \u{D3FC}\u{C73C}\u{B85C} \u{C774}\u{B3D9}\u{D569}\u{B2C8}\u{B2E4}."),
			'photo_alt_suffix' => (string)($L['photo_alt_suffix'] ?? ' ' . "\u{C0AC}\u{C9C4}" . ' '),
			'star_primary' => (string)($L['star_primary'] ?? "\u{2726}"),
			'star_secondary' => (string)($L['star_secondary'] ?? "\u{274B}"),
		];
	}

	public static function getDongkeydayPageUploadDir(): string
	{
		return \RX_BASEDIR . 'files/church/dongkeyday';
	}

	public static function getDongkeydayPageFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/dongkeyday_page.json';
	}

	/** @return array{page_title:string,intro:string,google_form_url:string,photos:array<int,string>} */
	public static function getDongkeydayPageData(): array
	{
		$path = self::getDongkeydayPageFilePath();
		$row = [];
		if (is_file($path))
		{
			$decoded = json_decode(file_get_contents($path) ?: '', true);
			if (is_array($decoded))
			{
				$row = $decoded;
			}
		}
		$photos = [];
		for ($i = 0; $i < self::DONGKEYDAY_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($row['photos'][$i] ?? ''));
		}
		return [
			'page_title' => trim((string)($row['page_title'] ?? self::getDongkeydayPageLabel())),
			'intro' => trim((string)($row['intro'] ?? '')),
			'google_form_url' => trim((string)($row['google_form_url'] ?? '')),
			'photos' => $photos,
		];
	}

	public static function saveDongkeydayPageData(array $data): BaseObject
	{
		$photos = [];
		for ($i = 0; $i < self::DONGKEYDAY_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($data['photos'][$i] ?? ''));
		}
		$payload = [
			'page_title' => trim((string)($data['page_title'] ?? self::getDongkeydayPageLabel())),
			'intro' => trim((string)($data['intro'] ?? '')),
			'google_form_url' => trim((string)($data['google_form_url'] ?? '')),
			'photos' => $photos,
			'updated' => date('Y-m-d H:i:s'),
		];
		$path = self::getDongkeydayPageFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return new BaseObject(-1, '???? ??? ??? ??????.');
		}
		self::fixDomesticMissionFilePermissions($path);
		return new BaseObject();
	}

	public static function renderDongkeydayPage(array $data): string
	{
		$ui = self::getDongkeydayUiStrings();
		$label = $ui['label'];
		$intro = trim((string)($data['intro'] ?? ''));
		$form_url = trim((string)($data['google_form_url'] ?? ''));

		$html = '<div class="church-dongkeyday">';
		$html .= '<div class="dkd-collage" role="presentation">';
		$html .= '<div class="dkd-orbit-ring" aria-hidden="true"></div>';
		$html .= '<div class="dkd-orbit-glow" aria-hidden="true"></div>';

		if ($intro !== '')
		{
			$html .= '<div class="dkd-intro">';
			$html .= '<span class="dkd-intro-star dkd-intro-star--tl" aria-hidden="true">' . htmlspecialchars($ui['star_primary'], ENT_QUOTES, 'UTF-8') . '</span>';
			$html .= '<span class="dkd-intro-star dkd-intro-star--tr" aria-hidden="true">' . htmlspecialchars($ui['star_primary'], ENT_QUOTES, 'UTF-8') . '</span>';
			$html .= '<span class="dkd-intro-star dkd-intro-star--bl" aria-hidden="true">' . htmlspecialchars($ui['star_secondary'], ENT_QUOTES, 'UTF-8') . '</span>';
			$html .= '<span class="dkd-intro-star dkd-intro-star--br" aria-hidden="true">' . htmlspecialchars($ui['star_secondary'], ENT_QUOTES, 'UTF-8') . '</span>';
			$html .= '<p class="dkd-intro-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</p>';
			$html .= '<div class="dkd-intro-inner">';
			$html .= nl2br(htmlspecialchars($intro, ENT_QUOTES, 'UTF-8'));
			$html .= '</div>';
			$html .= '<span class="dkd-intro-line" aria-hidden="true"></span>';
			$html .= '</div>';
		}

		for ($i = 0; $i < self::DONGKEYDAY_PHOTO_COUNT; $i++)
		{
			$photo = trim((string)($data['photos'][$i] ?? ''));
			if ($photo === '')
			{
				continue;
			}
			$n = $i + 1;
			$safe = htmlspecialchars($photo, ENT_QUOTES, 'UTF-8');
			$alt = htmlspecialchars($label . $ui['photo_alt_suffix'] . $n, ENT_QUOTES, 'UTF-8');
			$html .= '<figure class="dkd-photo dkd-photo-' . $n . '"><span class="dkd-photo-shine" aria-hidden="true"></span><img src="' . $safe . '" alt="' . $alt . '" loading="lazy" /></figure>';
		}

		$html .= '</div>';

		if ($form_url !== '')
		{
			$safe_url = htmlspecialchars($form_url, ENT_QUOTES, 'UTF-8');
			$html .= '<div class="dkd-apply">';
			$html .= '<a class="dkd-apply-btn" href="' . $safe_url . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($ui['apply_label'], ENT_QUOTES, 'UTF-8') . '</a>';
			$html .= '<p class="dkd-apply-note">' . htmlspecialchars($ui['apply_note'], ENT_QUOTES, 'UTF-8') . '</p>';
			$html .= '</div>';
		}

		$html .= '</div>';
		return $html;
	}

	public static function publishDongkeydayPage(array $data): BaseObject
	{
		$mid = self::DONGKEYDAY_PAGE_MID;
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '???? ??? ??(p93)? ?? ? ????.');
		}

		$output = self::saveDongkeydayPageData($data);
		if (!$output->toBool())
		{
			return $output;
		}

		$html = self::renderDongkeydayPage(self::getDongkeydayPageData());
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}

		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	public static function getDongkeydayPageForEdit(): ?object
	{
		$mid = self::DONGKEYDAY_PAGE_MID;
		if (self::getPageModuleSrl($mid) < 1)
		{
			return null;
		}
		$data = self::getDongkeydayPageData();
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::getDongkeydayPageLabel($mid);
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->intro = $data['intro'];
		$o->google_form_url = $data['google_form_url'];
		$o->photos = $data['photos'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		return $o;
	}

	public static function ensureDongkeydayMenuItem(int $listorder = -99960): BaseObject
	{
		$mid = self::DONGKEYDAY_PAGE_MID;
		$page_name = self::getDongkeydayPageLabel($mid);
		if ($page_name === '')
		{
			return new BaseObject(-1, '???? ?? ??? ??? ? ????.');
		}

		$oDB = Rhymix\Framework\DB::getInstance();
		$L = self::uiLabels();
		$mission_name = (string)($L['sub_top_menus']['mission'] ?? '??? ??');

		$mission_grp = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND parent_srl = 0 AND name = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mission_name
		)->fetch(\PDO::FETCH_OBJ);
		if (!$mission_grp || empty($mission_grp->menu_item_srl))
		{
			return new BaseObject(-1, '??? ?? ?? ??? ?? ? ????.');
		}
		$mission_grp_srl = (int)$mission_grp->menu_item_srl;

		$row = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND url = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mid
		)->fetch(\PDO::FETCH_OBJ);

		if ($row && !empty($row->menu_item_srl))
		{
			$oDB->query(
				'UPDATE menu_item SET parent_srl = ?, name = ?, listorder = ? WHERE menu_item_srl = ?',
				$mission_grp_srl,
				$page_name,
				$listorder,
				(int)$row->menu_item_srl
			);
		}
		else
		{
			$srl = getNextSequence();
			$oDB->query(
				'INSERT INTO menu_item (menu_item_srl, parent_srl, menu_srl, name, url, is_shortcut, open_window, expand, listorder, regdate) VALUES (?,?,?,?,?,?,?,?,?,?)',
				$srl,
				$mission_grp_srl,
				self::DOMESTIC_MISSION_MAIN_MENU_SRL,
				$page_name,
				$mid,
				'N',
				'N',
				'N',
				$listorder,
				date('YmdHis')
			);
		}

		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl > 0)
		{
			$oDB->query('UPDATE modules SET browser_title = ? WHERE module_srl = ?', $page_name, $module_srl);
		}

		Rhymix\Framework\Cache::clearGroup('menu');
		return new BaseObject();
	}

	/* ===================== ???? ??? ===================== */

	public const DOMESTIC_MISSION_LIST_MID = 'p25';
	public const DOMESTIC_MISSION_MAIN_MENU_SRL = 48;
	public const DOMESTIC_MISSION_SUB_FRAME_START = 251;

	/** @var array<string,string> */
	public const DOMESTIC_MISSION_CATEGORIES = [
		'church' => '??? ?? ??',
		'org' => '??? ?? ??',
	];

	public const DOMESTIC_MISSION_MAX_ITEMS = 80;

	public const OVERSEAS_MISSION_LIST_MID = 'p26';
	public const OVERSEAS_MISSION_SUB_FRAME_START = 261;

	public const OVERSEAS_MISSION_CATEGORIES = [
		'dispatch' => '???? ?????',
		'support' => '????? ?? ???',
	];

	public static function isDomesticMissionListMid(string $mid): bool
	{
		return trim($mid) === self::DOMESTIC_MISSION_LIST_MID;
	}

	public static function isDomesticMissionSubMid(string $mid): bool
	{
		$mid = trim($mid);
		if ($mid === '' || !preg_match('/^p\d+$/', $mid))
		{
			return false;
		}
		$data = self::getDomesticMissionData();
		foreach ((array)($data['items'] ?? []) as $item)
		{
			if (!empty($item['has_sub']) && ($item['sub_mid'] ?? '') === $mid)
			{
				return true;
			}
		}
		return false;
	}

	public static function getDomesticMissionFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/domestic_mission.json';
	}

	public static function getDomesticMissionUploadDir(string $scope = 'p25'): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $scope);
		return \RX_BASEDIR . 'files/church/domestic/' . $safe;
	}

	/** @return array{page_title:string,next_sub_frame:int,items:array<int,array<string,mixed>>} */
	public static function getDomesticMissionData(): array
	{
		$path = self::getDomesticMissionFilePath();
		if (!is_file($path))
		{
			return [
				'page_title' => '????',
				'next_sub_frame' => self::DOMESTIC_MISSION_SUB_FRAME_START,
				'items' => [],
			];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		if (!is_array($decoded))
		{
			return [
				'page_title' => '????',
				'next_sub_frame' => self::DOMESTIC_MISSION_SUB_FRAME_START,
				'items' => [],
			];
		}
		$items = [];
		foreach ((array)($decoded['items'] ?? []) as $item)
		{
			if (!is_array($item))
			{
				continue;
			}
			$items[] = self::normalizeDomesticMissionItem($item);
		}
		return [
			'page_title' => trim((string)($decoded['page_title'] ?? '????')),
			'next_sub_frame' => max(self::DOMESTIC_MISSION_SUB_FRAME_START, (int)($decoded['next_sub_frame'] ?? self::DOMESTIC_MISSION_SUB_FRAME_START)),
			'items' => $items,
		];
	}

	public static function saveDomesticMissionData(array $data): bool
	{
		$path = self::getDomesticMissionFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		self::fixDomesticMissionFilePermissions($path);
		return true;
	}

	public static function fixDomesticMissionFilePermissions(?string $path = null): void
	{
		$path = $path ?: self::getDomesticMissionFilePath();
		if (!is_file($path))
		{
			return;
		}
		@chmod($path, 0664);
		if (function_exists('posix_getpwnam'))
		{
			$pw = posix_getpwnam('nobody');
			if ($pw)
			{
				@chown($path, (int)$pw['uid']);
				if (isset($pw['gid']))
				{
					@chgrp($path, (int)$pw['gid']);
				}
			}
		}
	}

	public static function normalizeDomesticCategory(string $cat): string
	{
		$cat = trim($cat);
		return array_key_exists($cat, self::getDomesticMissionCategories()) ? $cat : 'church';
	}

	/** @param array<string,mixed> $item */
	public static function normalizeDomesticMissionItem(array $item): array
	{
		return [
			'id' => trim((string)($item['id'] ?? '')),
			'category' => self::normalizeDomesticCategory((string)($item['category'] ?? '')),
			'name' => trim((string)($item['name'] ?? '')),
			'thumb' => self::normalizeGuidePhotoUrl((string)($item['thumb'] ?? '')),
			'has_sub' => !empty($item['has_sub']),
			'sub_mid' => trim((string)($item['sub_mid'] ?? '')),
			'sub_photo' => self::normalizeGuidePhotoUrl((string)($item['sub_photo'] ?? '')),
			'sub_body' => trim((string)($item['sub_body'] ?? '')),
			'order' => (int)($item['order'] ?? 0),
		];
	}

	public static function getDomesticMissionSubTitle(string $mid): ?string
	{
		$mid = trim($mid);
		foreach (self::getDomesticMissionData()['items'] as $item)
		{
			if (!empty($item['has_sub']) && ($item['sub_mid'] ?? '') === $mid)
			{
				$name = trim((string)($item['name'] ?? ''));
				return $name !== '' ? $name : null;
			}
		}
		return null;
	}

	/** @return array<string,mixed>|null */
	public static function getDomesticMissionItemBySubMid(string $sub_mid): ?array
	{
		$sub_mid = trim($sub_mid);
		foreach (self::getDomesticMissionData()['items'] as $item)
		{
			if (!empty($item['has_sub']) && ($item['sub_mid'] ?? '') === $sub_mid)
			{
				return $item;
			}
		}
		return null;
	}

	public static function allocDomesticMissionSubMid(array &$data): string
	{
		$frame = max(self::DOMESTIC_MISSION_SUB_FRAME_START, (int)($data['next_sub_frame'] ?? self::DOMESTIC_MISSION_SUB_FRAME_START));
		$used = [];
		foreach ((array)($data['items'] ?? []) as $item)
		{
			if (!empty($item['sub_mid']))
			{
				$used[$item['sub_mid']] = true;
			}
		}
		while (isset($used['p' . $frame]) || self::getPageModuleSrl('p' . $frame) > 0)
		{
			$frame++;
		}
		$data['next_sub_frame'] = $frame + 1;
		return 'p' . $frame;
	}

	public static function ensureDomesticMissionSubPage(string $mid, string $title): BaseObject
	{
		$mid = trim($mid);
		$title = trim($title);
		if ($mid === '' || $title === '')
		{
			return new BaseObject(-1, '?? ??? ??? ???? ????.');
		}
		$srl = self::getPageModuleSrl($mid);
		if ($srl > 0)
		{
			$oDB = Rhymix\Framework\DB::getInstance();
			$oDB->query('UPDATE modules SET browser_title = ? WHERE module_srl = ?', $title, $srl);
			return new BaseObject();
		}

		$module_srl = getNextSequence();
		$stub = '<div class="church-page-stub" style="padding:24px"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p>??? ?? ????.</p></div>';
		$regdate = date('YmdHis');
		$oDB = Rhymix\Framework\DB::getInstance();
		$oDB->query(
			'INSERT INTO modules (module_srl, module, module_category_srl, menu_srl, site_srl, domain_srl, mid, layout_srl, mlayout_srl, use_mobile, skin, is_skin_fix, mskin, is_mskin_fix, browser_title, description, content, mcontent, is_default, open_rss, regdate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
			$module_srl,
			'page',
			0,
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			0,
			-1,
			$mid,
			-1,
			-1,
			'N',
			'/USE_DEFAULT/',
			'N',
			'/USE_DEFAULT/',
			'N',
			$title,
			'',
			$stub,
			'',
			'N',
			'Y',
			$regdate
		);
		return new BaseObject();
	}

	public static function syncDomesticMissionMenus(array $items): BaseObject
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$L = self::uiLabels();
		$mission_name = (string)($L['sub_top_menus']['mission'] ?? '??? ??');
		$domestic_name = (string)($L['domestic_mission']['page_title'] ?? '????');

		$mission_grp = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND parent_srl = 0 AND name = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mission_name
		)->fetch(\PDO::FETCH_OBJ);
		if (!$mission_grp || empty($mission_grp->menu_item_srl))
		{
			return new BaseObject(-1, '??? ?? ?? ??? ?? ? ????.');
		}
		$mission_grp_srl = (int)$mission_grp->menu_item_srl;

		$domestic_row = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND parent_srl = ? AND url = ? ORDER BY menu_item_srl ASC LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mission_grp_srl,
			self::DOMESTIC_MISSION_LIST_MID
		)->fetch(\PDO::FETCH_OBJ);
		if (!$domestic_row || empty($domestic_row->menu_item_srl))
		{
			return new BaseObject(-1, '???? ?? ??? ?? ? ????.');
		}
		$domestic_srl = (int)$domestic_row->menu_item_srl;

		$oDB->query(
			'UPDATE menu_item SET name = ?, listorder = ? WHERE menu_item_srl = ?',
			$domestic_name,
			-100000,
			$domestic_srl
		);

		$wanted = [];
		$order = 1;
		foreach ($items as $item)
		{
			if (empty($item['has_sub']))
			{
				continue;
			}
			$sub_mid = trim((string)($item['sub_mid'] ?? ''));
			$name = trim((string)($item['name'] ?? ''));
			if ($sub_mid === '' || $name === '')
			{
				continue;
			}
			$wanted[$sub_mid] = ['name' => $name, 'order' => $order++];
		}

		$sub_mids = array_keys($wanted);
		$existing = $oDB->query(
			'SELECT menu_item_srl, url, parent_srl FROM menu_item WHERE menu_srl = ? AND parent_srl = ?',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$domestic_srl
		)->fetchAll(\PDO::FETCH_OBJ);

		foreach ($existing as $row)
		{
			$url = trim((string)$row->url);
			if ($url === '' || $url === self::DOMESTIC_MISSION_LIST_MID)
			{
				continue;
			}
			if (!preg_match('/^p\d+$/', $url))
			{
				continue;
			}
			if (isset($wanted[$url]))
			{
				continue;
			}
			$oDB->query('DELETE FROM menu_item WHERE menu_item_srl = ?', (int)$row->menu_item_srl);
		}

		foreach ($wanted as $sub_mid => $meta)
		{
			$row = $oDB->query(
				'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND url = ? LIMIT 1',
				self::DOMESTIC_MISSION_MAIN_MENU_SRL,
				$sub_mid
			)->fetch(\PDO::FETCH_OBJ);
			$listorder = -((int)$meta['order'] * 10);
			if ($row && !empty($row->menu_item_srl))
			{
				$oDB->query(
					'UPDATE menu_item SET parent_srl = ?, name = ?, listorder = ? WHERE menu_item_srl = ?',
					$domestic_srl,
					$meta['name'],
					$listorder,
					(int)$row->menu_item_srl
				);
			}
			else
			{
				$srl = getNextSequence();
				$oDB->query(
					'INSERT INTO menu_item (menu_item_srl, parent_srl, menu_srl, name, url, is_shortcut, open_window, expand, listorder, regdate) VALUES (?,?,?,?,?,?,?,?,?,?)',
					$srl,
					$domestic_srl,
					self::DOMESTIC_MISSION_MAIN_MENU_SRL,
					$meta['name'],
					$sub_mid,
					'N',
					'N',
					'N',
					$listorder,
					date('YmdHis')
				);
			}
		}

		Rhymix\Framework\Cache::clearGroup('menu');
		return new BaseObject();
	}

	public static function renderDomesticMissionList(array $data): string
	{
		$groups = [];
		foreach (self::getDomesticMissionCategories() as $key => $label)
		{
			$groups[$key] = [];
		}
		$decorated = [];
		foreach ((array)($data['items'] ?? []) as $pos => $item)
		{
			$name = trim((string)($item['name'] ?? ''));
			if ($name === '')
			{
				continue;
			}
			$ord = (int)($item['order'] ?? 0);
			$decorated[] = ['item' => $item, 'ord' => $ord > 0 ? $ord : PHP_INT_MAX, 'pos' => $pos];
		}
		usort($decorated, function ($a, $b) {
			if ($a['ord'] !== $b['ord'])
			{
				return $a['ord'] <=> $b['ord'];
			}
			return $a['pos'] <=> $b['pos'];
		});
		foreach ($decorated as $d)
		{
			$item = $d['item'];
			$cat = self::normalizeDomesticCategory((string)($item['category'] ?? ''));
			$groups[$cat][] = $item;
		}
		foreach ($groups as $cat => &$list)
		{
			usort($list, function ($a, $b) {
				$a_sub = !empty($a['has_sub']) ? 0 : 1;
				$b_sub = !empty($b['has_sub']) ? 0 : 1;
				if ($a_sub !== $b_sub)
				{
					return $a_sub <=> $b_sub;
				}
				$a_ord = (int)($a['order'] ?? 0);
				$b_ord = (int)($b['order'] ?? 0);
				$a_ord = $a_ord > 0 ? $a_ord : PHP_INT_MAX;
				$b_ord = $b_ord > 0 ? $b_ord : PHP_INT_MAX;
				return $a_ord <=> $b_ord;
			});
		}
		unset($list);

		$html = '<div class="church-domestic-mission"><div class="church-dm-columns">';
		foreach (self::getDomesticMissionCategories() as $key => $label)
		{
			$list = $groups[$key];
			if (!$list)
			{
				continue;
			}
			$html .= '<section class="church-dm-col">';
			$html .= '<h2 class="church-dm-heading">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h2>';
			$html .= '<ul class="church-dm-list">';
			foreach ($list as $item)
			{
				$html .= self::renderDomesticMissionListItem($item);
			}
			$html .= '</ul></section>';
		}
		$html .= '</div></div>';
		return $html;
	}

	/** @param array<string,mixed> $item */
	protected static function getDomesticMissionItemImage(array $item): string
	{
		$has_sub = !empty($item['has_sub']);
		if ($has_sub)
		{
			$img = trim((string)($item['sub_photo'] ?? ''));
		}
		else
		{
			$img = trim((string)($item['thumb'] ?? ''));
		}
		return $img;
	}

	/** @param array<string,mixed> $item */
	protected static function renderDomesticMissionListItem(array $item): string
	{
		$name = trim((string)($item['name'] ?? ''));
		$img = self::getDomesticMissionItemImage($item);
		$has_sub = !empty($item['has_sub']) && trim((string)($item['sub_mid'] ?? '')) !== '';
		$sub_mid = trim((string)($item['sub_mid'] ?? ''));

		$html = '<li class="church-dm-item">';
		if ($img !== '')
		{
			$html .= '<figure class="church-dm-item-photo"><img src="' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" loading="lazy" /></figure>';
		}
		$html .= '<div class="church-dm-item-body">';
		if ($has_sub)
		{
			$url = htmlspecialchars(getNotEncodedUrl('', 'mid', $sub_mid), ENT_QUOTES, 'UTF-8');
			$html .= '<strong class="church-dm-item-name"><a href="' . $url . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a></strong>';
		}
		else
		{
			$html .= '<strong class="church-dm-item-name">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>';
		}
		$html .= '</div></li>';
		return $html;
	}

	/** @param array<string,mixed> $item */
	protected static function renderDomesticMissionListCard(array $item): string
	{
		$name = trim((string)($item['name'] ?? ''));
		$thumb = trim((string)($item['thumb'] ?? ''));
		if ($thumb === '')
		{
			$thumb = trim((string)($item['sub_photo'] ?? ''));
		}
		if ($thumb === '')
		{
			$thumb = './files/church/sub_top/mission.jpg';
		}
		$has_sub = !empty($item['has_sub']) && trim((string)($item['sub_mid'] ?? '')) !== '';
		$sub_mid = trim((string)($item['sub_mid'] ?? ''));
		$teaser = '';
		if ($has_sub)
		{
			$body = trim((string)($item['sub_body'] ?? ''));
			if ($body !== '')
			{
				$line = preg_split('/\r\n|\r|\n/', $body)[0] ?? $body;
				$teaser = trim((string)$line);
				if (function_exists('mb_strlen') && mb_strlen($teaser) > 120)
				{
					$teaser = mb_substr($teaser, 0, 120) . '...';
				}
			}
		}

		$html = '<article class="church-mission-card">';
		$html .= '<figure><img src="' . htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" loading="lazy" /></figure>';
		$html .= '<div class="church-mission-card-body">';
		if ($has_sub)
		{
			$url = htmlspecialchars(getNotEncodedUrl('', 'mid', $sub_mid), ENT_QUOTES, 'UTF-8');
			$html .= '<h3><a href="' . $url . '">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</a></h3>';
		}
		else
		{
			$html .= '<h3>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</h3>';
		}
		if ($teaser !== '')
		{
			$html .= '<p class="church-dm-teaser">' . htmlspecialchars($teaser, ENT_QUOTES, 'UTF-8') . '</p>';
		}
		if ($has_sub)
		{
			$url = htmlspecialchars(getNotEncodedUrl('', 'mid', $sub_mid), ENT_QUOTES, 'UTF-8');
			$html .= '<p class="church-dm-more"><a href="' . $url . '">??? ??</a></p>';
		}
		$html .= '</div></article>';
		return $html;
	}

	/** @param array<string,mixed> $item */
	public static function renderDomesticMissionSub(array $item): string
	{
		$name = trim((string)($item['name'] ?? ''));
		$photo = trim((string)($item['sub_photo'] ?? ''));
		if ($photo === '')
		{
			$photo = trim((string)($item['thumb'] ?? ''));
		}
		$body = trim((string)($item['sub_body'] ?? ''));

		$html = '<div class="church-mission-detail">';
		if ($photo !== '')
		{
			$html .= '<figure class="church-mission-detail-photo"><img src="' . htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" loading="lazy" /></figure>';
		}
		if ($body !== '')
		{
			$html .= '<div class="church-mission-detail-body">';
			$lines = preg_split('/\r\n|\r|\n/', $body) ?: [$body];
			foreach ($lines as $line)
			{
				$line = trim($line);
				if ($line === '')
				{
					$html .= '<br />';
					continue;
				}
				if (preg_match('#^https?://#i', $line))
				{
					$safe = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
					$html .= '<p><a href="' . $safe . '" target="_blank" rel="noopener">' . $safe . '</a></p>';
				}
				else
				{
					$html .= '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
				}
			}
			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function publishDomesticMissionAll(): BaseObject
	{
		$data = self::getDomesticMissionData();

		$list_srl = self::getPageModuleSrl(self::DOMESTIC_MISSION_LIST_MID);
		if ($list_srl < 1)
		{
			return new BaseObject(-1, '???? ??(p25)? ?? ? ????.');
		}

		$list_html = self::renderDomesticMissionList($data);
		$output = self::updatePageModuleContent($list_srl, $list_html);
		if (!$output->toBool())
		{
			return $output;
		}
		self::clearPageModuleCache($list_srl, self::DOMESTIC_MISSION_LIST_MID);

		foreach ((array)($data['items'] ?? []) as $item)
		{
			if (empty($item['has_sub']))
			{
				continue;
			}
			$sub_mid = trim((string)($item['sub_mid'] ?? ''));
			$name = trim((string)($item['name'] ?? ''));
			if ($sub_mid === '' || $name === '')
			{
				continue;
			}
			$ensure = self::ensureDomesticMissionSubPage($sub_mid, $name);
			if (!$ensure->toBool())
			{
				return $ensure;
			}
			$sub_srl = self::getPageModuleSrl($sub_mid);
			if ($sub_srl < 1)
			{
				return new BaseObject(-1, '?? ???(' . $sub_mid . ')? ?? ? ????.');
			}
			$sub_html = self::renderDomesticMissionSub($item);
			$output = self::updatePageModuleContent($sub_srl, $sub_html);
			if (!$output->toBool())
			{
				return $output;
			}
			self::clearPageModuleCache($sub_srl, $sub_mid);
		}

		$menu_out = self::syncDomesticMissionMenus((array)($data['items'] ?? []));
		if (!$menu_out->toBool())
		{
			return $menu_out;
		}

		return new BaseObject();
	}

	public static function saveDomesticMissionListData(array $data): BaseObject
	{
		$current = self::getDomesticMissionData();
		$existing_map = [];
		foreach ($current['items'] as $ex)
		{
			$key = trim((string)($ex['id'] ?? ''));
			if ($key === '' && !empty($ex['sub_mid']))
			{
				$key = (string)$ex['sub_mid'];
			}
			if ($key !== '')
			{
				$existing_map[$key] = $ex;
			}
		}
		$items = [];
		foreach ((array)($data['items'] ?? []) as $item)
		{
			if (!is_array($item))
			{
				continue;
			}
			$norm = self::normalizeDomesticMissionItem($item);
			if ($norm['name'] === '')
			{
				continue;
			}
			if ($norm['id'] === '')
			{
				$norm['id'] = 'dm_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
			}
			$lookup = $norm['id'];
			if (isset($existing_map[$lookup]))
			{
				$prev = $existing_map[$lookup];
				$norm['sub_photo'] = $norm['sub_photo'] !== '' ? $norm['sub_photo'] : (string)($prev['sub_photo'] ?? '');
				$norm['sub_body'] = $norm['sub_body'] !== '' ? $norm['sub_body'] : (string)($prev['sub_body'] ?? '');
				if ($norm['sub_mid'] === '' && !empty($prev['sub_mid']))
				{
					$norm['sub_mid'] = (string)$prev['sub_mid'];
				}
			}
			elseif ($norm['sub_mid'] !== '' && isset($existing_map[$norm['sub_mid']]))
			{
				$prev = $existing_map[$norm['sub_mid']];
				$norm['sub_photo'] = $norm['sub_photo'] !== '' ? $norm['sub_photo'] : (string)($prev['sub_photo'] ?? '');
				$norm['sub_body'] = $norm['sub_body'] !== '' ? $norm['sub_body'] : (string)($prev['sub_body'] ?? '');
			}
			if ($norm['has_sub'] && $norm['sub_mid'] === '')
			{
				$norm['sub_mid'] = self::allocDomesticMissionSubMid($current);
			}
			if (!$norm['has_sub'])
			{
				$norm['sub_mid'] = '';
				$norm['sub_photo'] = '';
				$norm['sub_body'] = '';
			}
			else
			{
				$norm['thumb'] = '';
			}
			$items[] = $norm;
		}
		$payload = [
			'page_title' => trim((string)($data['page_title'] ?? '????')),
			'next_sub_frame' => (int)($current['next_sub_frame'] ?? self::DOMESTIC_MISSION_SUB_FRAME_START),
			'items' => $items,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveDomesticMissionData($payload))
		{
			$path = self::getDomesticMissionFilePath();
			if (is_file($path) && !is_writable($path))
			{
				return new BaseObject(-1, '???? ??? ??? ? ? ????. ???? files/church/domestic_mission.json ??(nobody ?? ??)? ?????.');
			}
			return new BaseObject(-1, '???? ???? ???? ?????.');
		}
		return self::publishDomesticMissionAll();
	}

	public static function saveDomesticMissionSubData(string $sub_mid, array $fields): BaseObject
	{
		$sub_mid = trim($sub_mid);
		$data = self::getDomesticMissionData();
		$found = false;
		foreach ($data['items'] as &$item)
		{
			if (empty($item['has_sub']) || ($item['sub_mid'] ?? '') !== $sub_mid)
			{
				continue;
			}
			$found = true;
			if (isset($fields['sub_photo']))
			{
				$item['sub_photo'] = self::normalizeGuidePhotoUrl((string)$fields['sub_photo']);
			}
			if (isset($fields['sub_body']))
			{
				$item['sub_body'] = trim((string)$fields['sub_body']);
			}
			if (isset($fields['name']) && trim((string)$fields['name']) !== '')
			{
				$item['name'] = trim((string)$fields['name']);
			}
			break;
		}
		unset($item);
		if (!$found)
		{
			return new BaseObject(-1, '?? ??? ??? ?? ? ????.');
		}
		$payload = $data;
		$payload['updated'] = date('Y-m-d H:i:s');
		if (!self::saveDomesticMissionData($payload))
		{
			return new BaseObject(-1, '???? ??? ??? ??????.');
		}
		return self::publishDomesticMissionAll();
	}

	public static function getDomesticMissionListForEdit(): object
	{
		$data = self::getDomesticMissionData();
		$o = new stdClass;
		$o->mid = self::DOMESTIC_MISSION_LIST_MID;
		$o->label = '????';
		$o->page_title = $data['page_title'];
		$o->items = $data['items'];
		$o->categories = self::getDomesticMissionCategories();
		$o->view_url = getNotEncodedUrl('', 'mid', self::DOMESTIC_MISSION_LIST_MID);
		return $o;
	}

	public static function getDomesticMissionSubForEdit(string $sub_mid): ?object
	{
		$item = self::getDomesticMissionItemBySubMid($sub_mid);
		if (!$item)
		{
			return null;
		}
		$o = new stdClass;
		$o->sub_mid = $sub_mid;
		$o->name = $item['name'];
		$o->category = $item['category'];
		$o->category_label = self::getDomesticMissionCategories()[$item['category']] ?? '';
		$o->sub_photo = $item['sub_photo'];
		$o->sub_body = $item['sub_body'];
		$o->view_url = getNotEncodedUrl('', 'mid', $sub_mid);
		$o->list_edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDomesticMissionListEdit');
		return $o;
	}

	/** @return array<int,object> */
	public static function getDomesticMissionSubOptions(): array
	{
		$out = [];
		foreach (self::getDomesticMissionData()['items'] as $item)
		{
			if (empty($item['has_sub']))
			{
				continue;
			}
			$sub_mid = trim((string)($item['sub_mid'] ?? ''));
			if ($sub_mid === '')
			{
				continue;
			}
			$o = new stdClass;
			$o->sub_mid = $sub_mid;
			$o->name = $item['name'];
			$o->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDomesticMissionSubEdit', 'sub_mid', $sub_mid);
			$out[] = $o;
		}
		return $out;
	}
}
