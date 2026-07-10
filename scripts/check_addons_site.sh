#!/bin/bash
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "DESCRIBE rx_addons_site; SELECT * FROM rx_addons_site;"
