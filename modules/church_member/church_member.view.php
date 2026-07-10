<?php
/**
 * @class  church_memberView
 */
class church_memberView extends church_member
{
	protected function initLayout(string $template, string $title = '', bool $with_site_layout = false): void
	{
		if ($with_site_layout)
		{
			$this->applySiteLayout();
		}
		Context::addBodyClass('church-member-page');
		Context::loadFile('./modules/church_member/church_member.css');
		Context::set('church_member_title', $title);
		Context::set('is_logged', (bool)Context::get('logged_info'));
		Context::set('csrf_token', Rhymix\Framework\Session::createToken(''));
		Context::set('church_member_msg', Context::get('msg'));
		Context::set('church_member_msg_is_error', preg_match('/(실패|올바르지|일치하지|필요|만료|사용 중|등록되어)/u', (string)Context::get('msg')));
		if ($title)
		{
			Context::setBrowserTitle($title . ' - 동명교회');
		}
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile($template);
	}

	protected function applySiteLayout(): void
	{
		$site = Context::get('site_module_info');
		if (!$site || empty($site->mid))
		{
			return;
		}

		$home = ModuleModel::getModuleInfoByMid($site->mid);
		if (!$home)
		{
			return;
		}

		if (!$this->module_info)
		{
			$this->module_info = new stdClass;
		}

		$is_mobile = Mobile::isFromMobilePhone();
		$layout_srl = $is_mobile ? ($home->mlayout_srl ?? 0) : ($home->layout_srl ?? 0);
		if ($layout_srl == -1)
		{
			$oLayoutAdminModel = getAdminModel('layout');
			$layout_srl = $oLayoutAdminModel->getSiteDefaultLayout($is_mobile ? 'M' : 'P', $home->site_srl ?? 0);
		}
		elseif ($layout_srl == -2 && $is_mobile)
		{
			$layout_srl = $home->layout_srl ?? 0;
			if ($layout_srl == -1)
			{
				$oLayoutAdminModel = getAdminModel('layout');
				$layout_srl = $oLayoutAdminModel->getSiteDefaultLayout('P', $home->site_srl ?? 0);
			}
		}

		$this->module_info->layout_srl = $layout_srl;
		if ($is_mobile)
		{
			$this->module_info->mlayout_srl = $layout_srl;
		}
		$this->module_info->use_mobile = $home->use_mobile ?? 'N';
		$this->module_info->module = $this->module ?? 'church_member';

		// 레이아웃 껍데기(layout_srl)만 홈과 맞춤. mid/module_info 덮어쓰면 메인 홈 타일이 본문에 같이 나옴.
		Context::loadFile('./layouts/xedition/css/church_welcome.css');
	}

	public function dispChurchMemberGuide()
	{
		Context::set('login_url', church_memberModel::getLayoutLoginUrl());
		Context::set('verify_url', getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail'));
		Context::set('recover_url', getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchRecoverAccount'));
		$this->initLayout('guide', '새 홈피 회원 안내');
	}

	public function dispChurchVerifyEmail()
	{
		$logged = Context::get('logged_info');
		if (!$logged)
		{
			$this->setRedirectUrl(church_memberModel::getLayoutLoginUrl(getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail')));
			return;
		}
		if (!church_memberModel::needsEmailVerification($logged))
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('site_module_info')->mid ?? 'index'));
			return;
		}
		Context::set('user_id', $logged->user_id);
		Context::set('pending_email', church_memberModel::getPendingEmail($logged));
		$this->initLayout('verify_email', '개인 이메일 확인');
	}

	public function dispChurchRecoverAccount()
	{
		Context::loadFile('./layouts/xedition/css/church_login_guide.css');
		Context::set('login_url', church_memberModel::getLayoutLoginUrl());
		$this->initLayout('recover_request', '비밀번호 재설정');
	}

	public function dispChurchRecoverReset()
	{
		$member_srl = (int)Context::get('member_srl');
		$auth_key = (string)Context::get('auth_key');
		$auth = church_memberModel::validateAuth($member_srl, $auth_key, church_memberModel::AUTH_RECOVER);
		if (!$auth)
		{
			Context::set('msg', '인증 링크가 만료되었거나 올바르지 않습니다. 다시 요청해 주세요.');
			return $this->dispChurchRecoverAccount();
		}
		$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
		Context::set('member_srl', $member_srl);
		Context::set('auth_key', $auth_key);
		Context::set('user_id', $member->user_id ?? '');
		Context::set('pending_email', church_memberModel::getPendingEmail($member));
		$this->initLayout('recover_reset', '새 비밀번호 설정');
	}

	public function dispChurchCheckOnboard()
	{
		$user_id = strtolower(trim((string)Context::get('user_id')));
		$show_guide = church_memberModel::shouldShowLoginGuide($user_id);
		if (!$show_guide && $user_id !== '')
		{
			church_memberModel::rememberOnboardedUser($user_id);
		}
		Context::setResponseMethod('JSON');
		$this->add('show_guide', $show_guide);
		$this->add('onboard_ids', church_memberModel::parseOnboardCookie($_COOKIE[church_memberModel::ONBOARD_COOKIE] ?? ''));
	}

	public function dispChurchChangeEmail()
	{
		$logged = Context::get('logged_info');
		if (!$logged)
		{
			$this->setRedirectUrl(church_memberModel::getLayoutLoginUrl(church_memberModel::getMemberProfileUrl()));
			return;
		}
		if (church_memberModel::needsEmailVerification($logged))
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail'));
			return;
		}
		$this->setRedirectUrl(church_memberModel::getMemberProfileUrl() . '#church-email');
	}

	public function dispChurchMemberProfile()
	{
		if (!$this->module_info)
		{
			$this->module_info = new stdClass;
			$this->module_info->module = $this->module ?? 'church_member';
		}

		$logged = Context::get('logged_info');
		if (!$logged)
		{
			$this->setRedirectUrl(church_memberModel::getLayoutLoginUrl(church_memberModel::getMemberProfileUrl()));
			return;
		}

		$member = MemberModel::getMemberInfoByMemberSrl((int)$logged->member_srl);
		if (church_memberModel::needsEmailVerification($member))
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail'));
			return;
		}
		$extra = church_memberModel::getMemberExtra($member);
		$profile = new stdClass;
		$profile->user_id = $member->user_id ?? '';
		$profile->user_name = $member->user_name ?? '';
		$profile->nick_name = $member->nick_name ?? '';
		$profile->email_address = $member->email_address ?? '';
		$profile->phone_number = $member->phone_number ?? '';
		$profile->phone = $extra->phone ?? '';
		$profile->birthday = $member->birthday ?? ($extra->birthday ?? '');
		$profile->gender = $extra->gender ?? '';
		$profile->zipcode = $extra->zipcode ?? '';
		$profile->address1 = $extra->address1 ?? '';
		$profile->address2 = $extra->address2 ?? '';
		$profile->needs_verify = church_memberModel::needsEmailVerification($member);

		Context::set('profile', $profile);
		Context::loadFile('./modules/member/skins/default/css/church_pw_toggle.css');
		Context::loadFile('./modules/member/skins/default/js/church_pw_toggle.js');
		$this->initLayout('profile', '사용자정보변경', true);
	}
}
