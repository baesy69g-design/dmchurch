#!/bin/bash
# 교회 홈페이지 공개 접속 on/off (nginx)
# 사용: church_site_toggle.sh off | on | status
set -euo pipefail

LIVE="/etc/nginx/sites-available/dmchurch.kr.live"
MAINT="/etc/nginx/sites-available/dmchurch.kr.maintenance"
ENABLED="/etc/nginx/sites-enabled/dmchurch.kr"
CERT="/etc/letsencrypt/live/dmchurch.kr/fullchain.pem"
ACTION="${1:-status}"

MAINT_HTML='<!DOCTYPE html><html lang="ko"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>준비 중</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;background:#f6f6f6;color:#444}main{text-align:center;padding:24px}</style></head><body><main><h1>준비 중입니다</h1><p>잠시 후 다시 방문해 주세요.</p></main></body></html>'

write_maintenance_config() {
	if [ -f "$CERT" ]; then
		cat > "$MAINT" <<EOF
# dmchurch.kr — 임시 접속 차단 (church_site_toggle.sh on 으로 복구)
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name dmchurch.kr www.dmchurch.kr;

    ssl_certificate /etc/letsencrypt/live/dmchurch.kr/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/dmchurch.kr/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    default_type text/html;
    charset utf-8;

    location / {
        return 503 '${MAINT_HTML}';
    }
}

server {
    listen 80;
    listen [::]:80;
    server_name dmchurch.kr www.dmchurch.kr;
    return 301 https://\$host\$request_uri;
}

server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name 49.247.205.159 _;

    default_type text/html;
    charset utf-8;

    location / {
        return 503 '${MAINT_HTML}';
    }
}
EOF
	else
		cat > "$MAINT" <<EOF
# dmchurch.kr — 임시 접속 차단 (church_site_toggle.sh on 으로 복구)
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name dmchurch.kr www.dmchurch.kr 49.247.205.159 _;

    default_type text/html;
    charset utf-8;

    location / {
        return 503 '${MAINT_HTML}';
    }
}
EOF
	fi
}

case "$ACTION" in
	off)
		write_maintenance_config
		ln -sf "$MAINT" "$ENABLED"
		nginx -t
		systemctl reload nginx
		echo "church site: OFF (maintenance 503)"
		;;
	on)
		if [ ! -f "$LIVE" ]; then
			echo "missing live config: $LIVE" >&2
			exit 1
		fi
		ln -sf "$LIVE" "$ENABLED"
		nginx -t
		systemctl reload nginx
		echo "church site: ON (live)"
		;;
	status)
		if [ -L "$ENABLED" ]; then
			target="$(readlink -f "$ENABLED")"
			if [[ "$target" == *maintenance* ]]; then
				echo "church site: OFF (maintenance)"
			else
				echo "church site: ON (live)"
			fi
		else
			echo "church site: unknown (no symlink)"
		fi
		if [ -f "$CERT" ]; then
			echo "https: ready (expires $(openssl x509 -enddate -noout -in "$CERT" | cut -d= -f2))"
		else
			echo "https: not configured"
		fi
		;;
	*)
		echo "usage: $0 off|on|status" >&2
		exit 1
		;;
esac
