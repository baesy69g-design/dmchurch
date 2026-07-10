<?php
/**
 * @file church_member_onboard.addon.php
 */
if (!defined('__XE__'))
{
	exit();
}

if (Context::get('module') === 'admin' || isCrawler())
{
	return;
}

getModel('church_member');

$act = (string)(Context::get('act') ?: ($_GET['act'] ?? ''));
$module = (string)(Context::get('module') ?: ($_GET['module'] ?? ''));
$logged = Context::get('logged_info');
$login_url = church_memberModel::getLayoutLoginUrl();
$verify_url = getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchVerifyEmail');
$recover_url = getNotEncodedUrl('', 'module', 'church_member', 'act', 'dispChurchRecoverAccount');
$modify_url = church_memberModel::getMemberModifyInfoUrl();

if (isset($_GET['mid']) && $_GET['mid'] !== '' && in_array($act, ['dispChurchMemberProfile', 'procChurchSaveProfile'], true))
{
	$url = church_memberModel::getMemberProfileUrl();
	$msg = (string)Context::get('msg');
	if ($msg !== '')
	{
		$url .= (strpos($url, '?') !== false ? '&' : '?') . 'msg=' . rawurlencode($msg);
	}
	header('Location: ' . $url);
	Context::close();
	exit;
}

if ($act === 'dispMemberLoginForm' && !Context::get('is_logged'))
{
	$return_url = (string)Context::get('success_return_url');
	header('Location: ' . church_memberModel::getLayoutLoginUrl($return_url));
	Context::close();
	exit;
}

if ($act === 'dispMemberLogout' && Context::get('is_logged'))
{
	church_memberModel::setOpenLoginCookie();
}

if ($act === 'dispChurchMemberGuide')
{
	header('Location: ' . $login_url);
	Context::close();
	exit;
}

if ($act === 'dispMemberFindAccount')
{
	header('Location: ' . $recover_url);
	Context::close();
	exit;
}

if ($act === 'dispMemberModifyEmailAddress')
{
	header('Location: ' . $modify_url);
	Context::close();
	exit;
}

if (in_array($act, ['dispMemberModifyPassword', 'dispMemberModifyInfo'], true) && $logged)
{
	header('Location: ' . church_memberModel::getMemberProfileUrl());
	Context::close();
	exit;
}

$church_ob = strtolower(trim((string)Context::get('church_ob')));
if ($church_ob !== '' && preg_match('/^[a-z0-9._-]{2,40}$/', $church_ob))
{
	church_memberModel::rememberOnboardedUser($church_ob);
}

if ($logged && !church_memberModel::isExemptMember($logged) && !church_memberModel::needsEmailVerification($logged))
{
	church_memberModel::rememberOnboardedUser($logged->user_id ?? '');
}

if ($called_position === 'before_display_content' && Context::getResponseMethod() === 'HTML')
{
	$onboard_ids = church_memberModel::parseOnboardCookie($_COOKIE[church_memberModel::ONBOARD_COOKIE] ?? '');
	$pc_onboard = church_memberModel::hasPcOnboardFlag();

	Context::addHtmlHeader(
		'<script>try{if(localStorage.getItem("church_pc_onboarded")==="1"){document.documentElement.classList.add("church-pc-onboarded");}}catch(e){}</script>'
	);

	if ($onboard_ids)
	{
		Context::addHtmlHeader(
			'<script>window.churchOnboardIds=' . json_encode($onboard_ids, JSON_UNESCAPED_UNICODE) . ';try{localStorage.setItem("church_pc_onboarded","1");}catch(e){}</script>'
		);
	}

	if ($pc_onboard || $onboard_ids)
	{
		Context::addHtmlHeader('<style>html.church-pc-onboarded #church_login_guide,#church_login_guide[data-church-suppressed="1"]{display:none!important}</style>');
	}

	if ($logged && !church_memberModel::isExemptMember($logged) && !church_memberModel::needsEmailVerification($logged))
	{
		Context::addHtmlHeader('<script>try{localStorage.setItem("church_pc_onboarded","1");}catch(e){}</script>');
	}

	if ($logged && church_memberModel::needsEmailVerification($logged))
	{
		$allowed = church_memberModel::isChurchMemberAction($act)
			|| in_array($act, ['procMemberLogout', 'dispMemberLoginForm', 'procMemberLogin'], true);
		if (!$allowed && $module !== 'dmcadmin')
		{
			header('Location: ' . $verify_url);
			Context::close();
			exit;
		}

		Context::loadFile('./addons/church_member_onboard/church_member_onboard.css');
		Context::addHtmlHeader(
			'<div class="church-onboard-banner">새 홈피 이용을 위해 개인 이메일 확인이 필요합니다. '
			. '<a href="' . htmlspecialchars($verify_url, ENT_QUOTES, 'UTF-8') . '">이메일 확인하기</a></div>'
		);
	}
	elseif ($act === 'dispMemberModifyInfo' && $logged && church_memberModel::needsEmailVerification($logged))
	{
		header('Location: ' . $verify_url);
		Context::close();
		exit;
	}
}
