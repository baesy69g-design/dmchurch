#!/bin/bash
set -euo pipefail
HOST=dmchurch.kr
R="--resolve ${HOST}:443:127.0.0.1"
C=/tmp/cookie_dbg2.txt
rm -f "$C"
curl -sk $R -c "$C" "https://${HOST}/" -o /tmp/h1.html
CSRF=$(grep -oP 'name="csrf-token" content="\K[^"]+' /tmp/h1.html | head -1)
SID1=$(grep PHPSESSID "$C" | awk '{print $7}')
echo "sid_before=$SID1"
curl -sk $R -b "$C" -c "$C" -D /tmp/p.hdr -X POST "https://${HOST}/index.php?act=procMemberLogin" \
  -H "Origin: https://${HOST}" -H "Referer: https://${HOST}/" -H "Sec-Fetch-Site: same-origin" \
  -H "X-Requested-With: XMLHttpRequest" -H "Accept: application/json" \
  -H "X-CSRF-Token: ${CSRF}" \
  -d "user_id=baesy69" -d "password=dkagh@6918" \
  -d "xe_validator_id=layouts/xedition/layout/1" \
  -d "success_return_url=https://${HOST}/" -o /tmp/p.json
SID2=$(grep PHPSESSID "$C" | awk '{print $7}')
echo "sid_after_post=$SID2"
grep -i set-cookie /tmp/p.hdr || true
cat /tmp/p.json; echo
curl -sk $R -b "$C" -c "$C" -D /tmp/g.hdr "https://${HOST}/" -o /tmp/h2.html
SID3=$(grep PHPSESSID "$C" | awk '{print $7}')
echo "sid_after_get=$SID3"
grep -i set-cookie /tmp/g.hdr || true
grep -E 'cmd_logout|ly_logout|logout|baesy69|member_srl' /tmp/h2.html | head -6
grep -c 'member_srl' /tmp/h2.html || true
