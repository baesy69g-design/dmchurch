#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "SELECT addon, is_used FROM rx_addons ORDER BY addon;"
