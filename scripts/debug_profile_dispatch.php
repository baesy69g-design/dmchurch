<?php
define('__RX_BASEDIR__', '/var/www/vhosts/localhost/html/');
require __RX_BASEDIR__ . 'common/autoload.php';
Context::init();

getController('member')->doLogin('baesy69', 'dkagh@6918', false);

$xml = ModuleModel::getModuleActionXml('church_member');
$act = 'dispChurchMemberProfile';
echo 'action in xml: ' . (isset($xml->action->$act) ? 'yes' : 'no') . "\n";
if (isset($xml->action->$act))
{
	echo 'standalone: ' . ($xml->action->$act->standalone ?? '?') . "\n";
	echo 'permission: ' . ($xml->action->$act->permission->target ?? '?') . "\n";
}

$oView = getView('church_member');
echo 'view class: ' . get_class($oView) . "\n";
echo 'method exists: ' . (method_exists($oView, $act) ? 'yes' : 'no') . "\n";

$mh = new ModuleHandler('church_member', $act);
echo "handler module={$mh->module} act={$mh->act} mid={$mh->mid}\n";
$mh->init();
echo "after init module={$mh->module} act={$mh->act}\n";

$result = $mh->procModule();
echo 'result class: ' . (is_object($result) ? get_class($result) : gettype($result)) . "\n";
if (is_object($result) && method_exists($result, 'getMessage'))
{
	echo 'message: ' . $result->getMessage() . "\n";
}
