<?php
/**
 * @class  church_memberModel
 */
class church_memberModel extends church_member
{
	public const AUTH_VERIFY = 'church_email_verify';
	public const AUTH_RECOVER = 'church_recover_v2';
	public const AUTH_CHANGE_EMAIL = 'church_change_email';
	public const ONBOARD_COOKIE = 'church_onboard';
	public const PC_ONBOARD_COOKIE = 'church_pc_ok';

	/** 레이아웃 로그인 팝업 URL (회원 모듈 별도 로그인 페이지 대신 사용) */
	public static function getLayoutLoginUrl($success_return_url = '')
	{
		$url = getNotEncodedUrl('');
		$sep = (strpos($url, '?') !== false) ? '&' : '?';
		$out = $url . $sep . 'church_login=1';
		if ($success_return_url !== '' && Rhymix\Framework\URL::isInternalURL($success_return_url))
		{
			$out .= '&success_return_url=' . rawurlencode($success_return_url);
		}
		return $out;
	}

	/** 로그아웃 직후 로그인 팝업 자동 표시용 (120초) */
	public static function setOpenLoginCookie(): void
	{
		$secure = (defined('RX_SSL') && RX_SSL) || (config('use_ssl') === 'always');
		Rhymix\Framework\Cookie::set('church_open_login', '1', [
			'path' => '/',
			'expires' => time() + 120,
			'secure' => $secure,
			'samesite' => 'Lax',
		]);
	}

	public static function hasPcOnboardFlag(): bool
	{
		if (($_COOKIE[self::PC_ONBOARD_COOKIE] ?? '') === '1')
		{
			return true;
		}
		return (bool)self::parseOnboardCookie($_COOKIE[self::ONBOARD_COOKIE] ?? '');
	}

	public static function rememberPcOnboardFlag(): void
	{
		$expires = time() + 365 * 86400;
		setcookie(self::PC_ONBOARD_COOKIE, '1', [
			'expires' => $expires,
			'path' => '/',
			'samesite' => 'Lax',
		]);
		$_COOKIE[self::PC_ONBOARD_COOKIE] = '1';
	}

	public static function parseOnboardCookie(?string $raw): array
	{
		$ids = array_map(static function (string $id): string {
			return strtolower(trim($id));
		}, explode(',', (string)$raw));
		return array_values(array_filter($ids, static function (string $id): bool {
			return $id !== '' && preg_match('/^[a-z0-9._-]{2,40}$/', $id);
		}));
	}

	public static function isOnboardedUserId(?string $user_id): bool
	{
		$user_id = strtolower(trim((string)$user_id));
		if ($user_id === '')
		{
			return false;
		}
		return in_array($user_id, self::parseOnboardCookie($_COOKIE[self::ONBOARD_COOKIE] ?? ''), true);
	}

	public static function rememberOnboardedUser(?string $user_id): void
	{
		$user_id = strtolower(trim((string)$user_id));
		if ($user_id === '' || !preg_match('/^[a-z0-9._-]{2,40}$/', $user_id))
		{
			return;
		}
		$ids = self::parseOnboardCookie($_COOKIE[self::ONBOARD_COOKIE] ?? '');
		if (!in_array($user_id, $ids, true))
		{
			$ids[] = $user_id;
		}
		if (count($ids) > 30)
		{
			$ids = array_slice($ids, -30);
		}
		$expires = time() + 365 * 86400;
		setcookie(self::ONBOARD_COOKIE, implode(',', $ids), [
			'expires' => $expires,
			'path' => '/',
			'samesite' => 'Lax',
		]);
		$_COOKIE[self::ONBOARD_COOKIE] = implode(',', $ids);
		self::rememberPcOnboardFlag();
	}

	public static function shouldShowLoginGuide(?string $user_id): bool
	{
		$user_id = strtolower(trim((string)$user_id));
		if ($user_id === '')
		{
			return !self::hasPcOnboardFlag();
		}
		if (self::isOnboardedUserId($user_id))
		{
			return false;
		}
		$member = MemberModel::getMemberInfoByUserID($user_id);
		if (!$member || empty($member->member_srl))
		{
			return true;
		}
		if (!self::needsEmailVerification($member))
		{
			return false;
		}
		return true;
	}

	public static function getMemberModifyInfoUrl(): string
	{
		return self::getMemberProfileUrl();
	}

	public static function getMemberProfileUrl(): string
	{
		return getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchMemberProfile');
	}

	public static function isPlaceholderEmail(?string $email): bool
	{
		return (bool)preg_match('/@dmchurch\.local$/i', trim((string)$email));
	}

	public static function isValidPersonalEmail(string $email): bool
	{
		$email = strtolower(trim($email));
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return false;
		}
		if (self::isPlaceholderEmail($email))
		{
			return false;
		}
		if (preg_match('/@(00000\.000|example\.com)$/i', $email))
		{
			return false;
		}
		return true;
	}

	public static function isExemptMember($member): bool
	{
		if (!$member)
		{
			return true;
		}
		$uid = strtolower((string)($member->user_id ?? ''));
		return in_array($uid, ['admin', 'rankup', 'test'], true) || ($member->is_admin ?? 'N') === 'Y';
	}

	public static function loadExtraBySrl(int $member_srl): stdClass
	{
		$output = executeQuery('member.getMemberInfoByMemberSrl', (object)['member_srl' => $member_srl]);
		if (!$output->data)
		{
			return new stdClass;
		}
		$raw = (string)($output->data->extra_vars ?? '');
		if ($raw === '')
		{
			return new stdClass;
		}
		$parsed = @unserialize($raw);
		if ($parsed instanceof stdClass)
		{
			return $parsed;
		}
		if (is_array($parsed))
		{
			return (object)$parsed;
		}
		$json = json_decode($raw);
		if ($json instanceof stdClass)
		{
			return $json;
		}
		return new stdClass;
	}

	public static function getPreservedExtraKeys(): array
	{
		return [
			'email_verified',
			'pending_email',
			'email_original',
			'rankup_level',
			'rankup_passwd',
			'birthday',
			'baptismalname',
			'phone',
			'gender',
			'zipcode',
			'address1',
			'address2',
		];
	}

	public static function mergePreservedExtra(stdClass $current, stdClass $backup): stdClass
	{
		foreach (self::getPreservedExtraKeys() as $key)
		{
			$backup_val = $backup->$key ?? null;
			$current_val = $current->$key ?? null;
			if ($key === 'email_verified' && ($backup_val ?? '') === 'Y')
			{
				$current->email_verified = 'Y';
				continue;
			}
			if (($current_val === null || $current_val === '') && $backup_val !== null && $backup_val !== '')
			{
				$current->$key = $backup_val;
			}
		}
		return $current;
	}

	public static function canUseMemberSelfService($member): bool
	{
		if (!$member || self::isExemptMember($member))
		{
			return true;
		}
		if (!self::needsEmailVerification($member))
		{
			return true;
		}
		return !self::isPlaceholderEmail($member->email_address ?? '');
	}

	public static function saveExtraBySrl(int $member_srl, stdClass $extra): void
	{
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->extra_vars = serialize($extra);
		$output = executeQuery('member.updateMemberExtraVars', $args);
		if (!$output->toBool())
		{
			throw new Rhymix\Framework\Exception('회원 추가정보 저장에 실패했습니다.');
		}
		MemberController::clearMemberCache($member_srl);
	}

	public static function getMemberExtra($member): stdClass
	{
		if ($member && !empty($member->member_srl))
		{
			return self::loadExtraBySrl((int)$member->member_srl);
		}
		getModel('dmcadmin');
		return dmcadminModel::getMemberExtra($member);
	}

	public static function needsEmailVerification($member): bool
	{
		if (!$member || empty($member->member_srl) || self::isExemptMember($member))
		{
			return false;
		}
		$extra = self::getMemberExtra($member);
		if (!empty($extra->email_verified) && $extra->email_verified === 'Y')
		{
			return false;
		}
		return self::isPlaceholderEmail($member->email_address ?? '') || empty($extra->email_verified);
	}

	public static function generateAuthUrl(string $act, int $member_srl, string $auth_key): string
	{
		$path = getNotEncodedUrl('', 'module', 'church_member', 'act', $act, 'member_srl', $member_srl, 'auth_key', $auth_key);
		if (preg_match('#^https?://#i', $path))
		{
			return Rhymix\Framework\URL::getCanonicalURL($path);
		}
		return Rhymix\Framework\URL::getCurrentDomainURL($path);
	}

	public static function isEmailUsedByOther(string $email, int $member_srl): ?object
	{
		$existing = MemberModel::getMemberSrlByEmailAddress($email);
		if (!$existing || (int)$existing === $member_srl)
		{
			return null;
		}
		return MemberModel::getMemberInfoByMemberSrl((int)$existing);
	}

	public static function emailConflictMessage(?object $other): string
	{
		if (!$other)
		{
			return '이미 다른 회원이 사용 중인 이메일입니다.';
		}
		$uid = (string)($other->user_id ?? '');
		if ($uid === 'dmc2241' || ($other->is_admin ?? 'N') === 'Y')
		{
			return '이 이메일은 관리자 계정에 등록되어 있습니다. 구홈피에 등록했던 다른 개인 이메일(예: hotmail, gmail)을 입력해 주세요.';
		}
		return '이미 다른 회원(' . $uid . ')이 사용 중인 이메일입니다.';
	}

	public static function getAuthExpiresSeconds(): int
	{
		$config = MemberModel::getMemberConfig();
		return (intval($config->authmail_expires ?? 1) * intval($config->authmail_expires_unit ?? 86400)) ?: 86400;
	}

	public static function validateAuth(int $member_srl, string $auth_key, string $auth_type): ?object
	{
		$output = executeQuery('member.getAuthMail', [
			'member_srl' => $member_srl,
			'auth_key' => $auth_key,
		]);
		if (!$output->data || $output->data->auth_key !== $auth_key || $output->data->member_srl != $member_srl)
		{
			return null;
		}
		if (($output->data->auth_type ?? '') !== $auth_type)
		{
			return null;
		}
		if (ztime($output->data->regdate) < time() - self::getAuthExpiresSeconds())
		{
			executeQuery('member.deleteAuthMail', [
				'member_srl' => $member_srl,
				'auth_key' => $auth_key,
			]);
			return null;
		}
		return $output->data;
	}

	public static function createAuthMail(int $member_srl, string $user_id, string $auth_type): string
	{
		$auth_key = Rhymix\Framework\Security::getRandom(40, 'hex');
		$args = new stdClass;
		$args->member_srl = $member_srl;
		$args->user_id = $user_id;
		$args->auth_key = $auth_key;
		$args->auth_type = $auth_type;
		$args->new_password = Rhymix\Framework\Password::getRandomPassword(32);
		$args->is_register = 'N';
		$output = executeQuery('member.insertAuthMail', $args);
		if (!$output->toBool())
		{
			throw new Rhymix\Framework\Exception('인증 정보 저장에 실패했습니다.');
		}
		return $auth_key;
	}

	public static function savePendingEmail(int $member_srl, string $email, bool $keep_verified = false): void
	{
		$extra = self::loadExtraBySrl($member_srl);
		$extra->pending_email = strtolower(trim($email));
		if (!$keep_verified)
		{
			unset($extra->email_verified);
		}
		self::saveExtraBySrl($member_srl, $extra);
	}

	public static function getPendingEmail($member): string
	{
		$extra = self::getMemberExtra($member);
		return strtolower(trim((string)($extra->pending_email ?? '')));
	}

	public static function confirmMemberEmail(int $member_srl, string $email): void
	{
		$email = strtolower(trim($email));
		if (!self::isValidPersonalEmail($email))
		{
			throw new Rhymix\Framework\Exception('유효하지 않은 이메일 주소입니다.');
		}

		$existing = MemberModel::getMemberSrlByEmailAddress($email);
		if ($existing && (int)$existing !== $member_srl)
		{
			throw new Rhymix\Framework\Exception('이미 다른 회원이 사용 중인 이메일입니다.');
		}

		$backup = self::loadExtraBySrl($member_srl);
		$extra = clone $backup;
		$extra->email_verified = 'Y';
		unset($extra->pending_email);

		$parts = explode('@', $email, 2);
		$output = executeQuery('member.updateMemberEmailAddress', (object)[
			'member_srl' => $member_srl,
			'email_address' => $email,
			'email_id' => $parts[0],
			'email_host' => $parts[1],
		]);
		if (!$output->toBool())
		{
			throw new Rhymix\Framework\Exception('이메일 저장에 실패했습니다.');
		}
		$extra = self::mergePreservedExtra($extra, $backup);
		self::saveExtraBySrl($member_srl, $extra);
	}

	public static function sendMail(string $to, string $subject, string $body): bool
	{
		$oMail = new Rhymix\Framework\Mail();
		$oMail->setSubject($subject);
		$oMail->setBody($body);
		$oMail->addTo($to);
		$sent = (bool)$oMail->send(true);
		$log = sprintf("[%s] to=%s subject=%s sent=%s errors=%s\n", date('c'), $to, $subject, $sent ? 'Y' : 'N', json_encode($oMail->errors ?? [], JSON_UNESCAPED_UNICODE));
		@file_put_contents(\RX_BASEDIR . 'files/church_mail.log', $log, FILE_APPEND);
		return $sent;
	}

	public static function getLastMailError(): string
	{
		$path = \RX_BASEDIR . 'files/church_mail.log';
		if (!is_file($path))
		{
			return '';
		}
		$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!$lines)
		{
			return '';
		}
		$last = end($lines);
		if (preg_match('/errors=(.+)$/', $last, $m) && $m[1] !== '[]')
		{
			return $m[1];
		}
		return '';
	}

	public static function isMemberSelfServiceAction(?string $act): bool
	{
		return in_array((string)$act, [
			'dispChurchMemberProfile',
			'procChurchSaveProfile',
			'dispMemberModifyInfo',
			'dispMemberModifyPassword',
			'dispMemberModifyEmailAddress',
			'dispMemberInfo',
			'procMemberModifyInfo',
			'procMemberModifyPassword',
			'procMemberModifyEmailAddress',
		], true);
	}

	public static function isChurchMemberAction(?string $act): bool
	{
		return in_array((string)$act, [
			'dispChurchMemberProfile',
			'procChurchSaveProfile',
			'dispChurchMemberGuide',
			'dispChurchCheckOnboard',
			'dispChurchVerifyEmail',
			'procChurchSendVerifyEmail',
			'procChurchConfirmEmail',
			'dispChurchChangeEmail',
			'procChurchChangeEmailRequest',
			'procChurchConfirmChangeEmail',
			'dispChurchRecoverAccount',
			'procChurchRecoverRequest',
			'dispChurchRecoverReset',
			'procChurchRecoverSave',
		], true);
	}
}
