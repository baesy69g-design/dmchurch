<?php
/**
 * @class  church_writeModel
 */
class church_writeModel extends church_write
{
	public const ADMIN_USER_ID = 'dmc2241';
	public const PRAY_MODULE_SRL = 126;

	/** @var int[] 일반 회원도 글쓰기 버튼(기본 폼) */
	public static function publicWriteModuleSrls(): array
	{
		return [112, self::PRAY_MODULE_SRL];
	}

	public static function publicWriteMids(): array
	{
		return ['community', 'pray'];
	}

	public static function targetModuleSrls(): array
	{
		return [110, 112, 114, 116, 118, 120, 122, 124, self::PRAY_MODULE_SRL];
	}

	public static function isPrayBoard(int $module_srl = 0, string $mid = ''): bool
	{
		return $module_srl === self::PRAY_MODULE_SRL || $mid === 'pray';
	}

	/** @return int[] dmcadmin에 등록된 기도요청 전체 열람자 member_srl (최대 2명) */
	public static function getPrayerReaderSrls(): array
	{
		static $cache = null;
		if ($cache !== null)
		{
			return $cache;
		}

		$config = ModuleModel::getModuleConfig('church_write');
		$list = [];
		if ($config && !empty($config->prayer_reader_srls))
		{
			if (is_string($config->prayer_reader_srls))
			{
				$list = array_filter(array_map('intval', preg_split('/[\s,]+/', $config->prayer_reader_srls)));
			}
			elseif (is_array($config->prayer_reader_srls))
			{
				$list = array_map('intval', $config->prayer_reader_srls);
			}
		}

		$cache = array_values(array_unique(array_filter($list)));
		return $cache;
	}

	public static function setPrayerReaderSrls(array $member_srls): BaseObject
	{
		$clean = array_values(array_unique(array_filter(array_map('intval', $member_srls))));
		if (count($clean) > 2)
		{
			return new BaseObject(-1, '기도요청 조회자는 최대 2명입니다.');
		}
		$config = ModuleModel::getModuleConfig('church_write') ?: new stdClass;
		$config->prayer_reader_srls = $clean;
		$oModuleController = getController('module');
		return $oModuleController->insertModuleConfig('church_write', $config);
	}

	public static function canReadPrayerContent($document, $logged_info): bool
	{
		if (!$logged_info || !$document || !method_exists($document, 'isExists') || !$document->isExists())
		{
			return false;
		}

		$reader_srl = (int)($logged_info->member_srl ?? 0);
		$author_srl = (int)($document->member_srl ?? 0);
		if ($author_srl && $reader_srl === $author_srl)
		{
			return true;
		}

		return in_array($reader_srl, self::getPrayerReaderSrls(), true);
	}

	public static function getPrayPrivateContentHtml(): string
	{
		return '<div class="pray-private-notice"><p>다른 분의 기도 요청 내용은 열람할 수 없습니다.<br>제목과 날짜만 확인할 수 있습니다.</p></div>';
	}

	public static function sortPrayDocumentList(array $list, $logged_info): array
	{
		if (!$logged_info || count($list) < 2)
		{
			return $list;
		}

		$my_srl = (int)($logged_info->member_srl ?? 0);
		$mine = [];
		$others = [];
		foreach ($list as $doc)
		{
			if ((int)($doc->member_srl ?? 0) === $my_srl)
			{
				$mine[] = $doc;
			}
			else
			{
				$others[] = $doc;
			}
		}

		return array_merge($mine, $others);
	}

	public static function applyPrayBoardView(): void
	{
		Context::loadFile('./addons/church_board_ui/church_pray.css');
		Context::addBodyClass('church-pray-board');

		$logged_info = Context::get('logged_info');
		if (!$logged_info)
		{
			return;
		}

		$list = Context::get('document_list');
		if (is_array($list) && $list)
		{
			Context::set('document_list', self::sortPrayDocumentList($list, $logged_info));
		}

		$oDocument = Context::get('oDocument');
		if (!$oDocument || !method_exists($oDocument, 'isExists') || !$oDocument->isExists())
		{
			return;
		}

		if (self::canReadPrayerContent($oDocument, $logged_info))
		{
			return;
		}

		Context::addBodyClass('church-pray-hidden-read');
		$oDocument->add('content', self::getPrayPrivateContentHtml());
		Context::set('oDocument', $oDocument);
	}

	public static function isChurchAdmin($logged_info): bool
	{
		if (!$logged_info || !isset($logged_info->user_id))
		{
			return false;
		}
		return $logged_info->user_id === self::ADMIN_USER_ID || $logged_info->is_admin === 'Y';
	}

	public static function getBoardForms(): array
	{
		return [
			110 => [
				'mid' => 'sermon',
				'title' => '주일대예배설교 등록',
				'fields' => [
					['name' => 'title', 'label' => '말씀 제목', 'type' => 'text', 'required' => true],
					['name' => 'subtitle', 'label' => '본문', 'type' => 'text'],
					['name' => 'speaker', 'label' => '설교자', 'type' => 'text'],
					['name' => 'pubdate', 'label' => '설교일', 'type' => 'date', 'required' => true],
					['name' => 'youtube_url', 'label' => '유튜브 URL', 'type' => 'url', 'required' => true, 'help' => 'youtube.com 또는 youtu.be 링크'],
					['name' => 'summary', 'label' => '성경구절', 'type' => 'textarea'],
				],
			],
			114 => [
				'mid' => 'jubo',
				'title' => '주보 등록',
				'fields' => [
					['name' => 'title', 'label' => '주보 제목', 'type' => 'text', 'required' => true, 'placeholder' => '예: 0613 주보'],
					['name' => 'pubdate', 'label' => '주일 날짜', 'type' => 'date', 'required' => true],
					['name' => 'news_image', 'label' => '교회소식 이미지', 'type' => 'file', 'accept' => 'image/*', 'required' => true, 'help' => '목록 썸네일로 사용됩니다. 큰 사진은 자동으로 약 2MB(가로·세로 1600px) 이내로 줄여 저장됩니다.'],
					['name' => 'front_image', 'label' => '앞면 이미지', 'type' => 'file', 'accept' => 'image/*', 'required' => true, 'help' => '큰 사진은 자동으로 약 2MB(가로·세로 1600px) 이내로 줄여 저장됩니다.'],
					['name' => 'back_image', 'label' => '뒷면 이미지', 'type' => 'file', 'accept' => 'image/*', 'required' => true, 'help' => '큰 사진은 자동으로 약 2MB(가로·세로 1600px) 이내로 줄여 저장됩니다.'],
				],
			],
			116 => [
				'mid' => 'choir',
				'title' => '성가대 영상 등록',
				'fields' => [
					['name' => 'title', 'label' => '제목', 'type' => 'text', 'required' => true],
					['name' => 'speaker', 'label' => '팀/설명', 'type' => 'text', 'default' => '성가대'],
					['name' => 'pubdate', 'label' => '날짜', 'type' => 'date', 'required' => true],
					['name' => 'youtube_url', 'label' => '유튜브 URL', 'type' => 'url', 'required' => true],
					['name' => 'summary', 'label' => '메모', 'type' => 'textarea'],
				],
			],
			118 => [
				'mid' => 'peniel',
				'title' => '브니엘찬양팀 영상 등록',
				'fields' => [
					['name' => 'title', 'label' => '제목', 'type' => 'text', 'required' => true],
					['name' => 'speaker', 'label' => '팀/설명', 'type' => 'text', 'default' => '브니엘찬양팀'],
					['name' => 'pubdate', 'label' => '날짜', 'type' => 'date', 'required' => true],
					['name' => 'youtube_url', 'label' => '유튜브 URL', 'type' => 'url', 'required' => true],
					['name' => 'summary', 'label' => '메모', 'type' => 'textarea'],
				],
			],
			120 => [
				'mid' => 'eventvideo',
				'title' => '교회행사 영상 등록',
				'fields' => [
					['name' => 'title', 'label' => '행사 제목', 'type' => 'text', 'required' => true],
					['name' => 'speaker', 'label' => '행사/설명', 'type' => 'text'],
					['name' => 'pubdate', 'label' => '날짜', 'type' => 'date', 'required' => true],
					['name' => 'youtube_url', 'label' => '유튜브 URL', 'type' => 'url'],
					['name' => 'video_url', 'label' => 'MP4 직접 URL', 'type' => 'url', 'help' => '유튜브가 없으면 MP4 주소 입력'],
					['name' => 'summary', 'label' => '메모', 'type' => 'textarea'],
				],
			],
			122 => [
				'mid' => 'picture',
				'title' => '행사사진 등록',
				'fields' => [
					['name' => 'title', 'label' => '행사 제목', 'type' => 'text', 'required' => true],
					['name' => 'pubdate', 'label' => '행사 날짜', 'type' => 'date', 'required' => true],
					['name' => 'photos', 'label' => '사진 (여러 장)', 'type' => 'file', 'accept' => 'image/*', 'multiple' => true, 'required' => true, 'help' => '큰 사진은 자동으로 약 2MB(가로·세로 1600px) 이내로 줄여 저장됩니다.'],
					['name' => 'summary', 'label' => '설명', 'type' => 'textarea'],
				],
			],
			124 => [
				'mid' => 'newface',
				'title' => '새가족소개 등록',
				'fields' => [
					['name' => 'title', 'label' => '이름', 'type' => 'text', 'required' => true],
					['name' => 'pubdate', 'label' => '등록일', 'type' => 'date', 'required' => true],
					['name' => 'photo', 'label' => '사진', 'type' => 'file', 'accept' => 'image/*', 'required' => true, 'help' => '큰 사진은 자동으로 약 2MB(가로·세로 1600px) 이내로 줄여 저장됩니다.'],
					['name' => 'summary', 'label' => '설명', 'type' => 'textarea', 'required' => true],
				],
			],
		];
	}

	public static function getClientConfig(int $module_srl, $logged_info, string $mid = ''): ?array
	{
		if (!in_array($module_srl, self::targetModuleSrls(), true) && !self::isPrayBoard($module_srl, $mid))
		{
			return null;
		}

		$forms = self::getBoardForms();
		$is_pray = self::isPrayBoard($module_srl, $mid);
		$is_public = in_array($module_srl, self::publicWriteModuleSrls(), true)
			|| in_array($mid, self::publicWriteMids(), true);
		$is_admin = self::isChurchAdmin($logged_info);
		$csrf = '';
		if (Context::get('is_logged'))
		{
			$csrf = Rhymix\Framework\Session::createToken('');
		}

		return [
			'module_srl' => $module_srl,
			'mid' => $mid,
			'isChurchAdmin' => $is_admin,
			'isPublicWrite' => $is_public,
			'isPrayBoard' => $is_pray,
			'usePopup' => !$is_public && $is_admin,
			'canStandardWrite' => $is_public && $logged_info,
			'form' => $is_pray ? null : ($forms[$module_srl] ?? null),
			'csrf_token' => $csrf,
			'api_url' => getNotEncodedUrl('', 'module', 'church_write', 'act', 'procChurchWriteInsertDocument'),
		];
	}

	public static function buildYoutubeEmbed(string $url): string
	{
		$url = trim($url);
		if (!$url)
		{
			return '';
		}
		$id = '';
		if (preg_match('~youtu\.be/([\w-]+)~i', $url, $m))
		{
			$id = $m[1];
		}
		elseif (preg_match('~[?&]v=([\w-]+)~i', $url, $m))
		{
			$id = $m[1];
		}
		elseif (preg_match('~youtube\.com/embed/([\w-]+)~i', $url, $m))
		{
			$id = $m[1];
		}
		if (!$id)
		{
			return '';
		}
		return '<div class="broadcast-video"><iframe width="700" height="394" src="https://www.youtube.com/embed/'
			. htmlspecialchars($id, ENT_QUOTES, 'UTF-8')
			. '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
	}

	public static function buildVideoContent(array $args): string
	{
		$body = [];
		$speaker = trim($args['speaker'] ?? '');
		if ($speaker)
		{
			$body[] = '<p><strong>' . htmlspecialchars($speaker, ENT_QUOTES, 'UTF-8') . '</strong></p>';
		}

		$yt = self::buildYoutubeEmbed($args['youtube_url'] ?? '');
		if ($yt)
		{
			$body[] = $yt;
		}
		elseif (!empty($args['video_url']))
		{
			$vurl = htmlspecialchars(trim($args['video_url']), ENT_QUOTES, 'UTF-8');
			$body[] = '<div class="broadcast-video"><video controls width="700" src="' . $vurl . '"></video>'
				. '<p><a href="' . $vurl . '" target="_blank" rel="noopener">영상 새 창에서 보기</a></p></div>';
		}

		$summary = trim($args['summary'] ?? '');
		if ($summary)
		{
			$body[] = '<p>' . nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')) . '</p>';
		}

		return implode("\n", $body);
	}

	public static function buildJuboContent(int $document_srl, array $file_urls): string
	{
		$labels = [
			'news' => '교회소식',
			'front' => '앞면',
			'back' => '뒷면',
		];
		$prefix = 'jubo' . $document_srl;
		$nav = [];
		$sections = '';
		foreach ($labels as $kind => $label)
		{
			if (empty($file_urls[$kind]))
			{
				continue;
			}
			$anchor = $prefix . '-' . $kind;
			$url = htmlspecialchars($file_urls[$kind], ENT_QUOTES, 'UTF-8');
			$nav[] = '<a href="#' . $anchor . '">' . $label . '</a>';
			$sections .= '<div id="' . $anchor . '" style="text-align:center;margin:20px 0;">'
				. '<p style="font-weight:bold;margin-bottom:8px;">' . $label . '</p>'
				. '<img src="' . $url . '" width="700" style="max-width:100%;height:auto;" alt="' . $label . '">'
				. '</div>';
		}
		if (!$sections)
		{
			return '<p>주보 이미지가 없습니다.</p>';
		}
		return '<div class="jubo-wrap"><p class="jubo-nav" style="text-align:center;margin:12px 0 20px;">'
			. '<strong>주보 보기:</strong> ' . implode(' | ', $nav) . '</p>' . $sections . '</div>';
	}

	public static function buildPictureContent(array $image_urls, string $summary = ''): string
	{
		$parts = [];
		foreach ($image_urls as $url)
		{
			$u = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
			$parts[] = '<p style="text-align:center"><img src="' . $u . '" width="675" style="max-width:100%;height:auto;" alt=""></p>';
		}
		if ($summary)
		{
			$parts[] = '<p>' . nl2br(htmlspecialchars($summary, ENT_QUOTES, 'UTF-8')) . '</p>';
		}
		return implode("\n", $parts) ?: '<p></p>';
	}

	public static function buildNewfaceContent(array $args): string
	{
		$lines = [];
		foreach (['zone' => '구역', 'shepherd' => '목자', 'org' => '기관'] as $key => $label)
		{
			$val = trim($args[$key] ?? '');
			if ($val)
			{
				$lines[] = '<p><strong>' . $label . '</strong> ' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '</p>';
			}
		}
		$reg = trim($args['reg_date'] ?? '');
		if ($reg)
		{
			$lines[] = '<p><strong>등록일</strong> ' . htmlspecialchars($reg, ENT_QUOTES, 'UTF-8') . '</p>';
		}
		$intro = trim($args['intro'] ?? '');
		if ($intro)
		{
			$lines[] = '<p>' . nl2br(htmlspecialchars($intro, ENT_QUOTES, 'UTF-8')) . '</p>';
		}
		return implode("\n", $lines) ?: '<p></p>';
	}

	public static function regdateFromDate(string $date): string
	{
		$date = preg_replace('/\D/', '', $date);
		if (strlen($date) >= 8)
		{
			return substr($date, 0, 8) . '120000';
		}
		return date('YmdHis');
	}

	function dispChurchWriteFormConfig()
	{
		$module_srl = (int)Context::get('module_srl');
		$logged_info = Context::get('logged_info');
		$config = self::getClientConfig($module_srl, $logged_info);
		if (!$config)
		{
			return new BaseObject(-1, 'invalid module');
		}
		$this->add('config', $config);
	}
}
