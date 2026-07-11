#!/bin/bash
# Probe get document endpoint
curl -sk -H 'Host: dmchurch.kr' -A 'Mozilla/5.0' \
  'https://127.0.0.1/index.php?module=church_write&act=dispChurchWriteGetDocument&document_srl=900328&module_srl=114' \
  -D /tmp/getdoc.hdr -o /tmp/getdoc.body
echo '=== headers ==='
head -20 /tmp/getdoc.hdr
echo '=== body start ==='
head -c 400 /tmp/getdoc.body
echo
echo '=== CHURCH_BOARD_UI get_url on jubo ==='
curl -sk -H 'Host: dmchurch.kr' 'https://127.0.0.1/jubo' | tr '"' '\n' | grep -E 'get_url|update_url|dispChurchWriteGet' | head
