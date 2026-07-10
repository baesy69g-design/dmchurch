<?php
/**
 * @class  church_memberController
 */
class church_memberController extends church_member
{
	protected function redirectWithMessage(string $url, string $message, bool $success = true, ?string $onboard_user_id = null): BaseObject
	{
		$sep = strpos($url, '?') !== false ? '&' : '?';
		$url .= $sep . 'msg=' . rawurlencode($message);
		if ($onboard_user_id)
		{
			$url .= '&church_ob=' . rawurlencode(strtolower(trim($onboard_user_id)));
		}
		$this->setRedirectUrl($url);
		return new BaseObject($success ? 0 : -1, $message);
	}

	protected function failVerify(string $message): BaseObject
	{
		return $this->redirectWithMessage(
			getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail'),
			$message,
			false
		);
	}

	protected function failRecover(string $message): BaseObject
	{
		return $this->redirectWithMessage(
			getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchRecoverAccount'),
			$message,
			false
		);
	}

	protected function failChangeEmail(string $message): BaseObject
	{
		return $this->redirectWithMessage(
			church_memberModel::getMemberProfileUrl(),
			$message,
			false
		);
	}

	protected function failProfile(string $message): BaseObject
	{
		return $this->redirectWithMessage(
			church_memberModel::getMemberProfileUrl(),
			$message,
			false
		);
	}

	public function triggerMemberDoLoginAfter($obj)
	{
		if (empty($obj->member_srl))
		{
			return new BaseObject();
		}
		$member = MemberModel::getMemberInfoByMemberSrl((int)$obj->member_srl);
		if (!church_memberModel::needsEmailVerification($member))
		{
			church_memberModel::rememberOnboardedUser($member->user_id ?? '');
			return new BaseObject();
		}
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail'));
		return new BaseObject();
	}

	public function triggerMemberDoLogoutBefore($obj)
	{
		church_memberModel::setOpenLoginCookie();
		return new BaseObject();
	}

	public function triggerMemberModifyInfoBefore($args)
	{
		$logged = Context::get('logged_info');
		if (!$logged || church_memberModel::isExemptMember($logged))
		{
			return new BaseObject();
		}

		$backup = church_memberModel::loadExtraBySrl((int)$logged->member_srl);
		Context::set('church_member_extra_backup', $backup);

		if (!church_memberModel::canUseMemberSelfService($logged))
		{
			return new BaseObject(-1, '먼저 개인 이메일 확인을 완료해 주세요.');
		}

		$email = strtolower(trim((string)Context::get('church_email_address')));
		if ($email === '')
		{
			$email = strtolower(trim((string)Context::get('email_address')));
		}
		if ($email === '')
		{
			return new BaseObject();
		}
		if (!church_memberModel::isValidPersonalEmail($email))
		{
			return new BaseObject(-1, '올바른 등록 이메일 주소를 입력해 주세요.');
		}
		if (strtolower((string)$logged->email_address) === $email)
		{
			return new BaseObject();
		}

		$other = church_memberModel::isEmailUsedByOther($email, (int)$logged->member_srl);
		if ($other)
		{
			return new BaseObject(-1, church_memberModel::emailConflictMessage($other));
		}

		try
		{
			church_memberModel::confirmMemberEmail((int)$logged->member_srl, $email);
		}
		catch (Rhymix\Framework\Exception $e)
		{
			return new BaseObject(-1, $e->getMessage());
		}

		$logged->email_address = $email;
		Context::set('logged_info', $logged);

		return new BaseObject();
	}

	public function triggerMemberModifyInfoAfter($obj)
	{
		$logged = Context::get('logged_info');
		if (!$logged || church_memberModel::isExemptMember($logged))
		{
			return new BaseObject();
		}

		$backup = Context::get('church_member_extra_backup');
		if (!$backup instanceof stdClass)
		{
			return new BaseObject();
		}

		$extra = church_memberModel::loadExtraBySrl((int)$logged->member_srl);
		church_memberModel::saveExtraBySrl((int)$logged->member_srl, church_memberModel::mergePreservedExtra($extra, $backup));

		return new BaseObject();
	}

	public function procChurchSendVerifyEmail()
	{
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return $this->failVerify('보안 토큰이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
		}

		$logged = Context::get('logged_info');
		if (!$logged)
		{
			return $this->failVerify('로그인이 필요합니다. 다시 로그인해 주세요.');
		}
		if (!church_memberModel::needsEmailVerification($logged))
		{
			return $this->failVerify('이미 이메일 확인이 완료되었습니다.');
		}

		$email = strtolower(trim((string)Context::get('email_address')));
		if (!church_memberModel::isValidPersonalEmail($email))
		{
			return $this->failVerify('올바른 개인 이메일 주소를 입력해 주세요.');
		}

		$other = church_memberModel::isEmailUsedByOther($email, (int)$logged->member_srl);
		if ($other)
		{
			return $this->failVerify(church_memberModel::emailConflictMessage($other));
		}

		try
		{
			church_memberModel::savePendingEmail((int)$logged->member_srl, $email);
			$auth_key = church_memberModel::createAuthMail((int)$logged->member_srl, $logged->user_id, church_memberModel::AUTH_VERIFY);
		}
		catch (Rhymix\Framework\Exception $e)
		{
			return $this->failVerify($e->getMessage());
		}

		$url = church_memberModel::generateAuthUrl('procChurchConfirmEmail', (int)$logged->member_srl, $auth_key);
		$body = sprintf(
			'<p>%s님, 동명교회 새 홈페이지 개인 이메일 확인입니다.</p><p><a href="%s">이메일 확인하기</a></p><p>본인이 요청하지 않았다면 이 메일을 무시하세요.</p>',
			htmlspecialchars($logged->nick_name ?: $logged->user_id, ENT_QUOTES, 'UTF-8'),
			htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
		);
		if (!church_memberModel::sendMail($email, '[동명교회] 개인 이메일 확인', $body))
		{
			return $this->failVerify('메일 발송에 실패했습니다. 잠시 후 다시 시도해 주세요.');
		}

		return $this->redirectWithMessage(
			getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail'),
			'확인 메일을 ' . $email . ' 로 보냈습니다. 받은편지함과 스팸함을 확인해 주세요.'
		);
	}

	public function procChurchConfirmEmail()
	{
		$member_srl = (int)Context::get('member_srl');
		$auth_key = (string)Context::get('auth_key');
		$auth = church_memberModel::validateAuth($member_srl, $auth_key, church_memberModel::AUTH_VERIFY);
		if (!$auth)
		{
			return $this->failVerify('인증 링크가 만료되었거나 올바르지 않습니다. 다시 요청해 주세요.');
		}

		$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
		$email = church_memberModel::getPendingEmail($member);
		if (!$email)
		{
			return $this->failVerify('확인할 이메일 정보가 없습니다. 다시 요청해 주세요.');
		}

		try
		{
			church_memberModel::confirmMemberEmail($member_srl, $email);
		}
		catch (Rhymix\Framework\Exception $e)
		{
			return $this->failVerify($e->getMessage());
		}

		executeQuery('member.deleteAuthMail', ['member_srl' => $member_srl, 'auth_key' => $auth_key]);
		MemberController::clearMemberCache($member_srl);
		church_memberModel::rememberOnboardedUser($member->user_id ?? '');

		$logged = Context::get('logged_info');
		$home_url = getNotEncodedUrl('', 'mid', Context::get('site_module_info')->mid ?? 'index');
		if ($logged && (int)$logged->member_srl === $member_srl)
		{
			return $this->redirectWithMessage(
				$home_url,
				'이메일 확인이 완료되었습니다. 홈페이지를 이용하실 수 있습니다.',
				true,
				$member->user_id ?? ''
			);
		}

		return $this->redirectWithMessage(
			church_memberModel::getLayoutLoginUrl(),
			'이메일 확인이 완료되었습니다. 로그인해 주세요.',
			true,
			$member->user_id ?? ''
		);
	}

	public function procChurchRecoverRequest()
	{
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return $this->failRecover('보안 토큰이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
		}

		$user_id = strtolower(trim((string)Context::get('user_id')));
		$email = strtolower(trim((string)Context::get('email_address')));
		if (!$user_id || !church_memberModel::isValidPersonalEmail($email))
		{
			return $this->failRecover('아이디와 올바른 개인 이메일을 입력해 주세요.');
		}

		$member = MemberModel::getMemberInfoByUserID($user_id);
		if (!$member || empty($member->member_srl))
		{
			return $this->failRecover('일치하는 회원 정보가 없습니다. 아이디를 확인해 주세요.');
		}
		if (church_memberModel::isExemptMember($member))
		{
			return $this->failRecover('관리자 계정은 이 화면에서 재설정할 수 없습니다.');
		}

		$other = church_memberModel::isEmailUsedByOther($email, (int)$member->member_srl);
		if ($other)
		{
			return $this->failRecover(church_memberModel::emailConflictMessage($other));
		}

		$extra = church_memberModel::getMemberExtra($member);
		if (!empty($extra->email_verified) && $extra->email_verified === 'Y')
		{
			if (strtolower((string)$member->email_address) !== $email)
			{
				return $this->failRecover('등록된 이메일과 일치하지 않습니다.');
			}
		}

		try
		{
			church_memberModel::savePendingEmail((int)$member->member_srl, $email);
			$auth_key = church_memberModel::createAuthMail((int)$member->member_srl, $member->user_id, church_memberModel::AUTH_RECOVER);
		}
		catch (Rhymix\Framework\Exception $e)
		{
			return $this->failRecover($e->getMessage());
		}

		$url = church_memberModel::generateAuthUrl('dispChurchRecoverReset', (int)$member->member_srl, $auth_key);
		$body = sprintf(
			'<p>%s님, 동명교회 새 홈페이지 비밀번호 재설정입니다.</p><p><a href="%s">새 비밀번호 설정하기</a></p><p>본인이 요청하지 않았다면 이 메일을 무시하세요.</p>',
			htmlspecialchars($member->nick_name ?: $member->user_id, ENT_QUOTES, 'UTF-8'),
			htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
		);
		if (!church_memberModel::sendMail($email, '[동명교회] 비밀번호 재설정', $body))
		{
			return $this->failRecover('메일 발송에 실패했습니다. 잠시 후 다시 시도해 주세요.');
		}

		return $this->redirectWithMessage(
			getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchRecoverAccount'),
			'재설정 메일을 ' . $email . ' 로 보냈습니다. 받은편지함과 스팸함을 확인해 주세요.'
		);
	}

	public function procChurchRecoverSave()
	{
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return new BaseObject(-1, '보안 토큰이 올바르지 않습니다.');
		}

		$member_srl = (int)Context::get('member_srl');
		$auth_key = (string)Context::get('auth_key');
		$password = (string)Context::get('password');
		$password2 = (string)Context::get('password2');

		$auth = church_memberModel::validateAuth($member_srl, $auth_key, church_memberModel::AUTH_RECOVER);
		if (!$auth)
		{
			return $this->failRecover('인증 링크가 만료되었거나 올바르지 않습니다.');
		}
		if (!$password || strlen($password) < 4)
		{
			return new BaseObject(-1, '비밀번호는 4자 이상이어야 합니다.');
		}
		if ($password !== $password2)
		{
			return new BaseObject(-1, '비밀번호 확인이 일치하지 않습니다.');
		}

		$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
		$email = church_memberModel::getPendingEmail($member);
		if (!$email)
		{
			return $this->failRecover('이메일 정보가 없습니다. 처음부터 다시 요청해 주세요.');
		}

		MemberController::getInstance()->updateMemberPassword((object)[
			'member_srl' => $member_srl,
			'password' => $password,
		]);
		church_memberModel::confirmMemberEmail($member_srl, $email);

		$extra = church_memberModel::loadExtraBySrl($member_srl);
		if (!empty($extra->rankup_passwd))
		{
			unset($extra->rankup_passwd);
			church_memberModel::saveExtraBySrl($member_srl, $extra);
		}

		executeQuery('member.deleteAuthMail', ['member_srl' => $member_srl, 'auth_key' => $auth_key]);
		MemberController::clearMemberCache($member_srl);
		church_memberModel::rememberOnboardedUser($member->user_id ?? '');

		return $this->redirectWithMessage(
			church_memberModel::getLayoutLoginUrl(),
			'비밀번호와 이메일이 저장되었습니다. 새 비밀번호로 로그인해 주세요.',
			true,
			$member->user_id ?? ''
		);
	}

	public function procChurchChangeEmailRequest()
	{
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return $this->failChangeEmail('보안 토큰이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
		}

		$logged = Context::get('logged_info');
		if (!$logged)
		{
			return $this->failChangeEmail('로그인이 필요합니다.');
		}
		if (church_memberModel::isExemptMember($logged))
		{
			return $this->failChangeEmail('관리자 계정은 이 화면을 사용할 수 없습니다.');
		}
		if (church_memberModel::needsEmailVerification($logged))
		{
			return $this->failChangeEmail('먼저 개인 이메일 확인을 완료해 주세요.');
		}

		$email = strtolower(trim((string)Context::get('email_address')));
		if (!church_memberModel::isValidPersonalEmail($email))
		{
			return $this->failChangeEmail('올바른 새 이메일 주소를 입력해 주세요.');
		}
		if (strtolower((string)$logged->email_address) === $email)
		{
			return $this->failChangeEmail('현재 등록된 이메일과 동일합니다.');
		}

		$other = church_memberModel::isEmailUsedByOther($email, (int)$logged->member_srl);
		if ($other)
		{
			return $this->failChangeEmail(church_memberModel::emailConflictMessage($other));
		}

		try
		{
			church_memberModel::confirmMemberEmail((int)$logged->member_srl, $email);
		}
		catch (Rhymix\Framework\Exception $e)
		{
			return $this->failChangeEmail($e->getMessage());
		}

		return $this->redirectWithMessage(
			church_memberModel::getMemberProfileUrl() . '#church-email',
			'등록 이메일이 ' . $email . ' 로 변경되었습니다.'
		);
	}

	public function procChurchSaveProfile()
	{
		if (!Rhymix\Framework\Security::checkCSRF())
		{
			return $this->failProfile('보안 토큰이 만료되었습니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.');
		}

		$logged = Context::get('logged_info');
		if (!$logged || empty($logged->member_srl))
		{
			return $this->failProfile('로그인이 필요합니다.');
		}
		if (!church_memberModel::canUseMemberSelfService($logged))
		{
			return $this->failProfile('먼저 개인 이메일 확인을 완료해 주세요.');
		}

		$member_srl = (int)$logged->member_srl;
		$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
		if (!$member)
		{
			return $this->failProfile('회원 정보를 찾을 수 없습니다.');
		}
		if (church_memberModel::needsEmailVerification($member))
		{
			return $this->failProfile('먼저 개인 이메일 확인을 완료해 주세요.');
		}

		$backup = church_memberModel::loadExtraBySrl($member_srl);
		getModel('dmcadmin');
		$extra = dmcadminModel::buildExtraVars(Context::getRequestVars(), $backup);

		$user_name = trim((string)Context::get('user_name'));
		$nick_name = trim((string)Context::get('nick_name'));
		if ($user_name === '')
		{
			return $this->failProfile('이름을 입력해 주세요.');
		}

		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->user_name = $user_name;
		$args->nick_name = $nick_name !== '' ? $nick_name : $user_name;
		$args->phone_number = preg_replace('/\D/', '', (string)Context::get('phone_number'));
		$birthday = preg_replace('/\D/', '', (string)Context::get('birthday'));
		if ($birthday !== '')
		{
			$args->birthday = $birthday;
		}

		$output = MemberController::getInstance()->updateMember($args);
		if (!$output->toBool())
		{
			return $this->failProfile($output->getMessage() ?: '회원 정보 저장에 실패했습니다.');
		}

		$email = strtolower(trim((string)Context::get('email_address')));
		if ($email !== '' && strtolower((string)$member->email_address) !== $email)
		{
			if (!church_memberModel::isValidPersonalEmail($email))
			{
				return $this->failProfile('올바른 이메일 주소를 입력해 주세요.');
			}
			$other = church_memberModel::isEmailUsedByOther($email, $member_srl);
			if ($other)
			{
				return $this->failProfile(church_memberModel::emailConflictMessage($other));
			}
			try
			{
				church_memberModel::confirmMemberEmail($member_srl, $email);
			}
			catch (Rhymix\Framework\Exception $e)
			{
				return $this->failProfile($e->getMessage());
			}
		}

		$extra = church_memberModel::mergePreservedExtra($extra, $backup);
		church_memberModel::saveExtraBySrl($member_srl, $extra);

		$current_password = (string)Context::get('current_password');
		$password1 = (string)Context::get('password1');
		$password2 = (string)Context::get('password2');
		if ($current_password !== '' || $password1 !== '' || $password2 !== '')
		{
			if ($current_password === '' || $password1 === '' || $password2 === '')
			{
				return $this->failProfile('비밀번호 변경 시 현재·새 비밀번호를 모두 입력해 주세요.');
			}
			if ($password1 !== $password2)
			{
				return $this->failProfile('새 비밀번호가 서로 일치하지 않습니다.');
			}
			$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
			$valid_current = MemberModel::isValidPassword($member->password, $current_password, $member_srl);
			if (!$valid_current)
			{
				$valid_current = dmcadminModel::verifyLegacyPassword($member, $current_password);
			}
			if (!$valid_current)
			{
				return $this->failProfile('현재 비밀번호가 올바르지 않습니다.');
			}
			dmcadminModel::upgradePassword($member_srl, $password1);
			$extra = church_memberModel::loadExtraBySrl($member_srl);
			unset($extra->rankup_passwd);
			church_memberModel::saveExtraBySrl($member_srl, $extra);
		}

		MemberController::clearMemberCache($member_srl);
		return $this->redirectWithMessage(
			church_memberModel::getMemberProfileUrl(),
			'사용자 정보가 저장되었습니다.',
			true
		);
	}

	public function procChurchConfirmChangeEmail()
	{
		$member_srl = (int)Context::get('member_srl');
		$auth_key = (string)Context::get('auth_key');
		$auth = church_memberModel::validateAuth($member_srl, $auth_key, church_memberModel::AUTH_CHANGE_EMAIL);
		if ($auth)
		{
			$member = MemberModel::getMemberInfoByMemberSrl($member_srl);
			$email = church_memberModel::getPendingEmail($member);
			if ($email)
			{
				try
				{
					church_memberModel::confirmMemberEmail($member_srl, $email);
				}
				catch (Rhymix\Framework\Exception $e)
				{
					// fall through to redirect below
				}
			}
			executeQuery('member.deleteAuthMail', ['member_srl' => $member_srl, 'auth_key' => $auth_key]);
			MemberController::clearMemberCache($member_srl);
		}

		$logged = Context::get('logged_info');
		$url = ($logged && (int)$logged->member_srl === $member_srl)
			? church_memberModel::getMemberModifyInfoUrl() . '#church-email'
			: church_memberModel::getLayoutLoginUrl();

		return $this->redirectWithMessage(
			$url,
			'이메일 변경은 로그인 후 회원정보 변경 화면에서 바로 할 수 있습니다.'
		);
	}
}
