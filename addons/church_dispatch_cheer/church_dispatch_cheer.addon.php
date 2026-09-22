<?php
/**
 * 파송선교(p27) 페이지에서 응원 스크립트·스타일 보조 로드
 * (본문 HTML에도 스크립트를 넣지만, 캐시 전 페이지에서도 동작하도록 보강)
 */
if (!defined('RX_VERSION') && !defined('__XE__'))
{
	return;
}

if ($called_position !== 'before_display_content' && $called_position !== 'after_module_proc')
{
	return;
}

$mid = Context::get('mid');
if ($mid !== 'p27')
{
	return;
}

Context::loadFile('./addons/church_dispatch_cheer/church_dispatch_cheer.js');
Context::loadFile('./addons/church_theme/church_theme.css');
