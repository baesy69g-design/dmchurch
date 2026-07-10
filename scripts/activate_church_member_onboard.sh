#!/bin/bash
set -euo pipefail
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "
UPDATE rx_addons_site SET is_used='Y', is_used_m='Y', extra_vars='' WHERE addon='church_member_onboard';
UPDATE rx_addons SET is_used='Y', is_used_m='Y' WHERE addon='church_member_onboard';
"
docker exec church-rhymix rm -rf /var/www/vhosts/localhost/html/files/cache/addons/*
