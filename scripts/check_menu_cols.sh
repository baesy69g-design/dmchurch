#!/bin/bash
docker exec church-mariadb mariadb -urmx_user -prmx!!4321 rmx_db -e "DESCRIBE rx_menu_item;" | head -40
