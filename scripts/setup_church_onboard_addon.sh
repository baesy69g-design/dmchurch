#!/bin/bash
set -euo pipefail
NOW=$(date +%Y%m%d%H%M%S)
docker exec church-mariadb mysql -uroot -pm2m1234! rmx_db -e "
UPDATE rx_addons_site SET is_used='Y', is_used_m='Y', extra_vars='' WHERE addon='church_member_onboard';
INSERT INTO rx_addons (addon, is_used, is_used_m, is_fixed, regdate)
SELECT 'church_member_onboard', 'Y', 'Y', 'N', '${NOW}'
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM rx_addons WHERE addon='church_member_onboard');
UPDATE rx_addons SET is_used='Y', is_used_m='Y' WHERE addon='church_member_onboard';
"
docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/rebuild_addon_cache.php
docker exec church-rhymix php -r "
define('__RX_BASEDIR__','/var/www/vhosts/localhost/html');
require __RX_BASEDIR__.'/common/autoload.php';
Context::init();
\$c=getController('module');
if (!getModel('module')->getTrigger('member.doLogout','church_member','controller','triggerMemberDoLogoutBefore','before')) {
  \$c->insertTrigger('member.doLogout','church_member','controller','triggerMemberDoLogoutBefore','before');
  echo 'logout trigger inserted\n';
} else { echo 'logout trigger exists\n'; }
"
