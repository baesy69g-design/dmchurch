<?php
/**
 * 해외선교 — 국내선교와 동일 패턴 (선교사 이름 필드 추가)
 */
trait dmcadminOverseasMissionTrait
{
	public static function getOverseasMissionCategories(): array
	{
		$cats = self::uiLabels()['overseas_mission']['categories'] ?? null;
		return is_array($cats) && $cats ? $cats : self::OVERSEAS_MISSION_CATEGORIES;
	}

	public static function isOverseasMissionListMid(string $mid): bool
	{
		return trim($mid) === self::OVERSEAS_MISSION_LIST_MID;
	}

	public static function isOverseasMissionSubMid(string $mid): bool
	{
		$mid = trim($mid);
		if ($mid === '' || !preg_match('/^p\d+$/', $mid))
		{
			return false;
		}
		$data = self::getOverseasMissionData();
		foreach ((array)($data['items'] ?? []) as $item)
		{
			if (!empty($item['has_sub']) && ($item['sub_mid'] ?? '') === $mid)
			{
				return true;
			}
		}
		return false;
	}

	public static function getOverseasMissionFilePath(): string
	{
		return \RX_BASEDIR . 'files/church/overseas_mission.json';
	}

	public static function getOverseasMissionUploadDir(string $scope = 'p26'): string
	{
		$safe = preg_replace('/[^a-z0-9_]/i', '', $scope);
		return \RX_BASEDIR . 'files/church/overseas/' . $safe;
	}

	/** @return array{page_title:string,next_sub_frame:int,items:array<int,array<string,mixed>>} */
	public static function getOverseasMissionData(): array
	{
		$path = self::getOverseasMissionFilePath();
		if (!is_file($path))
		{
			return [
				'page_title' => '해외선교',
				'next_sub_frame' => self::OVERSEAS_MISSION_SUB_FRAME_START,
				'items' => [],
			];
		}
		$decoded = json_decode(file_get_contents($path) ?: '', true);
		if (!is_array($decoded))
		{
			return [
				'page_title' => '해외선교',
				'next_sub_frame' => self::OVERSEAS_MISSION_SUB_FRAME_START,
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
			$items[] = self::normalizeOverseasMissionItem($item);
		}
		return [
			'page_title' => trim((string)($decoded['page_title'] ?? '해외선교')),
			'next_sub_frame' => max(self::OVERSEAS_MISSION_SUB_FRAME_START, (int)($decoded['next_sub_frame'] ?? self::OVERSEAS_MISSION_SUB_FRAME_START)),
			'items' => $items,
		];
	}

	public static function saveOverseasMissionData(array $data): bool
	{
		$path = self::getOverseasMissionFilePath();
		FileHandler::makeDir(dirname($path));
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		if ($json === false || file_put_contents($path, $json) === false)
		{
			return false;
		}
		self::fixDomesticMissionFilePermissions($path);
		return true;
	}

	public static function normalizeOverseasCategory(string $cat): string
	{
		$cat = trim($cat);
		return array_key_exists($cat, self::getOverseasMissionCategories()) ? $cat : 'support';
	}

	/** @param array<string,mixed> $item */
	public static function getOverseasMissionItemLabel(array $item): string
	{
		$missionary = trim((string)($item['missionary_name'] ?? ''));
		$name = trim((string)($item['name'] ?? ''));
		return $missionary !== '' ? $missionary : $name;
	}

	/** 국가명 → ISO 3166-1 alpha-2 (국기 이미지용). Windows 이모지 국기는 영문으로 깨지므로 이미지 사용 */
	public static function getOverseasMissionCountryCode(string $country): string
	{
		$country = trim($country);
		if ($country === '')
		{
			return '';
		}
		static $map = [
			'일본' => 'jp',
			'필리핀' => 'ph',
			'캄보디아' => 'kh',
			'대만' => 'tw',
			'태국' => 'th',
			'튀르키예' => 'tr',
			'터키' => 'tr',
			'파푸아뉴기니' => 'pg',
			'중국' => 'cn',
			'홍콩' => 'hk',
			'몽골' => 'mn',
			'베트남' => 'vn',
			'라오스' => 'la',
			'미얀마' => 'mm',
			'말레이시아' => 'my',
			'싱가포르' => 'sg',
			'인도네시아' => 'id',
			'인도' => 'in',
			'네팔' => 'np',
			'방글라데시' => 'bd',
			'스리랑카' => 'lk',
			'파키스탄' => 'pk',
			'아프가니스탄' => 'af',
			'우즈베키스탄' => 'uz',
			'카자흐스탄' => 'kz',
			'키르기스스탄' => 'kg',
			'러시아' => 'ru',
			'우크라이나' => 'ua',
			'미국' => 'us',
			'캐나다' => 'ca',
			'브라질' => 'br',
			'아르헨티나' => 'ar',
			'페루' => 'pe',
			'칠레' => 'cl',
			'멕시코' => 'mx',
			'독일' => 'de',
			'프랑스' => 'fr',
			'영국' => 'gb',
			'이탈리아' => 'it',
			'스페인' => 'es',
			'호주' => 'au',
			'뉴질랜드' => 'nz',
			'남아프리카공화국' => 'za',
			'케냐' => 'ke',
			'에티오피아' => 'et',
			'이집트' => 'eg',
			'이스라엘' => 'il',
			'요르단' => 'jo',
			'레바논' => 'lb',
			'시리아' => 'sy',
			'이라크' => 'iq',
			'이란' => 'ir',
			'북한' => 'kp',
			'한국' => 'kr',
			'대한민국' => 'kr',
		];
		if (isset($map[$country]))
		{
			return $map[$country];
		}
		foreach ($map as $name => $code)
		{
			if (function_exists('mb_strpos'))
			{
				if (mb_strpos($country, $name) !== false || mb_strpos($name, $country) !== false)
				{
					return $code;
				}
			}
			elseif (strpos($country, $name) !== false || strpos($name, $country) !== false)
			{
				return $code;
			}
		}
		return '';
	}

	public static function getOverseasMissionFlagUrl(string $country): string
	{
		$code = self::getOverseasMissionCountryCode($country);
		if ($code === '')
		{
			return '';
		}
		$local = \RX_BASEDIR . 'files/church/flags/' . $code . '.svg';
		if (is_file($local))
		{
			return '/files/church/flags/' . $code . '.svg';
		}
		return 'https://flagcdn.com/' . $code . '.svg';
	}

	/** @param array<string,mixed> $item */
	public static function isOverseasMissionItemEmpty(array $item): bool
	{
		$country = trim((string)($item['country'] ?? ''));
		if ($country === '')
		{
			return true;
		}
		return self::getOverseasMissionItemLabel($item) === '';
	}

	/** @param array<string,mixed> $item */
	public static function normalizeOverseasMissionItem(array $item): array
	{
		return [
			'id' => trim((string)($item['id'] ?? '')),
			'category' => self::normalizeOverseasCategory((string)($item['category'] ?? '')),
			'country' => trim((string)($item['country'] ?? '')),
			'name' => trim((string)($item['name'] ?? '')),
			'missionary_name' => trim((string)($item['missionary_name'] ?? '')),
			'thumb' => self::normalizeGuidePhotoUrl((string)($item['thumb'] ?? '')),
			'has_sub' => !empty($item['has_sub']),
			'sub_mid' => trim((string)($item['sub_mid'] ?? '')),
			'sub_photo' => self::normalizeGuidePhotoUrl((string)($item['sub_photo'] ?? '')),
			'sub_body' => trim((string)($item['sub_body'] ?? '')),
			'sub_gallery' => self::normalizeOverseasMissionGallery($item['sub_gallery'] ?? []),
			'order' => (int)($item['order'] ?? 0),
		];
	}

	/** @param mixed $raw @return list<string> */
	public static function normalizeOverseasMissionGallery($raw): array
	{
		if (!is_array($raw))
		{
			return [];
		}
		$out = [];
		$max = self::OVERSEAS_MISSION_GALLERY_MAX;
		foreach ($raw as $url)
		{
			$u = self::normalizeGuidePhotoUrl((string)$url);
			if ($u === '')
			{
				continue;
			}
			$out[] = $u;
			if (count($out) >= $max)
			{
				break;
			}
		}
		return $out;
	}

	public static function getOverseasMissionSubTitle(string $mid): ?string
	{
		$mid = trim($mid);
		foreach (self::getOverseasMissionData()['items'] as $item)
		{
			if (!empty($item['has_sub']) && ($item['sub_mid'] ?? '') === $mid)
			{
				return self::getOverseasMissionItemLabel($item);
			}
		}
		return null;
	}

	public static function getOverseasMissionItemBySubMid(string $sub_mid): ?array
	{
		$sub_mid = trim($sub_mid);
		foreach (self::getOverseasMissionData()['items'] as $item)
		{
			if (!empty($item['has_sub']) && ($item['sub_mid'] ?? '') === $sub_mid)
			{
				return $item;
			}
		}
		return null;
	}

	public static function allocOverseasMissionSubMid(array &$data): string
	{
		$frame = max(self::OVERSEAS_MISSION_SUB_FRAME_START, (int)($data['next_sub_frame'] ?? self::OVERSEAS_MISSION_SUB_FRAME_START));
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

	public static function ensureOverseasMissionSubPage(string $mid, string $title): BaseObject
	{
		return self::ensureDomesticMissionSubPage($mid, $title);
	}

	public static function syncOverseasMissionMenus(array $items): BaseObject
	{
		$oDB = Rhymix\Framework\DB::getInstance();
		$L = self::uiLabels();
		$mission_name = (string)($L['sub_top_menus']['mission'] ?? '선교와 봉사');
		$overseas_name = (string)($L['overseas_mission']['page_title'] ?? '해외선교');

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

		$overseas_row = $oDB->query(
			'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND parent_srl = ? AND url = ? ORDER BY menu_item_srl ASC LIMIT 1',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$mission_grp_srl,
			self::OVERSEAS_MISSION_LIST_MID
		)->fetch(\PDO::FETCH_OBJ);
		if (!$overseas_row || empty($overseas_row->menu_item_srl))
		{
			$overseas_row = $oDB->query(
				'SELECT menu_item_srl FROM menu_item WHERE menu_srl = ? AND url = ? ORDER BY menu_item_srl ASC LIMIT 1',
				self::DOMESTIC_MISSION_MAIN_MENU_SRL,
				self::OVERSEAS_MISSION_LIST_MID
			)->fetch(\PDO::FETCH_OBJ);
		}
		if (!$overseas_row || empty($overseas_row->menu_item_srl))
		{
			$overseas_srl = getNextSequence();
			$oDB->query(
				'INSERT INTO menu_item (menu_item_srl, parent_srl, menu_srl, name, url, is_shortcut, open_window, expand, listorder, regdate) VALUES (?,?,?,?,?,?,?,?,?,?)',
				$overseas_srl,
				$mission_grp_srl,
				self::DOMESTIC_MISSION_MAIN_MENU_SRL,
				$overseas_name,
				self::OVERSEAS_MISSION_LIST_MID,
				'N',
				'N',
				'N',
				-99990,
				date('YmdHis')
			);
		}
		else
		{
			$overseas_srl = (int)$overseas_row->menu_item_srl;
		}

		$oDB->query(
			'UPDATE menu_item SET name = ?, listorder = ? WHERE menu_item_srl = ?',
			$overseas_name,
			-99990,
			$overseas_srl
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
			$name = self::getOverseasMissionItemLabel($item);
			if ($sub_mid === '' || $name === '')
			{
				continue;
			}
			$wanted[$sub_mid] = ['name' => $name, 'order' => $order++];
		}

		$existing = $oDB->query(
			'SELECT menu_item_srl, url FROM menu_item WHERE menu_srl = ? AND parent_srl = ?',
			self::DOMESTIC_MISSION_MAIN_MENU_SRL,
			$overseas_srl
		)->fetchAll(\PDO::FETCH_OBJ);

		foreach ($existing as $row)
		{
			$url = trim((string)$row->url);
			if ($url === '' || isset($wanted[$url]))
			{
				continue;
			}
			if (preg_match('/^p\d+$/', $url))
			{
				$oDB->query('DELETE FROM menu_item WHERE menu_item_srl = ?', (int)$row->menu_item_srl);
			}
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
					$overseas_srl,
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
					$overseas_srl,
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

	public static function renderOverseasMissionList(array $data): string
	{
		$groups = [];
		foreach (self::getOverseasMissionCategories() as $key => $label)
		{
			$groups[$key] = [];
		}
		$decorated = [];
		foreach ((array)($data['items'] ?? []) as $pos => $item)
		{
			if (self::isOverseasMissionItemEmpty($item))
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
			$cat = self::normalizeOverseasCategory((string)($item['category'] ?? ''));
			$groups[$cat][] = $item;
		}
		foreach ($groups as $cat => &$list)
		{
			usort($list, function ($a, $b) use ($cat) {
				if ($cat === 'support')
				{
					$a_sub = !empty($a['has_sub']) ? 0 : 1;
					$b_sub = !empty($b['has_sub']) ? 0 : 1;
					if ($a_sub !== $b_sub)
					{
						return $a_sub <=> $b_sub;
					}
				}
				$a_ord = (int)($a['order'] ?? 0);
				$b_ord = (int)($b['order'] ?? 0);
				$a_ord = $a_ord > 0 ? $a_ord : PHP_INT_MAX;
				$b_ord = $b_ord > 0 ? $b_ord : PHP_INT_MAX;
				return $a_ord <=> $b_ord;
			});
		}
		unset($list);

		$html = '<div class="church-domestic-mission church-overseas-mission"><div class="church-dm-columns">';
		foreach (self::getOverseasMissionCategories() as $key => $label)
		{
			$list = $groups[$key];
			if (!$list)
			{
				continue;
			}
			$count = count($list);
			$col_class = 'church-dm-col church-dm-col--' . $key;
			if ($key === 'dispatch')
			{
				$col_class .= ' church-dm-col-dispatch';
			}
			$html .= '<section class="' . htmlspecialchars($col_class, ENT_QUOTES, 'UTF-8') . '">';
			$html .= '<header class="church-dm-head">';
			$html .= '<p class="church-dm-kicker">Overseas Mission</p>';
			$html .= '<h2 class="church-dm-heading">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h2>';
			$html .= '<p class="church-dm-count"><em>' . $count . '</em><span>교회</span></p>';
			$html .= '</header>';
			$list_class = 'church-dm-list church-dm-list--' . $key;
			if ($key === 'dispatch')
			{
				$list_class .= ' church-dm-list--featured';
			}
			$html .= '<ul class="' . htmlspecialchars($list_class, ENT_QUOTES, 'UTF-8') . '">';
			foreach ($list as $item)
			{
				$html .= self::renderOverseasMissionListItem($item, $key === 'dispatch');
			}
			$html .= '</ul></section>';
		}
		$html .= '</div></div>';
		return $html;
	}

	/** @param array<string,mixed> $item */
	protected static function renderOverseasMissionListItem(array $item, bool $featured = false): string
	{
		$name = trim((string)($item['name'] ?? ''));
		$missionary = trim((string)($item['missionary_name'] ?? ''));
		$country = trim((string)($item['country'] ?? ''));
		$label = self::getOverseasMissionItemLabel($item);
		$has_sub = !empty($item['has_sub']) && trim((string)($item['sub_mid'] ?? '')) !== '';
		$sub_mid = trim((string)($item['sub_mid'] ?? ''));
		$img = '';
		if ($has_sub)
		{
			$img = trim((string)($item['sub_photo'] ?? ''));
		}
		else
		{
			$img = trim((string)($item['thumb'] ?? ''));
		}
		$flag_url = self::getOverseasMissionFlagUrl($country);

		$item_class = 'church-dm-item' . ($featured ? ' church-dm-item--featured' : '');
		$html = '<li class="' . $item_class . '">';
		if ($img !== '')
		{
			$html .= '<figure class="church-dm-item-photo"><img src="' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" loading="lazy" /></figure>';
		}
		elseif ($flag_url !== '')
		{
			$html .= '<span class="church-dm-flag" title="' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '"><img src="' . htmlspecialchars($flag_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '" loading="lazy" width="40" height="30" /></span>';
		}
		$html .= '<div class="church-dm-item-body">';
		if ($country !== '')
		{
			$html .= '<span class="church-dm-country">' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '</span>';
		}
		if ($has_sub)
		{
			$url = htmlspecialchars(getNotEncodedUrl('', 'mid', $sub_mid), ENT_QUOTES, 'UTF-8');
			$html .= '<strong class="church-dm-item-name"><a href="' . $url . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></strong>';
		}
		else
		{
			$html .= '<strong class="church-dm-item-name">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong>';
		}
		if ($missionary !== '' && $name !== '' && $name !== $missionary)
		{
			$html .= '<span class="church-dm-missionary">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span>';
		}
		elseif ($missionary !== '' && $label !== $missionary)
		{
			$html .= '<span class="church-dm-missionary">' . htmlspecialchars($missionary, ENT_QUOTES, 'UTF-8') . '</span>';
		}
		$html .= '</div></li>';
		return $html;
	}

	/** @param array<string,mixed> $item */
	public static function renderOverseasMissionSub(array $item): string
	{
		$name = trim((string)($item['name'] ?? ''));
		$missionary = trim((string)($item['missionary_name'] ?? ''));
		$country = trim((string)($item['country'] ?? ''));
		$label = self::getOverseasMissionItemLabel($item);
		$photo = trim((string)($item['sub_photo'] ?? ''));
		if ($photo === '')
		{
			$photo = trim((string)($item['thumb'] ?? ''));
		}
		$body = trim((string)($item['sub_body'] ?? ''));
		$gallery = self::normalizeOverseasMissionGallery($item['sub_gallery'] ?? []);
		$sub_mid = trim((string)($item['sub_mid'] ?? ''));
		$frame = self::getOverseasMissionFrameStyle($sub_mid);

		$html = '<div class="church-mission-detail church-mission-detail--frame-' . $frame
			. ($gallery ? ' church-mission-detail--with-gallery' : '') . '">';
		if ($photo !== '')
		{
			$html .= '<figure class="church-mission-detail-photo church-mission-frame church-mission-frame--' . $frame . '">';
			$html .= '<span class="church-mission-frame-mat" aria-hidden="true"></span>';
			$html .= '<img src="' . htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" loading="lazy" />';
			$html .= '</figure>';
		}
		$html .= '<div class="church-mission-detail-compose">';
		if ($gallery)
		{
			$html .= '<div class="church-mission-gallery" aria-label="추가 사진">';
			foreach ($gallery as $gi => $gurl)
			{
				$n = $gi + 1;
				$html .= '<figure class="church-mission-gal-item church-mission-gal-item--' . $n . '">';
				$html .= '<img src="' . htmlspecialchars($gurl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($label . ' 사진 ' . $n, ENT_QUOTES, 'UTF-8') . '" loading="lazy" />';
				$html .= '</figure>';
			}
			$html .= '</div>';
		}
		$html .= '<div class="church-mission-detail-body">';
		$flag_url = self::getOverseasMissionFlagUrl($country);
		if ($flag_url !== '')
		{
			$html .= '<div class="church-mission-detail-flag"><img src="' . htmlspecialchars($flag_url, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '" width="48" height="36" loading="lazy" /></div>';
		}
		if ($country !== '')
		{
			$html .= '<p class="church-mission-detail-country"><span class="church-mission-meta-label">국가</span><span class="church-mission-meta-value">' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '</span></p>';
		}
		if ($missionary !== '')
		{
			$html .= '<p class="church-mission-detail-missionary"><span class="church-mission-meta-label">선교사</span><span class="church-mission-meta-value">' . htmlspecialchars($missionary, ENT_QUOTES, 'UTF-8') . '</span></p>';
		}
		if ($name !== '' && $name !== $missionary)
		{
			$html .= '<p class="church-mission-detail-place"><span class="church-mission-meta-label">선교지</span><span class="church-mission-meta-value">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</span></p>';
		}
		if ($body !== '')
		{
			$html .= '<div class="church-mission-greeting">';
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
					$html .= '<p class="church-mission-greeting-line"><a href="' . $safe . '" target="_blank" rel="noopener">' . $safe . '</a></p>';
				}
				else
				{
					$html .= '<p class="church-mission-greeting-line">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
				}
			}
			$html .= '</div>';
		}
		$html .= '</div></div></div>';
		return $html;
	}

	/** @return int 1..6 */
	public static function getOverseasMissionFrameStyle(string $sub_mid): int
	{
		$sub_mid = strtolower(trim($sub_mid));
		$map = [
			'p264' => 1,
			'p261' => 2,
			'p262' => 3,
			'p263' => 4,
			'p266' => 5,
			'p265' => 6,
		];
		if (isset($map[$sub_mid]))
		{
			return $map[$sub_mid];
		}
		if ($sub_mid === '')
		{
			return 1;
		}
		return (abs(crc32($sub_mid)) % 6) + 1;
	}

	public static function publishOverseasMissionAll(): BaseObject
	{
		$data = self::getOverseasMissionData();
		$list_srl = self::getPageModuleSrl(self::OVERSEAS_MISSION_LIST_MID);
		if ($list_srl < 1)
		{
			return new BaseObject(-1, '해외선교 페이지(p26)를 찾을 수 없습니다.');
		}

		$list_html = self::renderOverseasMissionList($data);
		$output = self::updatePageModuleContent($list_srl, $list_html);
		if (!$output->toBool())
		{
			return $output;
		}
		self::clearPageModuleCache($list_srl, self::OVERSEAS_MISSION_LIST_MID);

		foreach ((array)($data['items'] ?? []) as $item)
		{
			if (empty($item['has_sub']))
			{
				continue;
			}
			$sub_mid = trim((string)($item['sub_mid'] ?? ''));
			$title = self::getOverseasMissionItemLabel($item);
			if ($sub_mid === '' || $title === '')
			{
				continue;
			}
			$ensure = self::ensureOverseasMissionSubPage($sub_mid, $title);
			if (!$ensure->toBool())
			{
				return $ensure;
			}
			$sub_srl = self::getPageModuleSrl($sub_mid);
			if ($sub_srl < 1)
			{
				return new BaseObject(-1, '상세 페이지(' . $sub_mid . ')를 찾을 수 없습니다.');
			}
			$sub_html = self::renderOverseasMissionSub($item);
			$output = self::updatePageModuleContent($sub_srl, $sub_html);
			if (!$output->toBool())
			{
				return $output;
			}
			self::clearPageModuleCache($sub_srl, $sub_mid);
		}

		$menu_out = self::syncOverseasMissionMenus((array)($data['items'] ?? []));
		if (!$menu_out->toBool())
		{
			return $menu_out;
		}

		return new BaseObject();
	}

	public static function saveOverseasMissionListData(array $data): BaseObject
	{
		$current = self::getOverseasMissionData();
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
			$norm = self::normalizeOverseasMissionItem($item);
			if (self::isOverseasMissionItemEmpty($norm))
			{
				continue;
			}
			if ($norm['id'] === '')
			{
				$norm['id'] = 'om_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 10);
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
				$norm['sub_mid'] = self::allocOverseasMissionSubMid($current);
			}
			if (!$norm['has_sub'])
			{
				$norm['sub_mid'] = '';
				$norm['sub_photo'] = '';
				$norm['sub_body'] = '';
				$norm['sub_gallery'] = [];
			}
			else
			{
				$norm['thumb'] = '';
			}
			$items[] = $norm;
		}
		$payload = [
			'page_title' => trim((string)($data['page_title'] ?? '해외선교')),
			'next_sub_frame' => (int)($current['next_sub_frame'] ?? self::OVERSEAS_MISSION_SUB_FRAME_START),
			'items' => $items,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::saveOverseasMissionData($payload))
		{
			$path = self::getOverseasMissionFilePath();
			if (is_file($path) && !is_writable($path))
			{
				return new BaseObject(-1, '해외선교 데이터 파일에 쓸 수 없습니다. files/church/overseas_mission.json 권한을 확인하세요.');
			}
			return new BaseObject(-1, '해외선교 데이터를 저장하지 못했습니다.');
		}
		return self::publishOverseasMissionAll();
	}

	public static function getOverseasMissionListForEdit(): object
	{
		$data = self::getOverseasMissionData();
		$o = new stdClass;
		$o->mid = self::OVERSEAS_MISSION_LIST_MID;
		$o->label = '해외선교';
		$o->page_title = $data['page_title'];
		$o->items = $data['items'];
		$o->categories = self::getOverseasMissionCategories();
		$o->view_url = getNotEncodedUrl('', 'mid', self::OVERSEAS_MISSION_LIST_MID);
		return $o;
	}

	public static function getOverseasMissionSubOptions(): array
	{
		$out = [];
		foreach (self::getOverseasMissionData()['items'] as $item)
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
			$out[] = $o;
		}
		return $out;
	}
}
