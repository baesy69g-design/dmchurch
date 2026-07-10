<?php
require dirname(__DIR__) . '/common/autoload.php';
Context::init();
Context::set('module', 'board');
Context::set('module_srl', 110);
Context::set('mid', 'sermon');
Context::set('logged_info', null);
Context::set('is_logged', false);
Context::setResponseMethod('HTML');
$called_position = 'after_module_proc';
try {
    include RX_BASEDIR . 'addons/church_board_ui/church_board_ui.addon.php';
    echo "addon ok\n";
    echo Context::getHtmlFooter() . "\n";
} catch (Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
