#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "SELECT addon, is_used FROM rx_addons_site WHERE addon='church_member_onboard';"
docker exec church-rhymix rm -rf /var/www/vhosts/localhost/html/files/cache/addons/*
docker exec church-rhymix php -r "define('__RX_BASEDIR__','/var/www/vhosts/localhost/html'); require __RX_BASEDIR__.'/common/autoload.php'; Context::init(); Rhymix\Framework\Cache::clearAll();"
curl -sk --resolve dmchurch.kr:443:127.0.0.1 'https://dmchurch.kr/' -o /dev/null
docker exec church-rhymix grep church_member_onboard /var/www/vhosts/localhost/html/files/cache/addons/pc.php | head -2
