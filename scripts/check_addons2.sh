#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -N -e "SHOW TABLES LIKE '%addon%';"
docker exec church-rhymix grep -r church_member_onboard /var/www/vhosts/localhost/html/files/config/ 2>/dev/null | head -5
docker exec church-rhymix ls /var/www/vhosts/localhost/html/files/config/
