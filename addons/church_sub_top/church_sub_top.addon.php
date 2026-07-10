<?php
/**
 * @file church_sub_top.addon.php
 * @brief dmcadmin 서브 TOP 배너 CSS·GNB (배너 URL은 layout.html에서 조회)
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
Context::loadFile('./addons/church_sub_top/church_sub_top.css');
Context::loadFile('./addons/church_gnb/church_gnb.css');
Context::loadFile('./addons/church_gnb/church_gnb.js');
dmcadminModel::applyChurchLogoToLayout();
dmcadminModel::applySubPageTitle();

$module = (string)Context::get('module');
$act = (string)Context::get('act');
$skip_site_protect = in_array($module, ['admin', 'dmcadmin'], true)
	|| ($act !== '' && preg_match('/(?:write|login|signup|edit|setup|admin|dmcadmin|proc)/i', $act));

if (!$skip_site_protect)
{
	Context::loadFile('./addons/church_site_protect/church_site_protect.css');
	Context::loadFile(['./addons/church_site_protect/church_site_protect.js', 'body', '', null], true);
}
