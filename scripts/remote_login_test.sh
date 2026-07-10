#!/bin/bash
set -euo pipefail
PASS=$(docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/set_temp_password.php dmc2241)
echo "temp_pass=${PASS}"
bash /tmp/test_https_login.sh dmc2241 "${PASS}" browser
