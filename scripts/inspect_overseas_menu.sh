#!/bin/bash
python3 /tmp/inspect_overseas_json.py
echo '--- menus ---'
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -N -e \
"SELECT menu_item_srl, name, url, parent_srl, listorder FROM rx_menu_item WHERE name LIKE '%허수%' OR name LIKE '%서원%' OR name LIKE '%해외선교%' OR url REGEXP '^p26' ORDER BY parent_srl, listorder LIMIT 50;"
echo '--- modules ---'
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -N -e \
"SELECT mid, browser_title, module_srl FROM rx_modules WHERE mid LIKE 'p26%' ORDER BY mid;"
