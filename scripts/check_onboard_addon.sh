#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "SELECT addon, is_used FROM rx_addons WHERE addon='church_member_onboard';"
docker exec church-rhymix test -f /var/www/vhosts/localhost/html/addons/church_member_onboard/church_member_onboard.addon.php && echo addon_file=OK
