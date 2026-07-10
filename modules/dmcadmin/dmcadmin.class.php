<?php
/**
 * @class  dmcadmin
 * @brief  동명교회 관리 페이지 (dmcadmin)
 */
class dmcadmin extends ModuleObject
{
	function moduleInstall()
	{
		return new BaseObject();
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		if (!$oModuleModel->getTrigger('member.doLogin', 'dmcadmin', 'controller', 'triggerMemberDoLoginBefore', 'before'))
		{
			return true;
		}
		$xml = ModuleModel::getModuleActionXml('dmcadmin');
		if (!$xml || empty($xml->default_index_act))
		{
			return true;
		}
		return false;
	}

	function moduleUpdate()
	{
		$oModuleController = getController('module');
		$oModuleController->insertTrigger('member.doLogin', 'dmcadmin', 'controller', 'triggerMemberDoLoginBefore', 'before');
		$oInstallController = getController('install');
		$oInstallController->installModule('dmcadmin', \RX_BASEDIR . 'modules/dmcadmin');
		return new BaseObject();
	}
}
