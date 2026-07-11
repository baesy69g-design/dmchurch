#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e \
"SELECT menu_item_srl, name, url, parent_srl, listorder, is_enable, is_show, expand FROM rx_menu_item WHERE parent_srl=306 OR menu_item_srl=306 ORDER BY listorder;"
echo '--- menu cache files ---'
find /root/church-web/html/files/cache -iname '*menu*' 2>/dev/null | head -30
