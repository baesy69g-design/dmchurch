#!/bin/bash
set -uo pipefail
HOST=dmchurch.kr
R="--resolve ${HOST}:443:127.0.0.1"

bash /tmp/test_keep_signed_login.sh baesy69 'dkagh@6918' N || true
C=/tmp/login_keep_N.txt

echo "=== modify password (logged in) ==="
curl -sk $R -b "$C" "https://${HOST}/index.php?mid=member&act=dispMemberModifyPassword" -o /tmp/mpw.html -w 'http=%{http_code}\n'
echo "pw_toggles=$(grep -c church-pw-toggle /tmp/mpw.html)"

echo "=== logout via dispMemberLogout ==="
curl -sk $R -b "$C" -c "$C" -D /tmp/lo.hdr -o /tmp/lo.html -w 'http=%{http_code}\n' \
  "https://${HOST}/index.php?act=dispMemberLogout"
grep -i '^location:' /tmp/lo.hdr || true
wc -c /tmp/lo.html

echo "=== login form after logout ==="
curl -sk $R -b "$C" "https://${HOST}/index.php?mid=member&act=dispMemberLoginForm" -o /tmp/lf2.html -w 'http=%{http_code}\n'
echo "lf_toggles=$(grep -c church-pw-toggle /tmp/lf2.html)"
if grep -q '서버 오류' /tmp/lf2.html; then echo 'SERVER_ERROR=yes'; else echo 'SERVER_ERROR=no'; fi
if grep -q '관리자 로그인' /tmp/lf2.html; then echo 'ADMIN_LOGIN_WIDGET=yes'; else echo 'ADMIN_LOGIN_WIDGET=no'; fi
