<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html');
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();

$info = ModuleModel::getModuleInfoByModule('church_member');
echo "module installed: " . ($info ? 'yes mid=' . ($info->mid ?? '') : 'no') . "\n";

$oView = getView('church_member');
$oView->dispChurchMemberGuide();
$t = new Rhymix\Framework\Template(__RX_BASEDIR__ . 'modules/church_member/tpl', 'guide');
$html = $t->compile();
echo "html length: " . strlen($html) . "\n";
echo substr($html, 0, 500) . "\n";
