#!/bin/bash
echo '=== controller has update? ==='
grep -n 'procChurchWriteUpdateDocument\|dispChurchWriteGetDocument\|extractEditFields' \
  /root/church-web/html/modules/church_write/church_write.controller.php \
  /root/church-web/html/modules/church_write/church_write.model.php | head -20
echo '=== docker view ==='
docker exec church-rhymix grep -n 'procChurchWriteUpdateDocument\|extractEditFields' \
  /var/www/vhosts/localhost/html/modules/church_write/church_write.controller.php \
  /var/www/vhosts/localhost/html/modules/church_write/church_write.model.php | head -20
echo '=== getModuleActionXml source ==='
docker exec church-rhymix sed -n '936,1020p' /var/www/vhosts/localhost/html/modules/module/module.model.php
