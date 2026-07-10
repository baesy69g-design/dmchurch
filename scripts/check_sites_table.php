<?php
define('__RX_BASEDIR__', dirname(__DIR__));
require __RX_BASEDIR__ . '/common/autoload.php';
Context::init();
$oDB = DB::getInstance();
$q = $oDB->_query("SELECT domain_srl, domain, security, is_default_domain, http_port, https_port FROM {$oDB->prefix}domains");
while ($row = $oDB->db_fetch_object($q))
{
	echo implode("\t", [
		$row->domain_srl,
		$row->domain,
		$row->security,
		$row->is_default_domain,
		$row->http_port,
		$row->https_port,
	]) . PHP_EOL;
}
