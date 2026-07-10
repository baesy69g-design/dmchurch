#!/bin/bash
# VPS 사전 설정: nginx, fail2ban, ufw, docker 포트 8000
set -eu

echo "=== 1) docker-compose 포트 변경 (8000, localhost only) ==="
cd /root/church-web
cp docker-compose.yml "docker-compose.yml.bak.$(date +%Y%m%d_%H%M%S)"

sed -i 's/"3306:3306"/"127.0.0.1:3306:3306"/' docker-compose.yml
sed -i 's/"8080:80"/"127.0.0.1:8000:80"/' docker-compose.yml

echo "--- docker-compose.yml ---"
cat docker-compose.yml

docker compose up -d
sleep 3
docker ps --format 'table {{.Names}}\t{{.Ports}}'

echo "=== 2) 패키지 설치 ==="
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq nginx fail2ban certbot python3-certbot-nginx

echo "=== 3) fail2ban SSH 보호 ==="
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime  = 1h
findtime = 10m
maxretry = 5
banaction = ufw
backend = systemd

[sshd]
enabled = true
port    = ssh
filter  = sshd
maxretry = 5
bantime  = 24h
EOF

systemctl enable fail2ban
systemctl restart fail2ban

echo "=== 4) nginx upstream + 사이트 설정 ==="
mkdir -p /etc/nginx/conf.d /etc/nginx/snippets

cat > /etc/nginx/conf.d/00-upstreams.conf << 'EOF'
# 교회 홈페이지 (Rhymix / OpenLiteSpeed)
upstream church_backend {
    server 127.0.0.1:8000;
    keepalive 16;
}

# 향후 웹 애플리케이션용 (컨테이너/서비스 바인딩 후 server 블록 활성화)
upstream app_backend_5000 {
    server 127.0.0.1:5000;
    keepalive 8;
}

upstream app_backend_6000 {
    server 127.0.0.1:6000;
    keepalive 8;
}
EOF

cat > /etc/nginx/snippets/proxy-common.conf << 'EOF'
proxy_http_version 1.1;
proxy_set_header Host $host;
proxy_set_header X-Real-IP $remote_addr;
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto $scheme;
proxy_set_header Connection "";
proxy_connect_timeout 60s;
proxy_send_timeout 120s;
proxy_read_timeout 120s;
client_max_body_size 64m;
EOF

cat > /etc/nginx/sites-available/dmchurch.kr << 'EOF'
# dmchurch.kr — DNS 전환 후: certbot --nginx -d dmchurch.kr -d www.dmchurch.kr
server {
    listen 80;
    listen [::]:80;
    server_name dmchurch.kr www.dmchurch.kr;

    location / {
        proxy_pass http://church_backend;
        include snippets/proxy-common.conf;
    }
}

# IP 직접 접속 (DNS 전환 전 테스트용)
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name 49.247.205.159 _;

    location / {
        proxy_pass http://church_backend;
        include snippets/proxy-common.conf;
    }
}
EOF

cat > /etc/nginx/sites-available/_future-app-template.conf << 'EOF'
# 사용법: server_name·upstream 이름 수정 후
#   cp /etc/nginx/sites-available/_future-app-template.conf /etc/nginx/sites-available/app-NAME.conf
#   (server 블록 주석 해제·수정)
#   ln -sf /etc/nginx/sites-available/app-NAME.conf /etc/nginx/sites-enabled/
#   nginx -t && systemctl reload nginx
#
# server {
#     listen 80;
#     server_name app.example.com;
#     location / {
#         proxy_pass http://app_backend_5000;
#         include snippets/proxy-common.conf;
#     }
# }
EOF

rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/dmchurch.kr /etc/nginx/sites-enabled/dmchurch.kr

nginx -t
systemctl enable nginx
systemctl restart nginx

echo "=== 5) UFW 방화벽 (22, 80, 443만 허용) ==="
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
ufw --force enable
ufw status verbose

echo "=== 6) 상태 확인 ==="
ss -tlnp | grep -E ':22|:80|:443|:8000|:3306|:8080' || true
curl -s -o /dev/null -w "HTTP %{http_code} via nginx:80\n" http://127.0.0.1/
curl -s -o /dev/null -w "HTTP %{http_code} direct:8000\n" http://127.0.0.1:8000/
fail2ban-client status sshd 2>/dev/null || fail2ban-client status

echo "=== 완료 ==="
