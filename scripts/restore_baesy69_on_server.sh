#!/bin/bash
set -e
docker exec church-mariadb mysql -uroot -pm2m1234! -N -e \
  "SELECT e.gender,e.zipcode,e.address1,e.address2,e.phone,e.hphone,m.passwd
   FROM rankup_src.rankup_member m
   LEFT JOIN rankup_src.rankup_member_extend e ON e.uid=m.uid
   WHERE m.uid='baesy69'" > /tmp/baesy69_rankup.tsv

docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/restore_baesy69_from_tsv.php /tmp/baesy69_rankup.tsv
