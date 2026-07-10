<?php
/**
 * @file church_main_slide.addon.php
 * @brief dmcadmin에서 등록한 메인 대표사진 4장을 레이아웃 슬라이드에 반영
 */
if (!defined('__XE__'))
{
	exit();
}

if ($called_position !== 'before_display_content' || Context::getResponseMethod() !== 'HTML' || isCrawler())
{
	return;
}

if (Context::get('module') === 'admin')
{
	return;
}

getModel('dmcadmin');
dmcadminModel::applyMainSlidesToLayout();
