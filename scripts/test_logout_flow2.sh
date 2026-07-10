#!/bin/bash
set -x
BASE='https://dmchurch.kr'
RESOLVE=(--resolve dmchurch.kr:443:127.0.0.1)
COOKIE=/tmp/logout_flow2.txt
rm -f "$COOKIE"

docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/set_member_password.php baesy69 'dkagh@6918' || true

# get CSRF + login
curl -sk "${RESOLVE[@]}" -c "$COOKIE" "$BASE/index.php?mid=member&act=dispMemberLoginForm" -o /tmp/lf_csrf.html
csrf=$(grep -oP 'name="xe_validator_id" value="\K[^"]+' /tmp/lf_csrf.html | head -1)
curl -sk "${RESOLVE[@]}" -b "$COOKIE" -c "$COOKIE" -X POST "$BASE/" \
  -d "act=procMemberLogin" \
  -d "user_id=baesy69" \
  -d "password=dkagh@6918" \
  -d "xe_validator_id=$csrf" \
  -d "success_return_url=$BASE/" \
  -o /tmp/login_resp.json
echo "login_resp=$(cat /tmp/login_resp.json)"

echo "=== logout ==="
curl -sk "${RESOLVE[@]}" -b "$COOKIE" -c "$COOKIE" -L -o /tmp/logout.html -w 'code=%{http_code}\n' "$BASE/index.php?act=procMemberLogout"

echo "=== login form after logout ==="
curl -sk "${RESOLVE[@]}" -b "$COOKIE" -o /tmp/lf_after.html -w 'code=%{http_code}\n' "$BASE/index.php?mid=member&act=dispMemberLoginForm"
grep -c church-pw-toggle /tmp/lf_after.html || true
grep '서버 오류' /tmp/lf_after.html || echo 'no server error text'
grep 'msg_administrator_login\|관리자 로그인' /tmp/lf_after.html || echo 'no admin login msg'
