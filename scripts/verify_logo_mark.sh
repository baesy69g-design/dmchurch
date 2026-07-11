#!/bin/bash
touch /root/church-web/html/addons/church_sub_top/church_sub_top.css
touch /root/church-web/html/m.layouts/default/mx.css
curl -sk -A iPhone -H 'Host: dmchurch.kr' https://127.0.0.1/ -o /tmp/mcheck.html
grep -o 'logo_mark[^"]*' /tmp/mcheck.html | head -5
ls -la /root/church-web/html/files/church/logo_mark.png
