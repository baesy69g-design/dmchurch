#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "SELECT addon, is_used, run_method FROM rx_addons WHERE addon LIKE '%church%' ORDER BY addon;"
