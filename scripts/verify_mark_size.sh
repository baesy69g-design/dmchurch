#!/bin/bash
touch /root/church-web/html/addons/church_sub_top/church_sub_top.css
touch /root/church-web/html/m.layouts/default/mx.css
grep -n 'max-height: none\|1\.2em\|church-brand-main' /root/church-web/html/addons/church_sub_top/church_sub_top.css | head -20
curl -sk -A 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36' -H 'Host: dmchurch.kr' 'https://127.0.0.1/?m=0' -o /tmp/pc_m2.html
grep -o 'church-brand-main\|1\.2em\|logo_mark.png[^"]*' /tmp/pc_m2.html | head -10
