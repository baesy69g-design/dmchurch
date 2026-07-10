#!/bin/bash
set -euo pipefail
HOST=dmchurch.kr
R="--resolve ${HOST}:443:127.0.0.1"
C=/tmp/cookie_dbg.txt
rm -f "$C"
curl -sk $R -c "$C" "https://${HOST}/" -o /tmp/home_dbg.html
echo '=== after GET ==='
cat "$C"
CSRF=$(grep -oP 'name="csrf-token" content="\K[^"]+' /tmp/home_dbg.html | head -1)
curl -sk $R -b "$C" -c "$C" -D /tmp/post_dbg.hdr -X POST "https://${HOST}/index.php?act=procMemberLogin" \
  -H "Origin: https://${HOST}" -H "Referer: https://${HOST}/" -H "Sec-Fetch-Site: same-origin" \
  -H "X-CSRF-Token: ${CSRF}" \
  -d "user_id=baesy69" -d "password=dkagh@6918" \
  -d "xe_validator_id=layouts/xedition/layout/1" \
  -d "success_return_url=https://${HOST}/" -o /tmp/post_dbg.json
echo '=== set-cookie on POST ==='
grep -i set-cookie /tmp/post_dbg.hdr || true
echo '=== after POST jar ==='
cat "$C"
echo '=== POST body ==='
cat /tmp/post_dbg.json
echo
curl -sk $R -b "$C" "https://${HOST}/" -o /tmp/after_dbg.html
echo '=== logged in page markers ==='
grep -c 'cmd_logout\|logged_info\|member_srl' /tmp/after_dbg.html || true
grep 'rx_login_status\|PHPSESSID' /tmp/after_dbg.hdr /tmp/post_dbg.hdr 2>/dev/null || true
