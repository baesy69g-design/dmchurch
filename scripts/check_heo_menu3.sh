#!/bin/bash
set -e
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "DESCRIBE rx_menu_item;"
echo '--- children of 306 ---'
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "SELECT menu_item_srl, name, url, parent_srl, listorder FROM rx_menu_item WHERE parent_srl=306 OR menu_item_srl=306 ORDER BY listorder;"
echo '--- cache ---'
ls -la /root/church-web/html/files/cache/menu/ || true
grep -Rsn 'p265' /root/church-web/html/files/cache/menu/ || echo 'p265 not in cache'
grep -Rsn 'p264' /root/church-web/html/files/cache/menu/ | head -5 || true

# rebuild menu xml via rhymix if possible
docker exec church-rhymix php -r '
chdir("/var/www/vhosts/localhost/html");
require "common/autoload.php";
Context::init();
$menu_srl = 48; // guess - check
$o = getModel("menu");
if (method_exists($o, "getMenu")) {
  echo "menu model ok\n";
}
$admin = getController("menu");
if (!$admin) { $admin = getAdminController("menu"); }
var_dump(get_class($admin));
' 2>&1 | head -40

# find menu_srl for overseas
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -N -e "SELECT menu_srl FROM rx_menu_item WHERE url='p26' LIMIT 1;"
