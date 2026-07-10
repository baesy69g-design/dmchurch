#!/bin/bash
# HTTPS 로그인 curl 테스트 (성공 여부 + 세션 쿠키 확인)
set -euo pipefail
HOST='dmchurch.kr'
RESOLVE="--resolve ${HOST}:443:127.0.0.1"
COOKIE=/tmp/login_test_cj.txt
HOME=/tmp/login_test_home.html
HDR=/tmp/login_test_hdr.txt
BODY=/tmp/login_test_body.txt
MODE="${3:-browser}"

curl -sk $RESOLVE -c "$COOKIE" "https://${HOST}/" -o "$HOME"
CSRF=$(grep -oP 'name="csrf-token" content="\K[^"]+' "$HOME" | head -1)
echo "csrf=$CSRF"
echo "enforce_ssl=$(grep enforce_ssl "$HOME" | head -1)"

USER_ID="${1:-dmc2241}"
PASS="${2:-wrongpass}"

CURL_EXTRA=()
if [ "$MODE" = "browser" ]; then
  CURL_EXTRA+=(
    -H "Origin: https://${HOST}"
    -H "Referer: https://${HOST}/"
    -H "Sec-Fetch-Site: same-origin"
    -H "Sec-Fetch-Mode: cors"
    -H "X-Requested-With: XMLHttpRequest"
    -H "Accept: application/json, text/javascript, */*; q=0.01"
    -H "X-CSRF-Token: ${CSRF}"
  )
fi

curl -sk $RESOLVE -b "$COOKIE" -c "$COOKIE" -X POST "https://${HOST}/index.php?act=procMemberLogin" \
  "${CURL_EXTRA[@]}" \
  -d "user_id=${USER_ID}" \
  -d "password=${PASS}" \
  -d "xe_validator_id=layouts/xedition/layout/1" \
  -d "success_return_url=https://${HOST}/" \
  -d "error_return_url=https://${HOST}/" \
  -D "$HDR" -o "$BODY"

echo '--- login response ---'
head -12 "$HDR"
head -c 500 "$BODY"
echo
echo '--- cookies ---'
grep -v '^#' "$COOKIE" | grep -v '^$' || true

echo '--- session check ---'
curl -sk $RESOLVE -b "$COOKIE" "https://${HOST}/" -o /tmp/login_test_after.html
grep -E 'rx_login_status|logged_username|cmd_logout|member_srl' /tmp/login_test_after.html | head -5
