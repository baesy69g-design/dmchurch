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
			'order' => (int)($item['order'] ?? 0),
		];
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
			$col_class = 'church-dm-col';
			if ($key === 'dispatch')
			{
				$col_class .= ' church-dm-col-dispatch';
			}
			$html .= '<section class="' . $col_class . '">';
			$html .= '<h2 class="church-dm-heading">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</h2>';
			$html .= '<ul class="church-dm-list">';
			foreach ($list as $item)
			{
				$html .= self::renderOverseasMissionListItem($item);
			}
			$html .= '</ul></section>';
		}
		$html .= '</div></div>';
		return $html;
	}

	/** @param array<string,mixed> $item */
	protected static function renderOverseasMissionListItem(array $item): string
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

		$html = '<li class="church-dm-item">';
		if ($img !== '')
		{
			$html .= '<figure class="church-dm-item-photo"><img src="' . htmlspecialchars($img, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" loading="lazy" /></figure>';
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

		$html = '<div class="church-mission-detail">';
		if ($photo !== '')
		{
			$html .= '<figure class="church-mission-detail-photo"><img src="' . htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" loading="lazy" /></figure>';
		}
		$html .= '<div class="church-mission-detail-body">';
		if ($country !== '')
		{
			$html .= '<p class="church-mission-detail-country"><strong>국가</strong> ' . htmlspecialchars($country, ENT_QUOTES, 'UTF-8') . '</p>';
		}
		if ($missionary !== '')
		{
			$html .= '<p class="church-mission-detail-missionary"><strong>선교사</strong> ' . htmlspecialchars($missionary, ENT_QUOTES, 'UTF-8') . '</p>';
		}
		if ($body !== '')
		{
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
		}
		$html .= '</div></div>';
		return $html;
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
