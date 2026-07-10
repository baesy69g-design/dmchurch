#!/bin/sh
cd /var/www/vhosts/localhost/html || exit 1
php scripts/make_session_cookie.php baesy69 dkagh@6918 /tmp/sess.txt
curl -sk -b /tmp/sess.txt \
  "https://127.0.0.1/index.php?module=church_member&act=dispChurchMemberProfile" \
  -H "Host: dmchurch.kr" -o /tmp/profile_http.html
grep -E 'church-member-heading|church_profile_form|msg_invalid|Error #|Fatal|ERR_' /tmp/profile_http.html | head -8
wc -c /tmp/profile_http.html
