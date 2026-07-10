#!/bin/bash
docker exec church-rhymix php -r '
require "/var/www/vhosts/localhost/html/common/autoload.php";
Context::init();
$tpl = new Rhymix\Framework\Template("/var/www/vhosts/localhost/html/modules/member/skins/default/login_form.html");
try {
  $c = $tpl->compile();
  echo "login_form_compile=OK len=" . strlen($c) . PHP_EOL;
} catch (Throwable $e) {
  echo "login_form_compile=FAIL " . $e->getMessage() . PHP_EOL;
}
$tpl2 = new Rhymix\Framework\Template("/var/www/vhosts/localhost/html/modules/member/skins/default/modify_password.html");
try {
  $c2 = $tpl2->compile();
  echo "modify_password_compile=OK len=" . strlen($c2) . PHP_EOL;
} catch (Throwable $e) {
  echo "modify_password_compile=FAIL " . $e->getMessage() . PHP_EOL;
}
'
