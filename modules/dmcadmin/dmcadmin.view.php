<?php
/**
 * @class  dmcadminView
 */
class dmcadminView extends dmcadmin
{
	protected function initLayout(string $template, string $title = ''): void
	{
		Context::addBodyClass('dmcadmin-page');
		Context::loadFile('./modules/dmcadmin/dmcadmin.css');
		Context::set('dmcadmin_title', $title);
		Context::set('csrf_token', Rhymix\Framework\Session::createToken(''));
		Context::set('dmcadmin_msg', Context::get('msg'));
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile($template);
	}

	public function dispDmcMgrLogin()
	{
		if (dmcadminModel::isAuthenticated())
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDashboard'));
			return;
		}
		Context::addBodyClass('dmcadmin-page');
		Context::loadFile('./modules/dmcadmin/dmcadmin.css');
		Context::set('csrf_token', Rhymix\Framework\Session::createToken(''));
		Context::set('dmcadmin_msg', Context::get('msg'));
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('login');
	}

	public function dispDmcMgrIndex()
	{
		if (dmcadminModel::isAuthenticated())
		{
			return $this->dispDmcMgrDashboard();
		}
		return $this->dispDmcMgrLogin();
	}

	public function dispDmcMgrDashboard()
	{
		dmcadminModel::requireAuth();
		$config = dmcadminModel::getChurchConfig();
		$members = dmcadminModel::getMemberList('');
		Context::set('member_count', count($members));
		Context::set('prayer_notify_email', $config->prayer_notify_email);
		Context::set('prayer_reader_count', count((array)$config->prayer_reader_srls));
		$slides = dmcadminModel::getMainSlideUrls();
		Context::set('main_slide_set_count', count(array_filter($slides)));
		$sub_tops = dmcadminModel::getSubTopBannerUrls();
		Context::set('sub_top_set_count', count(array_filter($sub_tops)));
		$tiles = dmcadminModel::getMainTileData();
		Context::set('main_tile_set_count', count(array_filter(array_column($tiles, 'image_url'))));
		$this->initLayout('dashboard', 'dmcadmin');
	}

	public function dispDmcMgrSettings()
	{
		dmcadminModel::requireAuth();
		$config = dmcadminModel::getChurchConfig();
		$reader_ids = ['', ''];
		$i = 0;
		foreach ((array)$config->prayer_reader_srls as $srl)
		{
			$m = MemberModel::getMemberInfoByMemberSrl((int)$srl);
			if ($m)
			{
				$reader_ids[$i++] = $m->user_id;
			}
			if ($i >= dmcadminModel::MAX_PRAYER_READERS)
			{
				break;
			}
		}
		Context::set('prayer_notify_email', $config->prayer_notify_email);
		Context::set('prayer_reader_1', $reader_ids[0]);
		Context::set('prayer_reader_2', $reader_ids[1]);
		$this->initLayout('settings', '설정');
	}

	public function dispDmcMgrMembers()
	{
		dmcadminModel::requireAuth();
		$search = (string)Context::get('search');
		Context::set('member_list', dmcadminModel::getMemberList($search));
		Context::set('search', $search);
		Context::set('can_view_secrets', dmcadminModel::canViewMemberSecrets());
		$this->initLayout('members', '회원 관리');
	}

	public function dispDmcMgrMemberForm()
	{
		dmcadminModel::requireAuth();
		$member_srl = (int)Context::get('member_srl');
		$form = dmcadminModel::getMemberFormData($member_srl);
		if (!$form)
		{
			return new BaseObject(-1, '회원을 찾을 수 없습니다.');
		}
		Context::set('form', $form);
		Context::set('can_view_secrets', dmcadminModel::canViewMemberSecrets());
		$this->initLayout('member_form', $member_srl ? '회원 수정' : '회원 추가');
	}

	public function dispDmcMgrMainSlides()
	{
		dmcadminModel::requireAuth();
		$slides = dmcadminModel::getMainSlideUrls();
		$slots = [];
		for ($i = 0; $i < dmcadminModel::MAIN_SLIDE_COUNT; $i++)
		{
			$o = new stdClass;
			$o->num = $i + 1;
			$o->url = $slides[$i] ?? '';
			$slots[] = $o;
		}
		Context::set('slide_slots', $slots);
		$this->initLayout('main_slides', '메인 대표사진');
	}

	public function dispDmcMgrSubTops()
	{
		dmcadminModel::requireAuth();
		$urls = dmcadminModel::getSubTopBannerUrls();
		[$banner_w, $banner_h] = dmcadminModel::getSubTopBannerSize();
		$items = [];
		foreach (dmcadminModel::SUB_TOP_MENUS as $key => $meta)
		{
			$o = new stdClass;
			$o->key = $key;
			$o->label = dmcadminModel::getSubTopMenuLabel($key);
			$o->url = $urls[$key] ?? '';
			$items[] = $o;
		}
		Context::set('sub_top_items', $items);
		Context::set('sub_top_width', $banner_w);
		Context::set('sub_top_height', $banner_h);
		$this->initLayout('sub_tops', '서브페이지 TOP 사진 생성 및 등록');
	}

	public function dispDmcMgrMainTiles()
	{
		dmcadminModel::requireAuth();
		$data = dmcadminModel::getMainTileData();
		$slots = [];
		foreach (dmcadminModel::MAIN_TILES as $key => $meta)
		{
			$row = $data[$key] ?? ['image_url' => '', 'link_url' => ''];
			$o = new stdClass;
			$o->key = $key;
			$o->label = dmcadminModel::getMainTileLabel($key);
			$o->image_url = $row['image_url'];
			$o->link_url = $row['link_url'];
			$o->default_link = dmcadminModel::buildMainTargetUrl($meta['target'], $meta['id']);
			$slots[] = $o;
		}
		Context::set('tile_slots', $slots);
		$this->initLayout('main_tiles', '메인 타일');
	}

	public function dispDmcMgrInfoPages()
	{
		dmcadminModel::requireAuth();
		Context::set('info_pages', dmcadminModel::getInfoPageList());
		$this->initLayout('info_pages', '정보페이지');
	}

	public function dispDmcMgrInfoPageEdit()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		$page = dmcadminModel::getGuidePageForEdit($mid);
		if (!$page)
		{
			return new BaseObject(-1, '페이지를 찾을 수 없습니다.');
		}

		$slots = [];
		foreach ($page->sections as $i => $section)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->subtitle = $section['subtitle'] ?? '';
			$o->summary = $section['summary'] ?? '';
			$o->body = $section['body'] ?? '';
			$slots[] = $o;
		}
		Context::set('section_slots', $slots);
		Context::set('section_count', count($slots));
		Context::set('page', $page);
		$this->initLayout('info_page_edit', $page->label);
	}

	public function dispDmcMgrHistoryPageEdit()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		$page = dmcadminModel::getHistoryPageForEdit($mid);
		if (!$page)
		{
			return new BaseObject(-1, '페이지를 찾을 수 없습니다.');
		}

		$slots = [];
		foreach ($page->blocks as $i => $block)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->era = $block['era'] ?? '';
			$o->photo = $block['photo'] ?? '';
			$o->body = $block['body'] ?? '';
			$slots[] = $o;
		}
		Context::set('block_slots', $slots);
		Context::set('block_count', count($slots));
		Context::set('page', $page);
		$this->initLayout('history_page_edit', $page->label);
	}

	public function dispDmcMgrPeoplePageEdit()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		$page = dmcadminModel::getPeoplePageForEdit($mid);
		if (!$page)
		{
			return new BaseObject(-1, '페이지를 찾을 수 없습니다.');
		}

		$cat_seq = [];
		$slots = [];
		foreach ($page->people as $i => $person)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->category = $person['category'] ?? '';
			$o->name = $person['name'] ?? '';
			$o->title = $person['title'] ?? '';
			$o->photo = $person['photo'] ?? '';
			$o->memo = $person['memo'] ?? '';
			// 순서 기본값: 저장된 값이 있으면 사용, 없으면 구분 내 현재 순서(1,2,3...)
			$cat = $o->category;
			$cat_seq[$cat] = ($cat_seq[$cat] ?? 0) + 1;
			$stored = (int)($person['order'] ?? 0);
			$o->order = $stored > 0 ? $stored : $cat_seq[$cat];
			$slots[] = $o;
		}
		Context::set('person_slots', $slots);
		Context::set('person_count', count($slots));
		Context::set('categories', dmcadminModel::PEOPLE_CATEGORIES);
		Context::set('page', $page);
		$this->initLayout('people_page_edit', $page->label);
	}

	public function dispDmcMgrWorshipPageEdit()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		$page = dmcadminModel::getWorshipPageForEdit($mid);
		if (!$page)
		{
			return new BaseObject(-1, '페이지를 찾을 수 없습니다.');
		}

		$slots = [];
		foreach ($page->items as $i => $item)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->category = $item['category'] ?? '';
			$o->name = $item['name'] ?? '';
			$o->time = $item['time'] ?? '';
			$o->place = $item['place'] ?? '';
			$slots[] = $o;
		}
		Context::set('item_slots', $slots);
		Context::set('item_count', count($slots));
		Context::set('categories', dmcadminModel::WORSHIP_CATEGORIES);
		Context::set('page', $page);
		$this->initLayout('worship_page_edit', $page->label);
	}

	public function dispDmcMgrNewfamilyPageEdit()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		$page = dmcadminModel::getNewfamilyPageForEdit($mid);
		if (!$page)
		{
			return new BaseObject(-1, '페이지를 찾을 수 없습니다.');
		}

		$slots = [];
		foreach ($page->photos as $i => $photo)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->photo = $photo;
			$slots[] = $o;
		}
		Context::set('photo_slots', $slots);
		Context::set('page', $page);
		$this->initLayout('newfamily_page_edit', $page->label);
	}

	public function dispDmcMgrTourPageEdit()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		$page = dmcadminModel::getTourPageForEdit($mid);
		if (!$page)
		{
			return new BaseObject(-1, '페이지를 찾을 수 없습니다.');
		}

		$slots = [];
		foreach ($page->photos as $i => $photo)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->photo = $photo;
			$slots[] = $o;
		}
		if (!$slots)
		{
			$o = new stdClass;
			$o->index = 0;
			$o->num = 1;
			$o->photo = '';
			$slots[] = $o;
		}
		Context::set('photo_slots', $slots);
		Context::set('photo_count', count($slots));
		Context::set('tour_max', dmcadminModel::TOUR_PAGE_MAX);
		Context::set('page', $page);
		$this->initLayout('tour_page_edit', $page->label);
	}

	public function dispDmcMgrSchoolPageEdit()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		if (!dmcadminModel::isSchoolPage($mid))
		{
			$mid = (string)array_key_first(dmcadminModel::SCHOOL_PAGE_MIDS);
		}
		$page = dmcadminModel::getSchoolPageForEdit($mid);
		if (!$page)
		{
			return new BaseObject(-1, '페이지를 찾을 수 없습니다.');
		}

		$slots = [];
		foreach ($page->photos as $i => $photo)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->photo = $photo;
			$slots[] = $o;
		}
		Context::set('photo_slots', $slots);

		$departments = [];
		foreach (dmcadminModel::SCHOOL_PAGE_MIDS as $dmid => $dlabel)
		{
			$d = new stdClass;
			$d->mid = $dmid;
			$d->label = $dlabel;
			$d->selected = ($dmid === $mid);
			$d->edit_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrSchoolPageEdit', 'page_mid', $dmid);
			$departments[] = $d;
		}
		Context::set('departments', $departments);
		Context::set('page', $page);
		$this->initLayout('school_page_edit', '교회학교 — ' . $page->label);
	}

	public function dispDmcMgrDongkeydayPageEdit()
	{
		dmcadminModel::requireAuth();
		$page = dmcadminModel::getDongkeydayPageForEdit();
		if (!$page)
		{
			return new BaseObject(-1, '동키데이 페이지를 찾을 수 없습니다. setup_dongkeyday_page.php를 먼저 실행하세요.');
		}

		$slots = [];
		for ($i = 0; $i < dmcadminModel::DONGKEYDAY_PHOTO_COUNT; $i++)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->photo = (string)($page->photos[$i] ?? '');
			$slots[] = $o;
		}

		Context::set('photo_slots', $slots);
		Context::set('photo_max', dmcadminModel::DONGKEYDAY_PHOTO_COUNT);
		Context::set('page', $page);
		$this->initLayout('dongkeyday_page_edit', '동키데이');
	}

	public function dispDmcMgrDomesticMissionListEdit()
	{
		dmcadminModel::requireAuth();
		$page = dmcadminModel::getDomesticMissionListForEdit();
		$items = (array)$page->items;
		if (!$items)
		{
			$items[] = [
				'id' => '',
				'category' => 'church',
				'name' => '',
				'thumb' => '',
				'has_sub' => false,
				'sub_mid' => '',
				'order' => 0,
			];
		}
		$slots = [];
		foreach ($items as $i => $item)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->id = (string)($item['id'] ?? '');
			$o->category = (string)($item['category'] ?? 'church');
			$o->name = (string)($item['name'] ?? '');
			$o->thumb = (string)($item['thumb'] ?? '');
			$o->has_sub = !empty($item['has_sub']);
			$o->sub_mid = (string)($item['sub_mid'] ?? '');
			$o->sub_photo = (string)($item['sub_photo'] ?? '');
			$o->sub_body = (string)($item['sub_body'] ?? '');
			$o->order = (int)($item['order'] ?? 0);
			$slots[] = $o;
		}
		Context::set('page', $page);
		Context::set('item_slots', $slots);
		Context::set('item_count', count($slots));
		Context::set('categories', dmcadminModel::getDomesticMissionCategories());
		Context::set('sub_options', dmcadminModel::getDomesticMissionSubOptions());
		$this->initLayout('domestic_mission_list_edit', '국내선교 — 목록');
	}

	public function dispDmcMgrDomesticMissionSubEdit()
	{
		dmcadminModel::requireAuth();
		$sub_mid = (string)Context::get('sub_mid');
		$options = dmcadminModel::getDomesticMissionSubOptions();
		if ($sub_mid === '' && $options)
		{
			$sub_mid = (string)$options[0]->sub_mid;
		}
		$page = dmcadminModel::getDomesticMissionSubForEdit($sub_mid);
		if (!$page)
		{
			return new BaseObject(-1, '상세 페이지를 찾을 수 없습니다. 목록에서 「상세 페이지 있음」을 먼저 저장하세요.');
		}
		Context::set('page', $page);
		Context::set('sub_options', $options);
		$this->initLayout('domestic_mission_sub_edit', '국내선교 — ' . $page->name);
	}

	public function dispDmcMgrOverseasMissionListEdit()
	{
		dmcadminModel::requireAuth();
		$page = dmcadminModel::getOverseasMissionListForEdit();
		$items = (array)$page->items;
		if (!$items)
		{
			$items[] = [
				'id' => '',
				'category' => 'support',
				'country' => '',
				'name' => '',
				'missionary_name' => '',
				'thumb' => '',
				'has_sub' => false,
				'sub_mid' => '',
				'order' => 0,
			];
		}
		$slots = [];
		foreach ($items as $i => $item)
		{
			$o = new stdClass;
			$o->index = $i;
			$o->num = $i + 1;
			$o->id = (string)($item['id'] ?? '');
			$o->category = (string)($item['category'] ?? 'support');
			$o->country = (string)($item['country'] ?? '');
			$o->name = (string)($item['name'] ?? '');
			$o->missionary_name = (string)($item['missionary_name'] ?? '');
			$o->thumb = (string)($item['thumb'] ?? '');
			$o->has_sub = !empty($item['has_sub']);
			$o->sub_mid = (string)($item['sub_mid'] ?? '');
			$o->sub_photo = (string)($item['sub_photo'] ?? '');
			$o->sub_body = (string)($item['sub_body'] ?? '');
			$o->order = (int)($item['order'] ?? 0);
			$slots[] = $o;
		}
		Context::set('page', $page);
		Context::set('item_slots', $slots);
		Context::set('item_count', count($slots));
		Context::set('categories', dmcadminModel::getOverseasMissionCategories());
		Context::set('sub_options', dmcadminModel::getOverseasMissionSubOptions());
		$this->initLayout('overseas_mission_list_edit', '해외선교 — 목록');
	}
}
