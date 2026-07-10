#!/bin/bash
# www 서브도메인 로그인 테스트
HOST='www.dmchurch.kr'
RESOLVE="--resolve ${HOST}:443:127.0.0.1"
COOKIE=/tmp/www_login_cj.txt
curl -sk $RESOLVE -c "$COOKIE" "https://${HOST}/" -o /tmp/www_home.html
CSRF=$(grep -oP 'name="csrf-token" content="\K[^"]+' /tmp/www_home.html | head -1)
echo "csrf=$CSRF default_url=$(grep default_url /tmp/www_home.html | head -1)"
curl -sk $RESOLVE -b "$COOKIE" -c "$COOKIE" -X POST "https://${HOST}/index.php?act=procMemberLogin" \
  -H "Origin: https://${HOST}" -H "Referer: https://${HOST}/" -H "Sec-Fetch-Site: same-origin" \
  -H "X-CSRF-Token: ${CSRF}" \
  -d "user_id=dmc2241" -d "password=dmchurch2026!" \
  -d "xe_validator_id=layouts/xedition/layout/1" \
  -d "success_return_url=https://${HOST}/" -o /tmp/www_login.json
cat /tmp/www_login.json
echo
curl -sk $RESOLVE -b "$COOKIE" "https://${HOST}/" | grep -E 'member_srl|cmd_logout|rx_login' | head -5
