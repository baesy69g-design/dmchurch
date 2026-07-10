#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "SELECT addon, is_used FROM rx_addons; SELECT addon, is_used, extra_vars FROM rx_addons_site WHERE is_used='Y';"
