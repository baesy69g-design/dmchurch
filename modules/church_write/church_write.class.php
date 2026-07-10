<?php
/**
 * @class  church_write
 * @brief  동명교회 게시판 간편 등록 모듈
 */
class church_write extends ModuleObject
{
	function moduleInstall()
	{
		return new BaseObject();
	}

	function checkUpdate()
	{
		return false;
	}

	function moduleUpdate()
	{
		return new BaseObject();
	}
}
