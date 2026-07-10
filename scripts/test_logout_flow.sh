#!/bin/bash
set -euo pipefail
BASE='https://dmchurch.kr'
RESOLVE=(--resolve dmchurch.kr:443:127.0.0.1)
COOKIE=/tmp/logout_test_cookies.txt
rm -f "$COOKIE"

echo "=== 1. GET login form (guest) ==="
code=$(curl -sk "${RESOLVE[@]}" -o /tmp/lf1.html -w '%{http_code}' "$BASE/index.php?mid=member&act=dispMemberLoginForm")
echo "http=$code toggle=$(grep -c church-pw-toggle /tmp/lf1.html || true)"
grep -i '서버 오류\|ParseError\|Fatal' /tmp/lf1.html | head -2 || true

echo "=== 2. Login baesy69 ==="
bash /tmp/test_keep_signed_login.sh baesy69 'dkagh@6918' N >/dev/null
cp /tmp/login_keep_N.txt "$COOKIE"

echo "=== 3. GET modify password ==="
code=$(curl -sk "${RESOLVE[@]}" -b "$COOKIE" -o /tmp/mpw.html -w '%{http_code}' "$BASE/index.php?mid=member&act=dispMemberModifyPassword")
echo "http=$code toggles=$(grep -c church-pw-toggle /tmp/mpw.html || true)"

echo "=== 4. Logout ==="
code=$(curl -sk "${RESOLVE[@]}" -b "$COOKIE" -c "$COOKIE" -L -o /tmp/logout.html -w '%{http_code}' "$BASE/index.php?act=procMemberLogout")
echo "logout_http=$code final_url=$(grep -o 'Location:.*' /dev/null 2>/dev/null || echo n/a)"
grep -i '서버 오류\|ParseError\|Fatal' /tmp/logout.html | head -2 || true

echo "=== 5. GET login form after logout ==="
code=$(curl -sk "${RESOLVE[@]}" -b "$COOKIE" -o /tmp/lf2.html -w '%{http_code}' "$BASE/index.php?mid=member&act=dispMemberLoginForm")
echo "http=$code toggle=$(grep -c church-pw-toggle /tmp/lf2.html || true)"
grep -i '서버 오류\|ParseError\|Fatal' /tmp/lf2.html | head -2 || true

echo "=== 6. Homepage after logout ==="
code=$(curl -sk "${RESOLVE[@]}" -b "$COOKIE" -o /tmp/home.html -w '%{http_code}' "$BASE/")
echo "home_http=$code"
