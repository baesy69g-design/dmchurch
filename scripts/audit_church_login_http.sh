#!/bin/bash
set -uo pipefail
HOST=dmchurch.kr
R=(--resolve "${HOST}:443:127.0.0.1)
PASS=0
FAIL=0

check() {
  local label="$1"
  local cond="$2"
  if [ "$cond" = "0" ]; then
    echo "[OK] $label"
    PASS=$((PASS + 1))
  else
    echo "[FAIL] $label"
    FAIL=$((FAIL + 1))
  fi
}

echo "=== HTTP checks ==="
code=$(curl -sk "${R[@]}" -o /dev/null -w '%{http_code}' "https://${HOST}/")
check "homepage" "$(( code != 200 ))"

loc=$(curl -sk "${R[@]}" -D - -o /dev/null "https://${HOST}/index.php?mid=member&act=dispMemberLoginForm" | grep -i '^location:' | tr -d '\r' || true)
echo "login redirect: $loc"
if echo "$loc" | grep -qi 'church_login=1'; then
  check "login form redirect" "0"
else
  check "login form redirect" "1"
fi

body=$(curl -sk "${R[@]}" "https://${HOST}/")
echo "$body" | grep -q 'church_login_widget.js' && c1=0 || c1=1
check "login widget js" "$c1"

echo "$body" | grep -q 'church_login_uid' && c2=0 || c2=1
check "login uid field" "$c2"

code=$(curl -sk "${R[@]}" -o /dev/null -w '%{http_code}' "https://${HOST}/index.php?module=church_member&act=dispChurchRecoverAccount")
check "recover page" "$(( code != 200 ))"

echo ""
echo "=== PHP audit ==="
docker exec church-rhymix php /var/www/vhosts/localhost/html/scripts/audit_church_login_flow.php || true

echo ""
echo "Summary OK=$PASS FAIL=$FAIL"
[ "$FAIL" -eq 0 ]
