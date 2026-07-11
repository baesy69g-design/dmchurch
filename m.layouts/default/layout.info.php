<?php
/**
 * Default mobile layout info.
 * Kept as a safety net if true-mobile layout is re-enabled.
 */
$layout_info = new stdClass();
$layout_info->title = 'default';
$layout_info->description = 'Church mobile fallback layout';
$layout_info->version = '1.0';
$layout_info->menu = new stdClass();
$layout_info->menu->main_menu = new stdClass();
$layout_info->menu->main_menu->name = 'main_menu';
$layout_info->menu->main_menu->title = '메인 메뉴';
$layout_info->menu->main_menu->maxdepth = 3;
