<?php
/**
 * @class  dmcadminController
 */
class dmcadminController extends dmcadmin
{
	protected function redirectAfterProc(string $url): void
	{
		if (!headers_sent())
		{
			header('Location: ' . $url);
		}
		Context::close();
		exit;
	}

	public function triggerMemberDoLoginBefore($obj)
	{
		if (empty($obj->user_id) || empty($obj->password))
		{
			return new BaseObject();
		}

		$member = MemberModel::getMemberInfoByUserID($obj->user_id);
		if (!$member)
		{
			return new BaseObject();
		}

		if (MemberModel::isValidPassword($member->password, $obj->password, $member->member_srl))
		{
			return new BaseObject();
		}

		if (!dmcadminModel::verifyLegacyPassword($member, $obj->password))
		{
			return new BaseObject();
		}

		dmcadminModel::upgradePassword((int)$member->member_srl, $obj->password);
		return new BaseObject();
	}

	public function procDmcMgrLogin()
	{
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$user_id = (string)Context::get('user_id');
		$password = (string)Context::get('password');
		if (!dmcadminModel::verifyAdminCredentials($user_id, $password))
		{
			return new BaseObject(-1, '아이디 또는 비밀번호가 올바르지 않습니다.');
		}

		dmcadminModel::setAuthenticated(true);
		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDashboard'));
	}

	public function procDmcMgrLogout()
	{
		dmcadminModel::setAuthenticated(false);
		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrLogin'));
	}

	public function procDmcMgrChangePassword()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$current = (string)Context::get('current_password');
		$new = (string)Context::get('new_password');
		$confirm = (string)Context::get('confirm_password');

		if (!$current || !$new || !$confirm)
		{
			return new BaseObject(-1, '모든 비밀번호 항목을 입력해 주세요.');
		}
		if ($new !== $confirm)
		{
			return new BaseObject(-1, '새 비밀번호와 확인 비밀번호가 일치하지 않습니다.');
		}
		if (strlen($new) < 4)
		{
			return new BaseObject(-1, '새 비밀번호는 4자 이상이어야 합니다.');
		}

		$member = MemberModel::getMemberInfoByUserID(dmcadminModel::ADMIN_USER_ID);
		if (!$member)
		{
			return new BaseObject(-1, '관리자 계정을 찾을 수 없습니다.');
		}

		$valid = MemberModel::isValidPassword($member->password, $current, $member->member_srl)
			|| dmcadminModel::verifyLegacyPassword($member, $current);
		if (!$valid)
		{
			return new BaseObject(-1, '현재 비밀번호가 올바르지 않습니다.');
		}

		dmcadminModel::upgradePassword((int)$member->member_srl, $new);
		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrSettings', 'msg', 'password_changed'));
	}

	public function procDmcMgrSaveSettings()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$email = trim((string)Context::get('prayer_notify_email'));
		if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return new BaseObject(-1, '기도요청 수신 메일 형식이 올바르지 않습니다.');
		}

		$readers = [];
		for ($i = 1; $i <= dmcadminModel::MAX_PRAYER_READERS; $i++)
		{
			$uid = trim((string)Context::get('prayer_reader_' . $i));
			if (!$uid)
			{
				continue;
			}
			$m = MemberModel::getMemberInfoByUserID($uid);
			if (!$m)
			{
				return new BaseObject(-1, '존재하지 않는 조회 ID: ' . $uid);
			}
			if ($m->user_id === dmcadminModel::ADMIN_USER_ID)
			{
				return new BaseObject(-1, '동명지킴이(dmc2241)는 기도요청 조회자로 등록할 수 없습니다.');
			}
			$readers[] = (int)$m->member_srl;
		}

		$output = dmcadminModel::saveChurchConfig([
			'prayer_notify_email' => $email,
			'prayer_reader_srls' => $readers,
		]);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrSettings', 'msg', 'settings_saved'));
	}

	public function procDmcMgrSaveMember()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$member_srl = (int)Context::get('member_srl');
		$user_id = strtolower(trim((string)Context::get('user_id')));
		$password = (string)Context::get('password');
		$user_name = trim((string)Context::get('user_name'));
		$nick_name = trim((string)Context::get('nick_name'));
		$email_address = trim((string)Context::get('email_address'));
		$group_srl = (int)Context::get('group_srl');
		$denied = Context::get('denied') === 'Y' ? 'Y' : 'N';

		if (!$user_id || !$user_name)
		{
			return new BaseObject(-1, '아이디와 이름은 필수입니다.');
		}
		if (!in_array($group_srl, [2, 3, 4], true))
		{
			$group_srl = 3;
		}
		if (!$nick_name)
		{
			$nick_name = $user_name;
		}
		if (!$email_address)
		{
			$email_address = $user_id . '@dmchurch.local';
		}

		$oMemberController = MemberController::getInstance();

		if ($member_srl)
		{
			$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
			if (!$member)
			{
				return new BaseObject(-1, '회원을 찾을 수 없습니다.');
			}

			$extra = dmcadminModel::buildExtraVars(Context::getRequestVars(), dmcadminModel::getMemberExtra($member));
			$args = new stdClass;
			$args->member_srl = $member_srl;
			$args->user_id = $member->user_id;
			$args->user_name = $user_name;
			$args->nick_name = $nick_name;
			$args->email_address = $email_address;
			$args->phone_number = preg_replace('/\D/', '', (string)Context::get('phone_number'));
			$args->birthday = preg_replace('/\D/', '', (string)Context::get('birthday'));
			$args->denied = $denied;
			$args->status = $denied === 'Y' ? 'DENIED' : 'APPROVED';

			$output = executeQuery('member.updateMember', $args);
			if (!$output->toBool())
			{
				return $output;
			}
			MemberController::clearMemberCache($member_srl);
			getModel('church_member');
			church_memberModel::saveExtraBySrl($member_srl, $extra);

			if ($password)
			{
				dmcadminModel::upgradePassword($member_srl, $password);
				$extra->rankup_passwd = null;
				unset($extra->rankup_passwd);
				church_memberModel::saveExtraBySrl($member_srl, $extra);
			}

			$this->syncMemberGroup($member_srl, $group_srl);
			$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrMembers', 'msg', 'member_saved'));
			return;
		}

		if (!$password)
		{
			return new BaseObject(-1, '신규 회원은 비밀번호가 필요합니다.');
		}
		if (MemberModel::getMemberInfoByUserID($user_id))
		{
			return new BaseObject(-1, '이미 사용 중인 아이디입니다.');
		}

		$extra = dmcadminModel::buildExtraVars(Context::getRequestVars());
		$args = new stdClass;
		$args->user_id = $user_id;
		$args->password = $password;
		$args->user_name = $user_name;
		$args->nick_name = $nick_name;
		$args->email_address = $email_address;
		$args->phone_number = preg_replace('/\D/', '', (string)Context::get('phone_number'));
		$args->birthday = preg_replace('/\D/', '', (string)Context::get('birthday'));
		$args->denied = $denied;
		$args->status = $denied === 'Y' ? 'DENIED' : 'APPROVED';
		$args->extra_vars = serialize($extra);

		$output = $oMemberController->insertMember($args);
		if (!$output->toBool())
		{
			return $output;
		}

		$new_srl = (int)$output->get('member_srl');
		$this->syncMemberGroup($new_srl, $group_srl);
		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrMembers', 'msg', 'member_saved'));
	}

	public function procDmcMgrSaveMainSlides()
	{
		dmcadminModel::requireAuth();
		$return_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrMainSlides');
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			$this->redirectAfterProc($return_url . '&msg=' . rawurlencode('보안 토큰이 올바르지 않습니다.'));
		}

		$urls = dmcadminModel::getMainSlideUrls();

		for ($i = 1; $i <= dmcadminModel::MAIN_SLIDE_COUNT; $i++)
		{
			if (Context::get('remove_slide_' . $i) === 'Y')
			{
				$this->deleteMainSlideFile($urls[$i - 1] ?? '');
				$urls[$i - 1] = '';
				continue;
			}

			$field = 'slide_' . $i;
			if (empty($_FILES[$field]['name']))
			{
				continue;
			}
			$upload_err = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
			if ($upload_err !== UPLOAD_ERR_OK)
			{
				$this->redirectAfterProc($return_url . '&msg=' . rawurlencode('사진 ' . $i . ': ' . $this->describeUploadError($upload_err)));
			}

			try
			{
				$new_url = $this->uploadMainSlideFile($i, $_FILES[$field]);
			}
			catch (Rhymix\Framework\Exception $e)
			{
				$this->redirectAfterProc($return_url . '&msg=' . rawurlencode('사진 ' . $i . ': ' . $e->getMessage()));
			}

			if ($new_url)
			{
				$this->deleteMainSlideFile($urls[$i - 1] ?? '');
				$urls[$i - 1] = $new_url;
			}
		}

		$output = dmcadminModel::saveMainSlideUrls($urls);
		if (!$output->toBool())
		{
			$this->redirectAfterProc($return_url . '&msg=' . rawurlencode($output->getMessage() ?: '저장에 실패했습니다.'));
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrMainSlides', 'msg', 'main_slides_saved'));
	}

	public function procDmcMgrSaveSubTops()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$urls = dmcadminModel::getSubTopBannerUrls();
		$dir = dmcadminModel::getSubTopUploadDir();
		FileHandler::makeDir($dir);

		foreach (dmcadminModel::SUB_TOP_MENUS as $key => $meta)
		{
			if (Context::get('remove_sub_top_' . $key) === 'Y')
			{
				dmcadminModel::deleteSubTopBannerFile($urls[$key] ?? '');
				$urls[$key] = '';
				continue;
			}

			$stitch_paths = [];
			for ($i = 1; $i <= dmcadminModel::SUB_TOP_STITCH_MAX; $i++)
			{
				$field = 'stitch_' . $key . '_' . $i;
				if (empty($_FILES[$field]['name']) || !empty($_FILES[$field]['error']))
				{
					continue;
				}
				if (empty($_FILES[$field]['tmp_name']) || !is_uploaded_file($_FILES[$field]['tmp_name']))
				{
					continue;
				}
				if (!@getimagesize($_FILES[$field]['tmp_name']))
				{
					return new BaseObject(-1, dmcadminModel::getSubTopMenuLabel($key) . ': 이미지 파일만 업로드할 수 있습니다.');
				}
				$stitch_paths[] = $_FILES[$field]['tmp_name'];
			}

			$banner_field = 'banner_' . $key;
			$has_single = !empty($_FILES[$banner_field]['name']) && empty($_FILES[$banner_field]['error']);

			try
			{
				if (count($stitch_paths) >= 2)
				{
					$dest = $dir . '/' . $key . '.jpg';
					dmcadminModel::stitchSubTopImages($stitch_paths, $dest);
					dmcadminModel::deleteSubTopBannerFile($urls[$key] ?? '');
					$urls[$key] = './files/church/sub_top/' . $key . '.jpg?t=' . time();
				}
				elseif ($has_single)
				{
					$info = @getimagesize($_FILES[$banner_field]['tmp_name']);
					if (!$info)
					{
						return new BaseObject(-1, dmcadminModel::getSubTopMenuLabel($key) . ': 이미지 파일만 업로드할 수 있습니다.');
					}
					$ext_map = [
						'image/jpeg' => 'jpg',
						'image/png' => 'png',
						'image/webp' => 'webp',
						'image/gif' => 'gif',
					];
					$ext = $ext_map[$info['mime']] ?? 'jpg';
					$dest = $dir . '/' . $key . '.' . $ext;
					dmcadminModel::saveUploadedSubTopSingle($_FILES[$banner_field]['tmp_name'], $dest);
					dmcadminModel::deleteSubTopBannerFile($urls[$key] ?? '');
					$urls[$key] = './files/church/sub_top/' . $key . '.' . $ext . '?t=' . time();
				}
			}
			catch (Rhymix\Framework\Exception $e)
			{
				return new BaseObject(-1, dmcadminModel::getSubTopMenuLabel($key) . ': ' . $e->getMessage());
			}
		}

		$output = dmcadminModel::saveSubTopBannerUrls($urls);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrSubTops', 'msg', 'sub_tops_saved'));
	}

	public function procDmcMgrSaveMainTiles()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$tiles = dmcadminModel::getMainTileData();

		foreach (dmcadminModel::MAIN_TILES as $key => $meta)
		{
			$link_field = 'link_url_' . $key;
			$tiles[$key]['link_url'] = trim((string)Context::get($link_field));

			if (Context::get('remove_tile_' . $key) === 'Y')
			{
				$this->deleteMainTileFile($tiles[$key]['image_url'] ?? '');
				$tiles[$key]['image_url'] = '';
			}

			$field = 'tile_' . $key;
			if (empty($_FILES[$field]['name']))
			{
				continue;
			}
			$upload_err = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
			if ($upload_err !== UPLOAD_ERR_OK)
			{
				return new BaseObject(-1, dmcadminModel::getMainTileLabel($key) . ': ' . $this->describeUploadError($upload_err));
			}

			try
			{
				$new_url = $this->uploadMainTileFile($key, $_FILES[$field]);
			}
			catch (Rhymix\Framework\Exception $e)
			{
				return new BaseObject(-1, dmcadminModel::getMainTileLabel($key) . ': ' . $e->getMessage());
			}

			if ($new_url)
			{
				$this->deleteMainTileFile($tiles[$key]['image_url'] ?? '');
				$tiles[$key]['image_url'] = $new_url;
			}
		}

		$output = dmcadminModel::saveMainTileData($tiles);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrMainTiles', 'msg', 'main_tiles_saved'));
	}

	protected function uploadMainTileFile(string $key, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 업로드할 수 있습니다.');
		}

		$map = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
			'image/gif' => 'gif',
		];
		$ext = $map[$info['mime']] ?? '';
		if (!$ext)
		{
			throw new Rhymix\Framework\Exception('JPG, PNG, WEBP, GIF 형식만 지원합니다.');
		}

		$dir = dmcadminModel::getMainTileUploadDir();
		FileHandler::makeDir($dir);

		$filename = $key . '.jpg';
		$path = $dir . '/' . $filename;
		$tmpPath = $path . '.upload';
		if (!move_uploaded_file($file['tmp_name'], $tmpPath))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}

		try
		{
			dmcadminModel::composeMainTileImage($key, $tmpPath, $path);
		}
		catch (Rhymix\Framework\Exception $e)
		{
			@unlink($tmpPath);
			throw $e;
		}
		@unlink($tmpPath);

		@chmod($path, 0644);
		return './files/church/main_tile/' . $filename . '?t=' . time();
	}

	protected function deleteMainTileFile(string $url): void
	{
		if (!$url || (strpos($url, './files/church/main_tile/') !== 0 && strpos($url, '/files/church/main_tile/') === false))
		{
			return;
		}

		$path = preg_replace('/\?.*$/', '', $url);
		$path = str_replace('./', \RX_BASEDIR, $path);
		$path = preg_replace('#^/files/#', \RX_BASEDIR . 'files/', $path);
		if (is_file($path))
		{
			@unlink($path);
		}
	}

	protected function uploadMainSlideFile(int $slot, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 업로드할 수 있습니다.');
		}

		$map = [
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
			'image/gif' => 'gif',
		];
		$ext = $map[$info['mime']] ?? '';
		if (!$ext)
		{
			throw new Rhymix\Framework\Exception('JPG, PNG, WEBP, GIF 형식만 지원합니다.');
		}

		$dir = dmcadminModel::getMainSlideUploadDir();
		FileHandler::makeDir($dir);

		$filename = 'slide' . $slot . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}

		@chmod($path, 0644);
		$this->resizeImageFile($path, 1600, 900 * 1024);
		return './files/church/main_slide/' . $filename . '?t=' . time();
	}

	protected function deleteMainSlideFile(string $url): void
	{
		if (!$url || (strpos($url, './files/church/main_slide/') !== 0 && strpos($url, '/files/church/main_slide/') === false))
		{
			return;
		}

		$path = preg_replace('/\?.*$/', '', $url);
		$path = str_replace('./', \RX_BASEDIR, $path);
		$path = preg_replace('#^/files/#', \RX_BASEDIR . 'files/', $path);
		if (is_file($path))
		{
			@unlink($path);
		}
	}

	protected function syncMemberGroup(int $member_srl, int $group_srl): void
	{
		$oMemberController = MemberController::getInstance();
		foreach ([2, 3, 4] as $g)
		{
			$oMemberController->deleteMemberGroupMember($member_srl, $g);
		}
		$oMemberController->addMemberToGroup($member_srl, $group_srl);
	}

	public function procDmcMgrSaveInfoPage()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$mid = (string)Context::get('page_mid');
		if (!dmcadminModel::isGuidePage($mid))
		{
			return new BaseObject(-1, '이 페이지는 아직 편집 기능이 준비되지 않았습니다.');
		}

		$output = $this->saveGuidePageForm($mid);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrInfoPageEdit', 'page_mid', $mid, 'msg', 'info_page_saved'));
	}

	protected function saveGuidePageForm(string $mid): BaseObject
	{
		$data = dmcadminModel::getGuidePageData($mid);
		$data['page_title'] = (string)Context::get('page_title');
		$data['catchphrase'] = (string)Context::get('catchphrase');

		if (Context::get('remove_hero_photo') === 'Y')
		{
			$this->deleteGuidePhotoFile($data['hero_photo'] ?? '');
			$data['hero_photo'] = '';
		}
		if (!empty($_FILES['hero_photo']['name']) && empty($_FILES['hero_photo']['error']))
		{
			try
			{
				$new_url = $this->uploadGuidePhoto($mid, 'hero', $_FILES['hero_photo']);
			}
			catch (Rhymix\Framework\Exception $e)
			{
				return new BaseObject(-1, $e->getMessage());
			}
			if ($new_url)
			{
				$this->deleteGuidePhotoFile($data['hero_photo'] ?? '');
				$data['hero_photo'] = $new_url;
			}
		}

		$count = (int)Context::get('section_count');
		if ($count < 1)
		{
			$count = 1;
		}
		if ($count > dmcadminModel::GUIDE_PAGE_MAX_SECTIONS)
		{
			$count = dmcadminModel::GUIDE_PAGE_MAX_SECTIONS;
		}

		$sections = [];
		for ($i = 0; $i < $count; $i++)
		{
			$subtitle = trim((string)Context::get('section_subtitle_' . $i));
			$summary = trim((string)Context::get('section_summary_' . $i));
			$body = trim((string)Context::get('section_body_' . $i));
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
		$data['sections'] = $sections;

		return dmcadminModel::publishGuidePage($mid, $data);
	}

	protected function uploadGuidePhoto(string $mid, string $slot, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getGuidePageUploadDir($mid);
		FileHandler::makeDir($dir);
		$filename = $slot . '_' . date('YmdHis') . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1400, 900 * 1024);
		$safe_mid = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return './files/church/guide/' . $safe_mid . '/' . $filename;
	}

	protected function deleteGuidePhotoFile(string $url): void
	{
		if ($url === '')
		{
			return;
		}
		$path = preg_replace('/\?.*$/', '', $url);
		$path = str_replace('./', \RX_BASEDIR, $path);
		if ($path[0] === '/')
		{
			$path = \RX_BASEDIR . ltrim($path, '/');
		}
		if (is_file($path))
		{
			@unlink($path);
		}
	}

	/**
	 * 업로드된 이미지를 작은 최적화 사진으로 리사이즈(가로/세로 최대 $max_dim, 용량 $max_bytes).
	 * GD가 없거나 이미지가 아니면 원본을 유지한다. 비율은 그대로(크롭 없음).
	 */
	protected function resizeImageFile(string $path, int $max_dim = 1200, int $max_bytes = 1048576): void
	{
		if (!is_file($path) || !function_exists('imagecreatetruecolor'))
		{
			return;
		}

		$info = @getimagesize($path);
		if (!$info || empty($info[0]) || empty($info[1]))
		{
			return;
		}

		$w = (int)$info[0];
		$h = (int)$info[1];
		$type = (int)$info[2];

		$too_big = ($w > $max_dim || $h > $max_dim);
		$too_heavy = (@filesize($path) > $max_bytes);
		if (!$too_big && !$too_heavy)
		{
			return;
		}

		switch ($type)
		{
			case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($path); break;
			case IMAGETYPE_PNG: $src = @imagecreatefrompng($path); break;
			case IMAGETYPE_GIF: $src = @imagecreatefromgif($path); break;
			case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false; break;
			default: return;
		}
		if (!$src)
		{
			return;
		}

		$ratio = $too_big ? min(1.0, $max_dim / max($w, $h)) : 1.0;
		$tw = max(1, (int)round($w * $ratio));
		$th = max(1, (int)round($h * $ratio));

		$dst = imagecreatetruecolor($tw, $th);
		if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF || $type === IMAGETYPE_WEBP)
		{
			imagealphablending($dst, false);
			imagesavealpha($dst, true);
			$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
			imagefilledrectangle($dst, 0, 0, $tw, $th, $transparent);
		}
		imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);

		if ($type === IMAGETYPE_PNG)
		{
			imagepng($dst, $path, 6);
		}
		elseif ($type === IMAGETYPE_GIF)
		{
			imagegif($dst, $path);
		}
		elseif ($type === IMAGETYPE_WEBP && function_exists('imagewebp'))
		{
			imagewebp($dst, $path, 82);
		}
		else
		{
			$quality = 86;
			imagejpeg($dst, $path, $quality);
			while (@filesize($path) > $max_bytes && $quality > 50)
			{
				$quality -= 10;
				imagejpeg($dst, $path, $quality);
			}
		}

		imagedestroy($src);
		imagedestroy($dst);
		@chmod($path, 0644);
		clearstatcache(true, $path);
	}

	/* ===================== 교회 연혁 페이지 저장 ===================== */

	public function procDmcMgrSaveHistoryPage()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$mid = (string)Context::get('page_mid');
		if (!dmcadminModel::isHistoryPage($mid))
		{
			return new BaseObject(-1, '이 페이지는 연혁 편집 대상이 아닙니다.');
		}

		$output = $this->saveHistoryPageForm($mid);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrHistoryPageEdit', 'page_mid', $mid, 'msg', 'info_page_saved'));
	}

	protected function saveHistoryPageForm(string $mid): BaseObject
	{
		$count = (int)Context::get('block_count');
		if ($count < 1)
		{
			$count = 1;
		}
		if ($count > dmcadminModel::HISTORY_PAGE_MAX_BLOCKS)
		{
			$count = dmcadminModel::HISTORY_PAGE_MAX_BLOCKS;
		}

		$blocks = [];
		for ($i = 0; $i < $count; $i++)
		{
			$era = trim((string)Context::get('block_era_' . $i));
			$body = trim((string)Context::get('block_body_' . $i));
			$photo = trim((string)Context::get('existing_block_photo_' . $i));

			if (Context::get('remove_block_photo_' . $i) === 'Y')
			{
				$this->deleteGuidePhotoFile($photo);
				$photo = '';
			}

			$field = 'block_photo_' . $i;
			if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
			{
				try
				{
					$new_url = $this->uploadHistoryPhoto($mid, $i, $_FILES[$field]);
				}
				catch (Rhymix\Framework\Exception $e)
				{
					return new BaseObject(-1, ($i + 1) . '번 블록 사진: ' . $e->getMessage());
				}
				if ($new_url)
				{
					$this->deleteGuidePhotoFile($photo);
					$photo = $new_url;
				}
			}

			if ($era === '' && $body === '' && $photo === '')
			{
				continue;
			}
			$blocks[] = ['era' => $era, 'photo' => $photo, 'body' => $body];
		}

		$data = [
			'page_title' => (string)Context::get('page_title'),
			'blocks' => $blocks,
		];

		return dmcadminModel::publishHistoryPage($mid, $data);
	}

	protected function uploadHistoryPhoto(string $mid, int $index, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getHistoryPageUploadDir($mid);
		FileHandler::makeDir($dir);
		$filename = 'era' . $index . '_' . date('YmdHis') . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1000, 500 * 1024);
		$safe_mid = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return './files/church/history/' . $safe_mid . '/' . $filename . '?t=' . time();
	}

	/* ===================== 섬기는 분 페이지 저장 ===================== */

	public function procDmcMgrSavePeoplePage()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$mid = (string)Context::get('page_mid');
		if (!dmcadminModel::isPeoplePage($mid))
		{
			return new BaseObject(-1, '이 페이지는 인물 편집 대상이 아닙니다.');
		}

		$output = $this->savePeoplePageForm($mid);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrPeoplePageEdit', 'page_mid', $mid, 'msg', 'info_page_saved'));
	}

	protected function savePeoplePageForm(string $mid): BaseObject
	{
		$count = (int)Context::get('person_count');
		if ($count < 1)
		{
			$count = 1;
		}
		if ($count > dmcadminModel::PEOPLE_PAGE_MAX)
		{
			$count = dmcadminModel::PEOPLE_PAGE_MAX;
		}

		$people = [];
		for ($i = 0; $i < $count; $i++)
		{
			$category = (string)Context::get('person_category_' . $i);
			$name = trim((string)Context::get('person_name_' . $i));
			$title = trim((string)Context::get('person_title_' . $i));
			$memo = trim((string)Context::get('person_memo_' . $i));
			$order = (int)Context::get('person_order_' . $i);
			$photo = trim((string)Context::get('existing_person_photo_' . $i));

			if (Context::get('remove_person_photo_' . $i) === 'Y')
			{
				$this->deleteGuidePhotoFile($photo);
				$photo = '';
			}

			$field = 'person_photo_' . $i;
			if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
			{
				try
				{
					$new_url = $this->uploadPeoplePhoto($mid, $i, $_FILES[$field]);
				}
				catch (Rhymix\Framework\Exception $e)
				{
					return new BaseObject(-1, ($i + 1) . '번 인물 사진: ' . $e->getMessage());
				}
				if ($new_url)
				{
					$this->deleteGuidePhotoFile($photo);
					$photo = $new_url;
				}
			}

			if ($name === '' && $title === '' && $memo === '' && $photo === '')
			{
				continue;
			}
			$people[] = [
				'category' => $category,
				'name' => $name,
				'title' => $title,
				'photo' => $photo,
				'memo' => $memo,
				'order' => $order,
			];
		}

		$data = [
			'page_title' => (string)Context::get('page_title'),
			'people' => $people,
		];

		return dmcadminModel::publishPeoplePage($mid, $data);
	}

	protected function uploadPeoplePhoto(string $mid, int $index, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getPeoplePageUploadDir($mid);
		FileHandler::makeDir($dir);
		$filename = 'person' . $index . '_' . date('YmdHis') . mt_rand(100, 999) . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 700, 400 * 1024);
		$safe_mid = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return './files/church/people/' . $safe_mid . '/' . $filename . '?t=' . time();
	}

	/* ===================== 예배시간 페이지 저장 ===================== */

	public function procDmcMgrSaveWorshipPage()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$mid = (string)Context::get('page_mid');
		if (!dmcadminModel::isWorshipPage($mid))
		{
			return new BaseObject(-1, '이 페이지는 예배시간 편집 대상이 아닙니다.');
		}

		$output = $this->saveWorshipPageForm($mid);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrWorshipPageEdit', 'page_mid', $mid, 'msg', 'info_page_saved'));
	}

	protected function saveWorshipPageForm(string $mid): BaseObject
	{
		$count = (int)Context::get('item_count');
		if ($count < 1)
		{
			$count = 1;
		}
		if ($count > dmcadminModel::WORSHIP_PAGE_MAX)
		{
			$count = dmcadminModel::WORSHIP_PAGE_MAX;
		}

		$items = [];
		for ($i = 0; $i < $count; $i++)
		{
			$category = (string)Context::get('item_category_' . $i);
			$name = trim((string)Context::get('item_name_' . $i));
			$time = trim((string)Context::get('item_time_' . $i));
			$place = trim((string)Context::get('item_place_' . $i));

			if ($name === '' && $time === '' && $place === '')
			{
				continue;
			}
			$items[] = [
				'category' => $category,
				'name' => $name,
				'time' => $time,
				'place' => $place,
			];
		}

		$data = [
			'page_title' => (string)Context::get('page_title'),
			'items' => $items,
		];

		return dmcadminModel::publishWorshipPage($mid, $data);
	}

	/* ===================== 새가족 안내 페이지 저장 ===================== */

	public function procDmcMgrSaveNewfamilyPage()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$mid = (string)Context::get('page_mid');
		if (!dmcadminModel::isNewfamilyPage($mid))
		{
			return new BaseObject(-1, '이 페이지는 새가족 안내 편집 대상이 아닙니다.');
		}

		$output = $this->saveNewfamilyPageForm($mid);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrNewfamilyPageEdit', 'page_mid', $mid, 'msg', 'info_page_saved'));
	}

	protected function saveNewfamilyPageForm(string $mid): BaseObject
	{
		$photos = [];
		for ($i = 0; $i < dmcadminModel::NEWFAMILY_PHOTO_COUNT; $i++)
		{
			$photo = trim((string)Context::get('existing_nf_photo_' . $i));

			$field = 'nf_photo_' . $i;
			if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
			{
				try
				{
					$new_url = $this->uploadNewfamilyPhoto($mid, $i, $_FILES[$field]);
				}
				catch (Rhymix\Framework\Exception $e)
				{
					return new BaseObject(-1, ($i + 1) . '번 사진: ' . $e->getMessage());
				}
				if ($new_url)
				{
					$this->deleteGuidePhotoFile($photo);
					$photo = $new_url;
				}
			}

			$photos[$i] = $photo;
		}

		$data = [
			'page_title' => (string)Context::get('page_title'),
			'photos' => $photos,
		];

		return dmcadminModel::publishNewfamilyPage($mid, $data);
	}

	protected function uploadNewfamilyPhoto(string $mid, int $index, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getNewfamilyPageUploadDir($mid);
		FileHandler::makeDir($dir);
		$filename = 'photo' . $index . '_' . date('YmdHis') . mt_rand(100, 999) . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1600, 1024 * 1024);
		$safe_mid = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return './files/church/newfamily/' . $safe_mid . '/' . $filename . '?t=' . time();
	}

	/* ===================== 교회둘러보기(갤러리) 페이지 저장 ===================== */

	public function procDmcMgrSaveTourPage()
	{
		dmcadminModel::requireAuth();
		$mid = (string)Context::get('page_mid');
		$return_url = getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrTourPageEdit', 'page_mid', $mid);
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			$this->redirectAfterProc($return_url . '&msg=' . rawurlencode('보안 토큰이 올바르지 않습니다.'));
		}

		if (!dmcadminModel::isTourPage($mid))
		{
			$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrInfoPages') . '&msg=' . rawurlencode('이 페이지는 갤러리 편집 대상이 아닙니다.'));
		}

		$output = $this->saveTourPageForm($mid);
		if (!$output->toBool())
		{
			$this->redirectAfterProc($return_url . '&msg=' . rawurlencode($output->getMessage() ?: '저장에 실패했습니다.'));
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrTourPageEdit', 'page_mid', $mid, 'msg', 'info_page_saved'));
	}

	protected function saveTourPageForm(string $mid): BaseObject
	{
		$count = (int)Context::get('photo_count');
		if ($count < 1)
		{
			$count = 1;
		}
		if ($count > dmcadminModel::TOUR_PAGE_MAX)
		{
			$count = dmcadminModel::TOUR_PAGE_MAX;
		}

		$photos = [];
		for ($i = 0; $i < $count; $i++)
		{
			$photo = trim((string)Context::get('existing_tour_photo_' . $i));

			if (Context::get('remove_tour_photo_' . $i) === 'Y')
			{
				$this->deleteGuidePhotoFile($photo);
				$photo = '';
			}

			$field = 'tour_photo_' . $i;
			if (!empty($_FILES[$field]['name']))
			{
				$upload_err = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
				if ($upload_err !== UPLOAD_ERR_OK && $upload_err !== UPLOAD_ERR_NO_FILE)
				{
					return new BaseObject(-1, ($i + 1) . '번 사진: ' . $this->describeUploadError($upload_err));
				}
			}
			if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
			{
				try
				{
					$new_url = $this->uploadTourPhoto($mid, $i, $_FILES[$field]);
				}
				catch (Rhymix\Framework\Exception $e)
				{
					return new BaseObject(-1, ($i + 1) . '번 사진: ' . $e->getMessage());
				}
				if ($new_url)
				{
					$this->deleteGuidePhotoFile($photo);
					$photo = $new_url;
				}
			}

			if ($photo === '')
			{
				continue;
			}
			$photos[] = $photo;
		}

		$data = [
			'page_title' => (string)Context::get('page_title'),
			'description' => trim((string)Context::get('page_description')),
			'photos' => $photos,
		];

		return dmcadminModel::publishTourPage($mid, $data);
	}

	protected function uploadTourPhoto(string $mid, int $index, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getTourPageUploadDir($mid);
		FileHandler::makeDir($dir);
		$filename = 'tour' . $index . '_' . date('YmdHis') . mt_rand(100, 999) . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1600, 1024 * 1024);
		$safe_mid = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return './files/church/tour/' . $safe_mid . '/' . $filename . '?t=' . time();
	}

	/* ===================== 교회학교(부서 소개) 페이지 저장 ===================== */

	public function procDmcMgrSaveSchoolPage()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$mid = trim((string)Context::get('page_mid'));
		if ($mid === '' && !empty($_FILES))
		{
			$content_len = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
			$post_max = ini_get('post_max_size');
			return new BaseObject(-1, '업로드 용량이 너무 커서 저장에 실패했습니다. 사진을 줄이거나 한 번에 올리는 장수를 줄여 주세요. (서버 제한: ' . $post_max . ', 요청: ' . round($content_len / 1024 / 1024, 1) . 'MB)');
		}
		if (!dmcadminModel::isSchoolPage($mid))
		{
			return new BaseObject(-1, '교회학교 페이지 정보(page_mid=' . ($mid === '' ? '비어있음' : $mid) . ')를 확인할 수 없습니다. 편집 화면을 새로고침한 뒤 다시 시도해 주세요.');
		}

		$output = $this->saveSchoolPageForm($mid);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrSchoolPageEdit', 'page_mid', $mid, 'msg', 'info_page_saved'));
	}

	protected function saveSchoolPageForm(string $mid): BaseObject
	{
		$photos = [];
		for ($i = 0; $i < dmcadminModel::SCHOOL_PHOTO_COUNT; $i++)
		{
			$photo = trim((string)Context::get('existing_school_photo_' . $i));

			if (Context::get('remove_school_photo_' . $i) === 'Y')
			{
				$this->deleteGuidePhotoFile($photo);
				$photo = '';
			}

			$field = 'school_photo_' . $i;
			if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
			{
				try
				{
					$new_url = $this->uploadSchoolPhoto($mid, $i, $_FILES[$field]);
				}
				catch (Rhymix\Framework\Exception $e)
				{
					return new BaseObject(-1, ($i + 1) . '번 사진: ' . $e->getMessage());
				}
				if ($new_url)
				{
					$this->deleteGuidePhotoFile($photo);
					$photo = $new_url;
				}
			}

			$photos[$i] = $photo;
		}

		$data = [
			'page_title' => (string)Context::get('page_title'),
			'theme' => (string)Context::get('theme'),
			'verse' => (string)Context::get('verse'),
			'goal' => (string)Context::get('goal'),
			'worship' => (string)Context::get('worship'),
			'staff' => (string)Context::get('staff'),
			'photos' => $photos,
		];

		return dmcadminModel::publishSchoolPage($mid, $data);
	}

	protected function uploadSchoolPhoto(string $mid, int $index, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getSchoolPageUploadDir($mid);
		FileHandler::makeDir($dir);
		$filename = 'photo' . $index . '_' . date('YmdHis') . mt_rand(100, 999) . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1400, 800 * 1024);
		$safe_mid = preg_replace('/[^a-z0-9_]/i', '', $mid);
		return './files/church/school/' . $safe_mid . '/' . $filename . '?t=' . time();
	}

	/* ===================== 동키데이 페이지 저장 ===================== */

	public function procDmcMgrSaveDongkeydayPage()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$mid = trim((string)Context::get('page_mid'));
		$content_len = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
		if ($mid === '' && $content_len > 0 && (!empty($_FILES) || $content_len > 2 * 1024 * 1024))
		{
			$post_max = ini_get('post_max_size');
			return new BaseObject(-1, '업로드 용량이 너무 커서 저장에 실패했습니다. 사진이 자동 축소된 뒤 다시 저장해 주세요. (서버 제한: ' . $post_max . ', 요청: ' . round($content_len / 1024 / 1024, 1) . 'MB)');
		}
		if ($mid === '')
		{
			$mid = dmcadminModel::DONGKEYDAY_PAGE_MID;
		}
		if (!dmcadminModel::isDongkeydayPage($mid))
		{
			return new BaseObject(-1, '동키데이 페이지 정보(page_mid=' . ($mid === '' ? '비어있음' : $mid) . ')를 확인할 수 없습니다. 편집 화면을 새로고침한 뒤 다시 시도해 주세요.');
		}

		$output = $this->saveDongkeydayPageForm();
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDongkeydayPageEdit', 'msg', 'info_page_saved'));
	}

	protected function saveDongkeydayPageForm(): BaseObject
	{
		$photos = [];
		for ($i = 0; $i < dmcadminModel::DONGKEYDAY_PHOTO_COUNT; $i++)
		{
			$photo = trim((string)Context::get('existing_dkd_photo_' . $i));

			if (Context::get('remove_dkd_photo_' . $i) === 'Y')
			{
				$this->deleteGuidePhotoFile($photo);
				$photo = '';
			}

			$field = 'dkd_photo_' . $i;
			if (!empty($_FILES[$field]['name']))
			{
				$err = (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE);
				if ($err !== UPLOAD_ERR_OK && $err !== UPLOAD_ERR_NO_FILE)
				{
					return new BaseObject(-1, ($i + 1) . '번 사진: ' . $this->describeUploadError($err));
				}
			}
			if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
			{
				try
				{
					$new_url = $this->uploadDongkeydayPhoto($i, $_FILES[$field]);
				}
				catch (Rhymix\Framework\Exception $e)
				{
					return new BaseObject(-1, ($i + 1) . '번 사진: ' . $e->getMessage());
				}
				if ($new_url)
				{
					$this->deleteGuidePhotoFile($photo);
					$photo = $new_url;
				}
			}

			$photos[$i] = $photo;
		}

		$data = [
			'page_title' => (string)Context::get('page_title'),
			'intro' => trim((string)Context::get('intro')),
			'google_form_url' => trim((string)Context::get('google_form_url')),
			'photos' => $photos,
		];

		return dmcadminModel::publishDongkeydayPage($data);
	}

	protected function uploadDongkeydayPhoto(int $index, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$ext = image_type_to_extension($info[2], false);
		if (!in_array($ext, ['jpeg', 'jpg', 'png', 'gif', 'webp'], true))
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getDongkeydayPageUploadDir();
		FileHandler::makeDir($dir);
		$filename = 'photo' . $index . '_' . date('YmdHis') . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1200, 550 * 1024);
		return './files/church/dongkeyday/' . $filename . '?t=' . time();
	}

	protected function describeUploadError(int $code): string
	{
		switch ($code)
		{
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return '파일 크기가 서버 제한을 초과했습니다. 브라우저에서 사진이 축소된 뒤 다시 저장해 주세요.';
			case UPLOAD_ERR_PARTIAL:
				return '파일이 일부만 전송되었습니다. 네트워크 연결을 확인하고 다시 시도해 주세요.';
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
			case UPLOAD_ERR_EXTENSION:
				return '서버에서 파일을 받지 못했습니다. 관리자에게 문의해 주세요.';
			default:
				return '업로드에 실패했습니다. (오류 코드: ' . $code . ')';
		}
	}

	/* ===================== 국내선교 페이지 저장 ===================== */

	public function procDmcMgrSaveDomesticMissionList()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$output = $this->saveDomesticMissionListForm();
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDomesticMissionListEdit', 'msg', 'info_page_saved'));
	}

	public function procDmcMgrSaveDomesticMissionSub()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$sub_mid = trim((string)Context::get('sub_mid'));
		if ($sub_mid === '' || !dmcadminModel::getDomesticMissionItemBySubMid($sub_mid))
		{
			return new BaseObject(-1, '상세 페이지를 찾을 수 없습니다.');
		}

		$photo = trim((string)Context::get('existing_sub_photo'));
		if (Context::get('remove_sub_photo') === 'Y')
		{
			$this->deleteGuidePhotoFile($photo);
			$photo = '';
		}
		if (!empty($_FILES['sub_photo']['name']) && empty($_FILES['sub_photo']['error']))
		{
			try
			{
				$new_url = $this->uploadDomesticMissionPhoto($sub_mid, 'sub', $_FILES['sub_photo']);
			}
			catch (Rhymix\Framework\Exception $e)
			{
				return new BaseObject(-1, $e->getMessage());
			}
			if ($new_url)
			{
				$this->deleteGuidePhotoFile($photo);
				$photo = $new_url;
			}
		}

		$output = dmcadminModel::saveDomesticMissionSubData($sub_mid, [
			'sub_photo' => $photo,
			'sub_body' => (string)Context::get('sub_body'),
		]);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrDomesticMissionSubEdit', 'sub_mid', $sub_mid, 'msg', 'info_page_saved'));
	}

	protected function saveDomesticMissionListForm(): BaseObject
	{
		$indices = [];
		foreach ($_POST as $key => $unused)
		{
			if (preg_match('/^item_name_(\d+)$/', (string)$key, $m))
			{
				$indices[] = (int)$m[1];
			}
		}
		if (!$indices)
		{
			$count = (int)Context::get('item_count');
			if ($count < 1)
			{
				$count = 1;
			}
			for ($i = 0; $i < $count; $i++)
			{
				$indices[] = $i;
			}
		}
		sort($indices, SORT_NUMERIC);
		$items = [];
		foreach ($indices as $i)
		{
			$name = trim((string)Context::get('item_name_' . $i));
			if ($name === '')
			{
				continue;
			}
			$order = (int)Context::get('item_order_' . $i);
			if ($order < 1)
			{
				return new BaseObject(-1, ($i + 1) . '번: 순서는 1 이상이어야 합니다.');
			}
			$has_sub = Context::get('item_has_sub_' . $i) === 'Y';
			$thumb = '';
			$sub_photo = '';
			$sub_body = '';

			if ($has_sub)
			{
				$sub_body = trim((string)Context::get('item_sub_body_' . $i));
				if ($sub_body === '')
				{
					return new BaseObject(-1, ($i + 1) . '번: 상세 페이지 항목은 사진설명이 필요합니다.');
				}
				$sub_photo = trim((string)Context::get('existing_item_sub_photo_' . $i));
				if (Context::get('remove_item_sub_photo_' . $i) === 'Y')
				{
					$this->deleteGuidePhotoFile($sub_photo);
					$sub_photo = '';
				}
				$field = 'item_sub_photo_' . $i;
				if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
				{
					try
					{
						$new_url = $this->uploadDomesticMissionPhoto('p25', 'sub' . $i, $_FILES[$field]);
					}
					catch (Rhymix\Framework\Exception $e)
					{
						return new BaseObject(-1, ($i + 1) . '번 사진: ' . $e->getMessage());
					}
					if ($new_url)
					{
						$this->deleteGuidePhotoFile($sub_photo);
						$sub_photo = $new_url;
					}
				}
			}
			else
			{
				$thumb = trim((string)Context::get('existing_item_thumb_' . $i));
				if (Context::get('remove_item_thumb_' . $i) === 'Y')
				{
					$this->deleteGuidePhotoFile($thumb);
					$thumb = '';
				}
				$field = 'item_thumb_' . $i;
				if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
				{
					try
					{
						$new_url = $this->uploadDomesticMissionPhoto('p25', 'thumb' . $i, $_FILES[$field]);
					}
					catch (Rhymix\Framework\Exception $e)
					{
						return new BaseObject(-1, ($i + 1) . '번 이미지: ' . $e->getMessage());
					}
					if ($new_url)
					{
						$this->deleteGuidePhotoFile($thumb);
						$thumb = $new_url;
					}
				}
			}

			$items[] = [
				'id' => (string)Context::get('item_id_' . $i),
				'category' => (string)Context::get('item_category_' . $i),
				'name' => $name,
				'thumb' => $thumb,
				'has_sub' => $has_sub,
				'sub_mid' => (string)Context::get('item_sub_mid_' . $i),
				'sub_photo' => $sub_photo,
				'sub_body' => $sub_body,
				'order' => $order,
			];
		}

		return dmcadminModel::saveDomesticMissionListData([
			'page_title' => (string)Context::get('page_title'),
			'items' => $items,
		]);
	}

	protected function uploadDomesticMissionPhoto(string $scope, string $prefix, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getDomesticMissionUploadDir($scope);
		FileHandler::makeDir($dir);
		$filename = preg_replace('/[^a-z0-9_]/i', '', $prefix) . '_' . date('YmdHis') . mt_rand(100, 999) . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1200, 700 * 1024);
		$safe = preg_replace('/[^a-z0-9_]/i', '', $scope);
		return './files/church/domestic/' . $safe . '/' . $filename . '?t=' . time();
	}

	/* ===================== 해외선교 페이지 저장 ===================== */

	public function procDmcMgrSaveOverseasMissionList()
	{
		dmcadminModel::requireAuth();
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$output = $this->saveOverseasMissionListForm();
		if (!$output->toBool())
		{
			return $output;
		}

		$this->redirectAfterProc(getNotEncodedUrl('', 'mid', 'dmcadmin', 'act', 'dispDmcMgrOverseasMissionListEdit', 'msg', 'info_page_saved'));
	}

	protected function saveOverseasMissionListForm(): BaseObject
	{
		$indices = [];
		foreach ($_POST as $key => $unused)
		{
			if (preg_match('/^item_name_(\d+)$/', (string)$key, $m))
			{
				$indices[] = (int)$m[1];
			}
		}
		if (!$indices)
		{
			foreach ($_POST as $key => $unused)
			{
				if (preg_match('/^item_country_(\d+)$/', (string)$key, $m))
				{
					$indices[] = (int)$m[1];
				}
			}
		}
		if (!$indices)
		{
			$count = (int)Context::get('item_count');
			if ($count < 1)
			{
				$count = 1;
			}
			for ($i = 0; $i < $count; $i++)
			{
				$indices[] = $i;
			}
		}
		sort($indices, SORT_NUMERIC);
		$items = [];
		foreach ($indices as $i)
		{
			$country = trim((string)Context::get('item_country_' . $i));
			$name = trim((string)Context::get('item_name_' . $i));
			$missionary = trim((string)Context::get('item_missionary_name_' . $i));
			if ($country === '' && $name === '' && $missionary === '')
			{
				continue;
			}
			if ($country === '')
			{
				return new BaseObject(-1, ($i + 1) . '번: 국가는 필수입니다.');
			}
			if ($name === '' && $missionary === '')
			{
				return new BaseObject(-1, ($i + 1) . '번: 선교사 이름 또는 선교지·기관명 중 하나는 필요합니다.');
			}
			$order = (int)Context::get('item_order_' . $i);
			if ($order < 1)
			{
				return new BaseObject(-1, ($i + 1) . '번: 순서는 1 이상이어야 합니다.');
			}
			$has_sub = Context::get('item_has_sub_' . $i) === 'Y';
			$thumb = '';
			$sub_photo = '';
			$sub_body = '';

			if ($has_sub)
			{
				$sub_body = trim((string)Context::get('item_sub_body_' . $i));
				if ($sub_body === '')
				{
					return new BaseObject(-1, ($i + 1) . '번: 상세 페이지 항목은 사진설명이 필요합니다.');
				}
				$sub_photo = trim((string)Context::get('existing_item_sub_photo_' . $i));
				if (Context::get('remove_item_sub_photo_' . $i) === 'Y')
				{
					$this->deleteGuidePhotoFile($sub_photo);
					$sub_photo = '';
				}
				$field = 'item_sub_photo_' . $i;
				if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
				{
					try
					{
						$new_url = $this->uploadOverseasMissionPhoto('p26', 'sub' . $i, $_FILES[$field]);
					}
					catch (Rhymix\Framework\Exception $e)
					{
						return new BaseObject(-1, ($i + 1) . '번 사진: ' . $e->getMessage());
					}
					if ($new_url)
					{
						$this->deleteGuidePhotoFile($sub_photo);
						$sub_photo = $new_url;
					}
				}
			}
			else
			{
				$thumb = trim((string)Context::get('existing_item_thumb_' . $i));
				if (Context::get('remove_item_thumb_' . $i) === 'Y')
				{
					$this->deleteGuidePhotoFile($thumb);
					$thumb = '';
				}
				$field = 'item_thumb_' . $i;
				if (!empty($_FILES[$field]['name']) && empty($_FILES[$field]['error']))
				{
					try
					{
						$new_url = $this->uploadOverseasMissionPhoto('p26', 'thumb' . $i, $_FILES[$field]);
					}
					catch (Rhymix\Framework\Exception $e)
					{
						return new BaseObject(-1, ($i + 1) . '번 이미지: ' . $e->getMessage());
					}
					if ($new_url)
					{
						$this->deleteGuidePhotoFile($thumb);
						$thumb = $new_url;
					}
				}
			}

			$items[] = [
				'id' => (string)Context::get('item_id_' . $i),
				'category' => (string)Context::get('item_category_' . $i),
				'country' => $country,
				'name' => $name,
				'missionary_name' => $missionary,
				'thumb' => $thumb,
				'has_sub' => $has_sub,
				'sub_mid' => (string)Context::get('item_sub_mid_' . $i),
				'sub_photo' => $sub_photo,
				'sub_body' => $sub_body,
				'order' => $order,
			];
		}

		return dmcadminModel::saveOverseasMissionListData([
			'page_title' => (string)Context::get('page_title'),
			'items' => $items,
		]);
	}

	protected function uploadOverseasMissionPhoto(string $scope, string $prefix, array $file): string
	{
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))
		{
			throw new Rhymix\Framework\Exception('업로드 파일이 올바르지 않습니다.');
		}

		$info = @getimagesize($file['tmp_name']);
		if (!$info)
		{
			throw new Rhymix\Framework\Exception('이미지 파일만 등록할 수 있습니다.');
		}

		$map = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_GIF => 'gif',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $map[$info[2]] ?? '';
		if ($ext === '')
		{
			throw new Rhymix\Framework\Exception('지원하지 않는 이미지 형식입니다.');
		}

		$dir = dmcadminModel::getOverseasMissionUploadDir($scope);
		FileHandler::makeDir($dir);
		$filename = preg_replace('/[^a-z0-9_]/i', '', $prefix) . '_' . date('YmdHis') . mt_rand(100, 999) . '.' . $ext;
		$path = $dir . '/' . $filename;
		if (!move_uploaded_file($file['tmp_name'], $path))
		{
			throw new Rhymix\Framework\Exception('파일 저장에 실패했습니다.');
		}
		@chmod($path, 0644);
		$this->resizeImageFile($path, 1200, 700 * 1024);
		$safe = preg_replace('/[^a-z0-9_]/i', '', $scope);
		return './files/church/overseas/' . $safe . '/' . $filename . '?t=' . time();
	}
}
