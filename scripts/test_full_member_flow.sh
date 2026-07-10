#!/bin/bash
set -euo pipefail
HOST=dmchurch.kr
C=/tmp/full_flow.txt
rm -f "$C"

docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/set_member_password.php baesy69 dkagh@6918 >/dev/null

curl -sk --resolve "${HOST}:443:127.0.0.1" -c "$C" "https://${HOST}/" -o /tmp/home.html
CSRF=$(grep -oP 'name="csrf-token" content="\K[^"]+' /tmp/home.html | head -1)

curl -sk --resolve "${HOST}:443:127.0.0.1" -b "$C" -c "$C" -X POST "https://${HOST}/index.php?act=procMemberLogin" \
  -H "Origin: https://${HOST}" -H "Referer: https://${HOST}/" \
  -H "X-CSRF-Token: ${CSRF}" \
  -d "user_id=baesy69" -d "password=dkagh@6918" \
  -d "xe_validator_id=layouts/xedition/layout/1" \
  -d "success_return_url=https://${HOST}/" -o /tmp/login.json
echo login_ok
grep error /tmp/login.json | head -1

echo modify_pw
curl -sk --resolve "${HOST}:443:127.0.0.1" -b "$C" \
  "https://${HOST}/index.php?mid=member&act=dispMemberModifyPassword" -o /tmp/mpw.html -w 'code=%{http_code}\n'
echo toggles=$(grep -c church-pw-toggle /tmp/mpw.html)

echo logout
curl -sk --resolve "${HOST}:443:127.0.0.1" -b "$C" -c "$C" -D /tmp/logout.hdr -o /tmp/logout_body.html -w 'code=%{http_code}\n' \
  "https://${HOST}/index.php?act=dispMemberLogout"
grep -i '^location:' /tmp/logout.hdr || true

echo login_form
curl -sk --resolve "${HOST}:443:127.0.0.1" -b "$C" \
  "https://${HOST}/index.php?mid=member&act=dispMemberLoginForm" -o /tmp/lf.html -w 'code=%{http_code}\n'
echo toggles=$(grep -c church-pw-toggle /tmp/lf.html)
grep -c 'server error\|서버 오류' /tmp/lf.html || echo err_count=0
