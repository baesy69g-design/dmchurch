#!/bin/bash
grep -n 'church-brand-sub-ch\|대한예수교장로회' /root/church-web/html/modules/dmcadmin/dmcadmin.model.php | head -10
curl -sL http://127.0.0.1:8000/ | grep -c 'church-brand-sub-ch'
curl -sL http://127.0.0.1:8000/ | grep -o 'brand-sub[^"]*' | head -5
