<?php
/**
 * Debug procChurchWriteGetDocument field extraction + simulate request vars
 */
require '/var/www/vhosts/localhost/html/common/autoload.php';
Context::init();

$srl = (int)($argv[1] ?? 900328);
$oDocument = DocumentModel::getDocument($srl);
echo "exists=" . ($oDocument && $oDocument->isExists() ? 'Y' : 'N') . PHP_EOL;
if (!$oDocument || !$oDocument->isExists()) {
	exit(1);
}
$module_srl = (int)$oDocument->get('module_srl');
echo "module_srl=$module_srl" . PHP_EOL;
$forms = church_writeModel::getBoardForms();
echo "form_ok=" . (isset($forms[$module_srl]) ? 'Y' : 'N') . PHP_EOL;
$f = church_writeModel::extractEditFields($oDocument);
echo "fields=" . json_encode($f, JSON_UNESCAPED_UNICODE) . PHP_EOL;

// Check action is registered
$info = Rhymix\Framework\Parsers\ModuleActionParser::loadXML(RX_BASEDIR . 'modules/church_write/conf/module.xml');
echo "has_get_act=" . (isset($info->action->procChurchWriteGetDocument) ? 'Y' : 'N') . PHP_EOL;
