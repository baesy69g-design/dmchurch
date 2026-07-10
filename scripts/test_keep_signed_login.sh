#!/bin/bash
# keep_signed 유무에 따른 로그인 세션 테스트
set -euo pipefail
HOST='dmchurch.kr'
RESOLVE="--resolve ${HOST}:443:127.0.0.1"
USER_ID="${1:-baesy69}"
PASS="${2:-}"
KEEP="${3:-N}"
COOKIE="/tmp/login_keep_${KEEP}.txt"
HOME="/tmp/login_keep_home.html"
BODY="/tmp/login_keep_body.json"
HDR="/tmp/login_keep_hdr.txt"

if [ -z "$PASS" ]; then echo "usage: $0 USER PASS [Y|N]"; exit 1; fi

curl -sk $RESOLVE -c "$COOKIE" "https://${HOST}/" -o "$HOME"
CSRF=$(grep -oP 'name="csrf-token" content="\K[^"]+' "$HOME" | head -1)

POST_DATA=(
  -d "user_id=${USER_ID}"
  -d "password=${PASS}"
  -d "xe_validator_id=layouts/xedition/layout/1"
  -d "success_return_url=https://${HOST}/"
  -d "error_return_url=https://${HOST}/"
)
if [ "$KEEP" = "Y" ]; then
  POST_DATA+=(-d "keep_signed=Y")
fi

curl -sk $RESOLVE -b "$COOKIE" -c "$COOKIE" -X POST "https://${HOST}/index.php?act=procMemberLogin" \
  -H "Origin: https://${HOST}" \
  -H "Referer: https://${HOST}/" \
  -H "Sec-Fetch-Site: same-origin" \
  -H "X-Requested-With: XMLHttpRequest" \
  -H "Accept: application/json, text/javascript, */*; q=0.01" \
  -H "X-CSRF-Token: ${CSRF}" \
  "${POST_DATA[@]}" \
  -D "$HDR" -o "$BODY"

echo "=== keep_signed=${KEEP} ==="
grep -i set-cookie "$HDR" || true
echo "--- response ---"
cat "$BODY"
echo
echo "--- cookies after login ---"
grep -v '^#' "$COOKIE" | grep -v '^$' || true
echo "--- homepage session markers ---"
curl -sk $RESOLVE -b "$COOKIE" "https://${HOST}/" -o /tmp/login_keep_after.html
grep -E 'rx_login_status|cmd_logout|logged|member_srl|baesy69|dmc2241' /tmp/login_keep_after.html | head -8
wc -c /tmp/login_keep_after.html
