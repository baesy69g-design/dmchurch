<?php
/**
 * @class  church_member
 * @brief  새 홈피 회원 이메일 확인·비밀번호 재설정
 */
class church_member extends ModuleObject
{
	function moduleInstall()
	{
		return new BaseObject();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		if (!$oModuleModel->getTrigger('member.doLogin', 'church_member', 'controller', 'triggerMemberDoLoginAfter', 'after'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('member.doLogout', 'church_member', 'controller', 'triggerMemberDoLogoutBefore', 'before'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('member.procMemberModifyInfo', 'church_member', 'controller', 'triggerMemberModifyInfoBefore', 'before'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('member.procMemberModifyInfo', 'church_member', 'controller', 'triggerMemberModifyInfoAfter', 'after'))
		{
			return true;
		}
		return false;
	}

	function moduleUpdate()
	{
		$oModuleController = getController('module');
		$oModuleController->insertTrigger('member.doLogin', 'church_member', 'controller', 'triggerMemberDoLoginAfter', 'after');
		$oModuleController->insertTrigger('member.doLogout', 'church_member', 'controller', 'triggerMemberDoLogoutBefore', 'before');
		$oModuleController->insertTrigger('member.procMemberModifyInfo', 'church_member', 'controller', 'triggerMemberModifyInfoBefore', 'before');
		$oModuleController->insertTrigger('member.procMemberModifyInfo', 'church_member', 'controller', 'triggerMemberModifyInfoAfter', 'after');
		$oInstallController = getController('install');
		$oInstallController->installModule('church_member', \RX_BASEDIR . 'modules/church_member');
		return new BaseObject();
	}
}
