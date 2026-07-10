#!/bin/bash
set -euo pipefail
bash /tmp/test_keep_signed_login.sh baesy69 'dkagh@6918' N >/dev/null
curl -sk --resolve dmchurch.kr:443:127.0.0.1 -b /tmp/login_keep_N.txt \
  'https://dmchurch.kr/index.php?mid=member&act=dispMemberModifyPassword' -o /tmp/mpw.html
echo "toggle_count=$(grep -c church-pw-toggle /tmp/mpw.html)"
grep -E 'id="cpw"|id="npw1"|id="npw2"' /tmp/mpw.html | head -3
