<?php
/**
 * 파송선교 전용 페이지 (p27)
 * — 헤더·사진콜라주·기도 / 선교편지·동영상은 게시판 / 성도 응원
 */
trait dmcadminDispatchMissionTrait
{
	public static function isDispatchMissionPage(string $mid): bool
	{
		return trim($mid) === self::DISPATCH_MISSION_PAGE_MID;
	}

	public static function getDispatchMissionPageLabel(string $mid = ''): string
	{
		$L = self::uiLabels()['dispatch_mission_page'] ?? [];
		return (string)($L['label'] ?? '파송선교');
	}

	/** 해외선교 LNB·메뉴에 표시되는 이름 (상세페이지와 동일하게 선교사명) */
	public static function getDispatchMissionMenuLabel(): string
	{
		$L = self::uiLabels()['dispatch_mission_page'] ?? [];
		$menu = trim((string)($L['menu_label'] ?? ''));
		if ($menu !== '')
		{
			return $menu;
		}
		$data = self::getDispatchMissionPageData();
		$name = trim((string)($data['missionary_name'] ?? ''));
		return $name !== '' ? $name : '박미경 선교사';
	}

	/** @return array<string,string> */
	public static function getDispatchMissionUiStrings(): array
	{
		$L = self::uiLabels()['dispatch_mission_page'] ?? [];
		if (!is_array($L))
		{
			$L = [];
		}
		return [
			'label' => (string)($L['label'] ?? '파송선교'),
			'tab_letter' => (string)($L['tab_letter'] ?? '선교편지'),
			'tab_video' => (string)($L['tab_video'] ?? 'Youtube 소식'),
			'tab_cheer' => (string)($L['tab_cheer'] ?? '응원 한마디'),
			'prayer_prefix' => (string)($L['prayer_prefix'] ?? '이번 달 기도'),
			'quick_letter' => (string)($L['quick_letter'] ?? '최신 선교편지'),
			'quick_video' => (string)($L['quick_video'] ?? '최신 영상'),
			'quick_cheer' => (string)($L['quick_cheer'] ?? '응원댓글'),
			'empty_letter' => (string)($L['empty_letter'] ?? '등록된 선교편지가 없습니다.'),
			'empty_video' => (string)($L['empty_video'] ?? '등록된 동영상이 없습니다.'),
			'empty_cheer' => (string)($L['empty_cheer'] ?? '등록된 응원이 없습니다.'),
			'cheer_placeholder' => (string)($L['cheer_placeholder'] ?? '선교사님께 응원 한마디를 남겨 주세요.'),
			'cheer_submit' => (string)($L['cheer_submit'] ?? '응원 남기기'),
			'reply_badge' => (string)($L['reply_badge'] ?? '선교사'),
			'reply_placeholder' => (string)($L['reply_placeholder'] ?? '성도님께 답글을 남깁니다.'),
			'login_needed' => (string)($L['login_needed'] ?? '응원을 남기려면 회원가입 또는 로그인이 필요합니다.'),
			'login_btn' => (string)($L['login_btn'] ?? '로그인'),
			'signup_btn' => (string)($L['signup_btn'] ?? '회원가입'),
			'write_letter' => (string)($L['write_letter'] ?? '선교편지 등록'),
			'write_video' => (string)($L['write_video'] ?? '동영상 등록'),
			'edit_prayer' => (string)($L['edit_prayer'] ?? '기도 제목 수정'),
		];
	}

	public static function getDispatchMissionUploadDir(): string
	{
		return \RX_BASEDIR . 'files/church/dispatch_mission';
	}

	public static function getDispatchMissionFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/dispatch_mission.json';
	}

	public static function getDispatchCheerFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/dispatch_cheer.json';
	}

	public static function getDispatchLetterMid(): string
	{
		return 'dispatch_letter';
	}

	public static function getDispatchVideoMid(): string
	{
		return 'dispatch_video';
	}

	/** @return array{page_title:string,country:string,missionary_name:string,place_name:string,intro:string,prayer_line:string,photo_desc:string,photos:array<int,string>} */
	public static function getDispatchMissionPageData(): array
	{
		$path = self::getDispatchMissionFilePath();
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
		for ($i = 0; $i < self::DISPATCH_MISSION_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($row['photos'][$i] ?? ''));
		}
		/* 구버전 단일 photo → photos[0] */
		if ($photos[0] === '' && !empty($row['photo']))
		{
			$photos[0] = self::normalizeGuidePhotoUrl((string)$row['photo']);
		}

		return [
			'page_title' => trim((string)($row['page_title'] ?? self::getDispatchMissionPageLabel())),
			'country' => trim((string)($row['country'] ?? '태국')),
			'missionary_name' => trim((string)($row['missionary_name'] ?? '박미경 선교사')),
			'place_name' => trim((string)($row['place_name'] ?? '치앙라이 기치교회')),
			'intro' => trim((string)($row['intro'] ?? '')),
			'prayer_line' => trim((string)($row['prayer_line'] ?? '')),
			'photo_desc' => trim((string)($row['photo_desc'] ?? '')),
			'photos' => $photos,
		];
	}

	public static function saveDispatchMissionPageData(array $data): BaseObject
	{
		$photos = [];
		for ($i = 0; $i < self::DISPATCH_MISSION_PHOTO_COUNT; $i++)
		{
			$photos[$i] = self::normalizeGuidePhotoUrl((string)($data['photos'][$i] ?? ''));
		}
		$payload = [
			'page_title' => trim((string)($data['page_title'] ?? self::getDispatchMissionPageLabel())),
			'country' => trim((string)($data['country'] ?? '')),
			'missionary_name' => trim((string)($data['missionary_name'] ?? '')),
			'place_name' => trim((string)($data['place_name'] ?? '')),
			'intro' => trim((string)($data['intro'] ?? '')),
			'prayer_line' => trim((string)($data['prayer_line'] ?? '')),
			'photo_desc' => trim((string)($data['photo_desc'] ?? '')),
			'photos' => $photos,
			'updated' => date('Y-m-d H:i:s'),
		];
		$path = self::getDispatchMissionFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return new BaseObject(-1, '파송선교 페이지 데이터를 저장하지 못했습니다.');
		}
		self::fixDomesticMissionFilePermissions($path);
		return new BaseObject();
	}

	public static function updateDispatchPrayerLine(string $prayer_line): BaseObject
	{
		$data = self::getDispatchMissionPageData();
		$data['prayer_line'] = trim($prayer_line);
		$output = self::saveDispatchMissionPageData($data);
		if (!$output->toBool())
		{
			return $output;
		}
		return self::publishDispatchMissionPage();
	}

	public static function extractYoutubeId(string $url): string
	{
		$url = trim($url);
		if ($url === '')
		{
			return '';
		}
		if (preg_match('~youtu\.be/([\w-]+)~i', $url, $m))
		{
			return $m[1];
		}
		if (preg_match('~[?&]v=([\w-]+)~i', $url, $m))
		{
			return $m[1];
		}
		if (preg_match('~youtube\.com/embed/([\w-]+)~i', $url, $m))
		{
			return $m[1];
		}
		if (preg_match('~youtube\.com/shorts/([\w-]+)~i', $url, $m))
		{
			return $m[1];
		}
		return '';
	}

	public static function youtubeThumbUrl(string $url): string
	{
		$id = self::extractYoutubeId($url);
		return $id === '' ? '' : 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg';
	}

	public static function getModuleSrlByMid(string $mid): int
	{
		$info = ModuleModel::getModuleInfoByMid($mid);
		return $info ? (int)$info->module_srl : 0;
	}

	/**
	 * @return list<array{document_srl:int,title:string,date:string,content:string,images:list<string>,youtube_id:string,summary:string,readed_count:int}>
	 */
	public static function listDispatchBoardDocuments(string $mid, int $limit = 36): array
	{
		$module_srl = self::getModuleSrlByMid($mid);
		if ($module_srl < 1)
		{
			return [];
		}
		$oDB = Rhymix\Framework\DB::getInstance();
		$rows = $oDB->query(
			'SELECT document_srl, title, content, regdate, readed_count FROM documents WHERE module_srl = ? AND (status = ? OR status IS NULL OR status = ?) ORDER BY list_order ASC LIMIT ' . max(1, min(100, $limit)),
			$module_srl,
			'PUBLIC',
			'PUBLIC'
		)->fetchAll(\PDO::FETCH_OBJ);
		$out = [];
		foreach ((array)$rows as $row)
		{
			$content = (string)($row->content ?? '');
			$images = [];
			if (preg_match_all('@<img[^>]+src=["\']([^"\']+)["\']@i', $content, $ms))
			{
				foreach ($ms[1] as $src)
				{
					$images[] = $src;
				}
			}
			$yt = '';
			if (preg_match('@youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})@', $content, $m))
			{
				$yt = $m[1];
			}
			$summary = '';
			if (preg_match('@<p class="cd-doc-summary">(.*?)</p>@is', $content, $sm))
			{
				$summary = trim(html_entity_decode(strip_tags($sm[1]), ENT_QUOTES, 'UTF-8'));
			}
			elseif (preg_match_all('@<p>(.*?)</p>@is', $content, $pms))
			{
				foreach ($pms[1] as $phtml)
				{
					$text = trim(html_entity_decode(strip_tags($phtml), ENT_QUOTES, 'UTF-8'));
					if ($text !== '' && stripos($text, 'iframe') === false)
					{
						$summary = $text;
						break;
					}
				}
			}
			$reg = (string)($row->regdate ?? '');
			$date = strlen($reg) >= 8
				? substr($reg, 0, 4) . '-' . substr($reg, 4, 2) . '-' . substr($reg, 6, 2)
				: '';
			$out[] = [
				'document_srl' => (int)$row->document_srl,
				'title' => (string)($row->title ?? ''),
				'date' => $date,
				'content' => $content,
				'images' => $images,
				'youtube_id' => $yt,
				'summary' => $summary,
				'readed_count' => (int)($row->readed_count ?? 0),
			];
		}
		return $out;
	}

	public static function renderDispatchMissionPage(array $data): string
	{
		$ui = self::getDispatchMissionUiStrings();
		$country = trim((string)($data['country'] ?? ''));
		$missionary = trim((string)($data['missionary_name'] ?? ''));
		$place = trim((string)($data['place_name'] ?? ''));
		$intro = trim((string)($data['intro'] ?? ''));
		$prayer = trim((string)($data['prayer_line'] ?? ''));
		$photo_desc = trim((string)($data['photo_desc'] ?? ''));
		$photos = (array)($data['photos'] ?? []);
		$flag_url = method_exists(__CLASS__, 'getOverseasMissionFlagUrl')
			? self::getOverseasMissionFlagUrl($country)
			: '';

		$filled = [];
		foreach ($photos as $p)
		{
			$p = trim((string)$p);
			if ($p !== '')
			{
				$filled[] = $p;
			}
		}
		$n = count($filled);

		$html = '<div class="church-dispatch" id="church-dispatch" data-mid="' . htmlspecialchars(self::DISPATCH_MISSION_PAGE_MID, ENT_QUOTES, 'UTF-8') . '">';

		$html .= '<header class="cd-header cd-header--collage">';

		$has_aside = ($country !== '' || $missionary !== '' || $place !== '' || $photo_desc !== '' || $flag_url !== '');
		if ($n > 0 || $has_aside)
		{
			$html .= '<div class="cd-hero-stage">';

			$html .= '<aside class="cd-aside">';
			$html .= '<div class="cd-aside-card">';
			if ($flag_url !== '')
			{
				$html .= '<div class="cd-aside-flag"><img src="' . htmlspecialchars($flag_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '" width="48" height="36" loading="lazy" /></div>';
			}
			if ($country !== '')
			{
				$html .= '<p class="cd-aside-meta cd-aside-meta--country"><span class="cd-aside-label">국가</span><span class="cd-aside-value">' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '</span></p>';
			}
			if ($missionary !== '')
			{
				$html .= '<p class="cd-aside-meta cd-aside-meta--missionary"><span class="cd-aside-label">선교사</span><span class="cd-aside-value">' . htmlspecialchars($missionary, ENT_QUOTES, 'UTF-8') . '</span></p>';
			}
			if ($place !== '' && $place !== $missionary)
			{
				$html .= '<p class="cd-aside-meta cd-aside-meta--place"><span class="cd-aside-label">선교지</span><span class="cd-aside-value">' . htmlspecialchars($place, ENT_QUOTES, 'UTF-8') . '</span></p>';
			}
			if ($photo_desc !== '')
			{
				$html .= '<div class="cd-aside-body">';
				$lines = preg_split('/\r\n|\r|\n/', $photo_desc) ?: [$photo_desc];
				$first = true;
				foreach ($lines as $line)
				{
					$line = trim($line);
					if ($line === '')
					{
						$html .= '<br />';
						continue;
					}
					$class = 'cd-aside-line' . ($first ? ' cd-aside-line--lead' : '');
					$first = false;
					$html .= '<p class="' . $class . '">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
				}
				$html .= '</div>';
			}
			$html .= '</div>';
			$html .= '</aside>';

			$html .= '<div class="cd-photos">';
			if ($n > 0)
			{
				$html .= '<div class="cd-collage cd-collage--' . min(6, $n) . '">';
				foreach ($filled as $i => $src)
				{
					$safe = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
					$alt = htmlspecialchars(($missionary !== '' ? $missionary : $ui['label']) . ' ' . ($i + 1), ENT_QUOTES, 'UTF-8');
					$html .= '<figure class="cd-collage-item cd-collage-item--' . ($i + 1) . '" data-full="' . $safe . '">';
					$html .= '<img src="' . $safe . '" alt="' . $alt . '" loading="lazy" />';
					$html .= '</figure>';
				}
				/* 영문 구절 — 와이어프레임 노란색 비스듬 영역 */
				$html .= '<blockquote class="cd-verse-wm cd-verse-pocket" cite="Matthew 28:19-20">';
				$html .= '<p class="cd-verse-wm-text">Therefore go and make disciples of all nations, baptizing them in the name of the Father and of the Son and of the Holy Spirit, and teaching them to obey everything I have commanded you. And surely I am with you always, to the very end of the age.</p>';
				$html .= '<cite class="cd-verse-wm-ref">Matthew 28:19–20</cite>';
				$html .= '</blockquote>';
				$html .= '</div>';
			}
			$html .= '</div>';

			$html .= '</div>';
			if ($n > 0)
			{
				$html .= '<div class="cd-photo-zoom" id="cd-photo-zoom" hidden>'
					. '<div class="cd-photo-zoom-card" role="dialog" aria-modal="true" aria-label="선교편지 보기">'
					. '<button type="button" class="cd-zoom-nav cd-zoom-prev" aria-label="이전 페이지" hidden><span aria-hidden="true"></span></button>'
					. '<img alt="" />'
					. '<button type="button" class="cd-zoom-nav cd-zoom-next" aria-label="다음 페이지" hidden><span aria-hidden="true"></span></button>'
					. '<span class="cd-zoom-page" id="cd-zoom-page" hidden></span>'
					. '<button type="button" class="cd-zoom-close" aria-label="닫기">&times;</button>'
					. '</div></div>';
			}
		}

		$html .= '</header>';

		$html .= '<section class="cd-quick" aria-label="최신 소식">';
		$html .= '<a class="cd-quick-card" href="#cd-tab-letter" data-cd-tab="letter">';
		$html .= '<span class="cd-quick-label">' . htmlspecialchars($ui['quick_letter'], ENT_QUOTES, 'UTF-8') . '</span>';
		$html .= '<div class="cd-quick-body" id="cd-quick-letter"><strong class="cd-muted">' . htmlspecialchars($ui['empty_letter'], ENT_QUOTES, 'UTF-8') . '</strong></div>';
		$html .= '</a>';
		$html .= '<a class="cd-quick-card" href="#cd-tab-video" data-cd-tab="video" id="cd-quick-video-card">';
		$html .= '<span class="cd-quick-label">' . htmlspecialchars($ui['quick_video'], ENT_QUOTES, 'UTF-8') . '</span>';
		$html .= '<div class="cd-quick-body" id="cd-quick-video"><strong class="cd-muted">' . htmlspecialchars($ui['empty_video'], ENT_QUOTES, 'UTF-8') . '</strong></div>';
		$html .= '</a>';
		$html .= '<div class="cd-quick-card cd-quick-card--cheer" data-cd-tab="cheer" id="cd-quick-cheer-card" role="link" tabindex="0">'
			. '<span class="cd-quick-label">' . htmlspecialchars($ui['quick_cheer'], ENT_QUOTES, 'UTF-8') . '</span>'
			. '<button type="button" class="cd-quick-cheer-write" id="cd-quick-cheer-write">'
			. htmlspecialchars($ui['cheer_submit'] ?? '응원 남기기', ENT_QUOTES, 'UTF-8')
			. '</button>'
			. '<div class="cd-quick-body" id="cd-quick-cheer"><strong class="cd-muted">'
			. htmlspecialchars($ui['empty_cheer'], ENT_QUOTES, 'UTF-8')
			. '</strong></div>'
			. '</div></section>';

		$html .= '<div class="cd-yt-popup" id="cd-yt-popup" hidden>';
		$html .= '<div class="cd-yt-popup-card"><button type="button" class="cd-yt-popup-close" id="cd-yt-popup-close" aria-label="닫기">×</button>';
		$html .= '<div class="cd-yt-popup-frame" id="cd-yt-popup-frame"></div></div></div>';

		$html .= '<nav class="cd-tabs" role="tablist">';
		$html .= '<button type="button" class="cd-tab is-active" role="tab" aria-selected="true" data-cd-tab="letter">' . htmlspecialchars($ui['tab_letter'], ENT_QUOTES, 'UTF-8') . '</button>';
		$html .= '<button type="button" class="cd-tab" role="tab" aria-selected="false" data-cd-tab="video">' . htmlspecialchars($ui['tab_video'], ENT_QUOTES, 'UTF-8') . '</button>';
		$html .= '<button type="button" class="cd-tab" role="tab" aria-selected="false" data-cd-tab="cheer">' . htmlspecialchars($ui['tab_cheer'], ENT_QUOTES, 'UTF-8') . '</button>';
		$html .= '</nav>';

		$html .= '<section class="cd-panel is-active" id="cd-tab-letter" role="tabpanel" data-cd-panel="letter">';
		$html .= '<div class="cd-panel-toolbar" id="cd-letter-toolbar" hidden></div>';
		$html .= '<div id="cd-letter-list"><p class="cd-empty">불러오는 중…</p></div>';
		$html .= '</section>';

		$html .= '<section class="cd-panel" id="cd-tab-video" role="tabpanel" data-cd-panel="video" hidden>';
		$html .= '<div class="cd-panel-toolbar" id="cd-video-toolbar" hidden></div>';
		$html .= '<div id="cd-video-list"><p class="cd-empty">불러오는 중…</p></div>';
		$html .= '<div class="cd-video-player" id="cd-video-player" hidden></div>';
		$html .= '</section>';

		$html .= '<section class="cd-panel" id="cd-tab-cheer" role="tabpanel" data-cd-panel="cheer" hidden>';
		$html .= '<div class="cd-cheer" id="cd-cheer-root">';
		$html .= '<div class="cd-cheer-composer" id="cd-cheer-composer"></div>';
		$html .= '<div class="cd-cheer-list" id="cd-cheer-list"><p class="cd-empty">응원을 불러오는 중…</p></div>';
		$html .= '</div></section>';

		$config = [
			'mid' => self::DISPATCH_MISSION_PAGE_MID,
			'configUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteCheerConfig'),
			'listUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteCheerList'),
			'addUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteCheerAdd'),
			'replyUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteCheerReply'),
			'reactUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteCheerReact'),
			'deleteUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteCheerDelete'),
			'prayerUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteDispatchPrayerSave'),
			'feedUrl' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteDispatchFeed'),
			'loginUrl' => '/?church_login=1',
			'signupUrl' => getNotEncodedUrl('', 'act', 'dispMemberSignUpForm'),
			'letterMid' => self::getDispatchLetterMid(),
			'videoMid' => self::getDispatchVideoMid(),
			'ui' => $ui,
			'reactions' => [
				['key' => 'heart', 'emoji' => '❤️', 'label' => '사랑'],
				['key' => 'like', 'emoji' => '👍', 'label' => '좋아요'],
				['key' => 'smile', 'emoji' => '😊', 'label' => '기쁨'],
				['key' => 'sad', 'emoji' => '😢', 'label' => '슬픔'],
				['key' => 'pray', 'emoji' => '🙏', 'label' => '기도·미안'],
			],
		];
		$html .= '<script type="application/json" id="cd-cheer-config">' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . '</script>';
		$html .= '<script src="./addons/church_board_ui/church_board_ui.js?t=' . date('Ymd') . '" defer></script>';
		$html .= '<script src="./addons/church_dispatch_cheer/church_dispatch_cheer.js?t=' . date('YmdHi') . '" defer></script>';
		$html .= '</div>';
		return $html;
	}

	public static function publishDispatchMissionPage(?array $data = null): BaseObject
	{
		$mid = self::DISPATCH_MISSION_PAGE_MID;
		$module_srl = self::getPageModuleSrl($mid);
		if ($module_srl < 1)
		{
			return new BaseObject(-1, '파송선교 페이지(p27)를 찾을 수 없습니다. setup_dispatch_mission_page.php를 먼저 실행하세요.');
		}
		if ($data !== null)
		{
			$output = self::saveDispatchMissionPageData($data);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		$html = self::renderDispatchMissionPage(self::getDispatchMissionPageData());
		$output = self::updatePageModuleContent($module_srl, $html);
		if (!$output->toBool())
		{
			return $output;
		}
		self::clearPageModuleCache($module_srl, $mid);
		return new BaseObject();
	}

	public static function getDispatchMissionPageForEdit(): ?object
	{
		$mid = self::DISPATCH_MISSION_PAGE_MID;
		if (self::getPageModuleSrl($mid) < 1)
		{
			return null;
		}
		$data = self::getDispatchMissionPageData();
		$o = new stdClass;
		$o->mid = $mid;
		$o->label = self::getDispatchMissionPageLabel($mid);
		$o->module_srl = self::getPageModuleSrl($mid);
		$o->page_title = $data['page_title'];
		$o->country = $data['country'];
		$o->missionary_name = $data['missionary_name'];
		$o->place_name = $data['place_name'];
		$o->intro = $data['intro'];
		$o->prayer_line = $data['prayer_line'];
		$o->photo_desc = $data['photo_desc'];
		$o->photos = $data['photos'];
		$o->view_url = getNotEncodedUrl('', 'mid', $mid);
		$o->letter_board_url = getNotEncodedUrl('', 'mid', self::getDispatchLetterMid());
		$o->video_board_url = getNotEncodedUrl('', 'mid', self::getDispatchVideoMid());
		return $o;
	}

	public static function ensureDispatchMissionMenuItem(int $listorder = -10): BaseObject
	{
		$mid = self::DISPATCH_MISSION_PAGE_MID;
		$menu_name = self::getDispatchMissionMenuLabel();
		$oDB = Rhymix\Framework\DB::getInstance();
		$L = self::uiLabels();
		$mission_name = (string)($L['sub_top_menus']['mission'] ?? '선교와 봉사');

		$mission_grp = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND parent_srl = 0 AND name = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mission_name
		)->fetch(\PDO::FETCH_OBJ);
		if (!$mission_grp || empty($mission_grp->menu_item_srl))
		{
			return new BaseObject(-1, '선교와 봉사 메뉴 그룹을 찾을 수 없습니다.');
		}
		$mission_grp_srl = (int)$mission_grp->menu_item_srl;

		$overseas = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND url = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			self::OVERSEAS_MISSION_LIST_MID
		)->fetch(\PDO::FETCH_OBJ);
		$parent_srl = $overseas && !empty($overseas->menu_item_srl)
			? (int)$overseas->menu_item_srl
			: $mission_grp_srl;

		$row = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND url = ? LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mid
		)->fetch(\PDO::FETCH_OBJ);

		if ($row && !empty($row->menu_item_srl))
		{
			$oDB->query(
				'UPDATE menu_item SET parent_srl = ?, name = ?, listorder = ? WHERE menu_item_srl = ?',
				$parent_srl,
				$menu_name,
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
				$parent_srl,
				self::DOMESTIC_MISSION_MAIN_MENU_SRL,
				$menu_name,
				$mid,
				'N',
				'N',
				'N',
				$listorder,
				date('YmdHis')
			);
		}

		/* 브라우저 탭 제목도 선교사명으로 맞춤 */
		$mod_srl = self::getPageModuleSrl($mid);
		if ($mod_srl > 0)
		{
			$oDB->query('UPDATE modules SET browser_title = ? WHERE module_srl = ?', $menu_name, $mod_srl);
		}

		Rhymix\Framework\Cache::clearGroup('menu');
		Rhymix\Framework\Storage::deleteDirectory(\RX_BASEDIR . 'files/cache/menu', false);
		$oMenuAdminController = getController('menu');
		if (!$oMenuAdminController)
		{
			$oMenuAdminController = getAdminController('menu');
		}
		if ($oMenuAdminController && method_exists($oMenuAdminController, 'makeXmlFile'))
		{
			$oMenuAdminController->makeXmlFile(self::DOMESTIC_MISSION_MAIN_MENU_SRL);
		}
		return new BaseObject();
	}

	/* ===================== 성도 응원 JSON ===================== */

	/** @return array{comments:array<int,array<string,mixed>>} */
	public static function getDispatchCheerData(): array
	{
		$path = self::getDispatchCheerFilePath();
		$row = ['comments' => []];
		if (is_file($path))
		{
			$decoded = json_decode(file_get_contents($path) ?: '', true);
			if (is_array($decoded) && isset($decoded['comments']) && is_array($decoded['comments']))
			{
				$row['comments'] = $decoded['comments'];
			}
		}
		return $row;
	}

	public static function saveDispatchCheerData(array $data): BaseObject
	{
		$path = self::getDispatchCheerFilePath();
		$dir = dirname($path);
		FileHandler::makeDir($dir);
		if (is_file($path) && !is_writable($path))
		{
			@chmod($path, 0666);
		}
		if (!is_writable($dir) && !(is_file($path) && is_writable($path)))
		{
			return new BaseObject(-1, '응원 저장 폴더에 쓰기 권한이 없습니다. 관리자에게 문의해 주세요.');
		}
		$payload = [
			'comments' => array_values((array)($data['comments'] ?? [])),
			'updated' => date('Y-m-d H:i:s'),
		];
		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false)
		{
			return new BaseObject(-1, '응원 데이터를 저장하지 못했습니다.');
		}
		$tmp = $path . '.tmp.' . getmypid();
		if (file_put_contents($tmp, $json) === false)
		{
			return new BaseObject(-1, '응원 데이터를 저장하지 못했습니다.');
		}
		@chmod($tmp, 0664);
		if (!@rename($tmp, $path))
		{
			$ok = @copy($tmp, $path);
			@unlink($tmp);
			if (!$ok)
			{
				return new BaseObject(-1, '응원 데이터를 저장하지 못했습니다.');
			}
			@chmod($path, 0664);
		}
		return new BaseObject();
	}

	/** @return list<string> */
	public static function cheerReactionKeys(): array
	{
		return ['heart', 'like', 'smile', 'sad', 'pray'];
	}

	/** @param array<string,mixed> $reactions */
	public static function summarizeCheerReactions(array $reactions, int $viewer_srl = 0): array
	{
		$out = [];
		foreach (self::cheerReactionKeys() as $key)
		{
			$members = array_values(array_unique(array_map('intval', (array)($reactions[$key] ?? []))));
			$out[$key] = [
				'count' => count($members),
				'mine' => $viewer_srl > 0 && in_array($viewer_srl, $members, true),
			];
		}
		return $out;
	}

	/** 응원 표시명: 실명(user_name) 우선 */
	public static function cheerMemberDisplayName(int $member_srl, string $fallback = ''): string
	{
		if ($member_srl > 0)
		{
			$m = MemberModel::getMemberInfoByMemberSrl($member_srl);
			if ($m)
			{
				$name = trim((string)($m->user_name ?? ''));
				if ($name !== '')
				{
					return $name;
				}
				$nick = trim((string)($m->nick_name ?? ''));
				if ($nick !== '')
				{
					return $nick;
				}
			}
		}
		$fb = trim($fallback);
		return $fb !== '' ? $fb : '성도';
	}

	/** @param array<string,mixed> $comment */
	public static function formatCheerCommentForClient(array $comment, int $viewer_srl = 0, bool $can_moderate = false): array
	{
		$reply = null;
		if (!empty($comment['reply']) && is_array($comment['reply']))
		{
			$r = $comment['reply'];
			$reply_srl = (int)($r['member_srl'] ?? 0);
			$reply = [
				'body' => (string)($r['body'] ?? ''),
				'nick_name' => self::cheerMemberDisplayName($reply_srl, (string)($r['nick_name'] ?? '')),
				'created' => (string)($r['created'] ?? ''),
				'member_srl' => $reply_srl,
				'is_missionary' => true,
				'reactions' => self::summarizeCheerReactions((array)($r['reactions'] ?? []), $viewer_srl),
				'can_edit' => $can_moderate || ($viewer_srl > 0 && $viewer_srl === $reply_srl),
			];
		}
		$author = (int)($comment['member_srl'] ?? 0);
		return [
			'id' => (string)($comment['id'] ?? ''),
			'body' => (string)($comment['body'] ?? ''),
			'nick_name' => self::cheerMemberDisplayName($author, (string)($comment['nick_name'] ?? '')),
			'created' => (string)($comment['created'] ?? ''),
			'member_srl' => $author,
			'reactions' => self::summarizeCheerReactions((array)($comment['reactions'] ?? []), $viewer_srl),
			'reply' => $reply,
			'can_delete' => $can_moderate || ($viewer_srl > 0 && $viewer_srl === $author),
		];
	}
}
