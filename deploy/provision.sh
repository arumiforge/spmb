#!/usr/bin/env bash
# One-time server preparation for one SPMB installation (docs/deployment.md, "Server preparation").
# Run as root on a fresh Ubuntu Server 24.04. Safe to run again (idempotent).
#
# Usage:
#   sudo DOMAIN=spmbsmpnualmarufkudusweb.id bash provision.sh
#
# Optional variables: DEPLOY_USER (deploy), DEPLOY_PATH (/var/www/spmb), DB_NAME (spmb), DB_USER (spmb).
# It installs packages, creates the deploy user, folders, database, shared .env, Nginx site and
# PHP limits. It does NOT open any inbound port: traffic arrives through Cloudflare Tunnel
# (`cloudflared service install <token>`, token from the Cloudflare dashboard, step 7 in the docs).
set -euo pipefail

DOMAIN="${DOMAIN:?Set DOMAIN, e.g. DOMAIN=example.sch.id}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"
DEPLOY_PATH="${DEPLOY_PATH:-/var/www/spmb}"
DB_NAME="${DB_NAME:-spmb}"
DB_USER="${DB_USER:-spmb}"
PHP=8.3

[ "$(id -u)" -eq 0 ] || { echo "Run as root (sudo)." >&2; exit 1; }

echo "== Packages"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q
apt-get install -y -q nginx mysql-server unzip curl ufw \
    "php$PHP-fpm" "php$PHP-cli" "php$PHP-intl" "php$PHP-mbstring" "php$PHP-mysql" \
    "php$PHP-gd" "php$PHP-zip" "php$PHP-xml" "php$PHP-curl"

if ! command -v cloudflared >/dev/null; then
    mkdir -p --mode=0755 /usr/share/keyrings
    curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg -o /usr/share/keyrings/cloudflare-main.gpg
    echo "deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared any main" \
        > /etc/apt/sources.list.d/cloudflared.list
    apt-get update -q && apt-get install -y -q cloudflared
fi

echo "== Firewall: no inbound ports (Cloudflare Tunnel only)"
ufw default deny incoming >/dev/null
ufw default allow outgoing >/dev/null
ufw --force enable >/dev/null

echo "== Deploy user and folders"
id "$DEPLOY_USER" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$DEPLOY_USER"
usermod -aG www-data "$DEPLOY_USER"
mkdir -p "$DEPLOY_PATH"/{releases,shared/writable/{cache,logs,session,uploads,debugbar}}
chown -R "$DEPLOY_USER":www-data "$DEPLOY_PATH"
chmod -R 2775 "$DEPLOY_PATH/shared/writable"
echo "$DEPLOY_USER ALL=NOPASSWD: /usr/bin/systemctl reload php$PHP-fpm" > /etc/sudoers.d/spmb-deploy
chmod 0440 /etc/sudoers.d/spmb-deploy
sed -i 's/^#\?PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config
systemctl reload ssh || true

echo "== Database"
if [ ! -f "$DEPLOY_PATH/shared/.db-password" ]; then
    openssl rand -base64 24 | tr -d '/+=' > "$DEPLOY_PATH/shared/.db-password"
    chmod 600 "$DEPLOY_PATH/shared/.db-password"
fi
DB_PASS="$(cat "$DEPLOY_PATH/shared/.db-password")"
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "== Shared .env"
ENV_FILE="$DEPLOY_PATH/shared/.env"
if [ ! -f "$ENV_FILE" ]; then
    KEY="hex2bin:$(openssl rand -hex 32)"
    cat > "$ENV_FILE" <<ENV
CI_ENVIRONMENT = production
app.baseURL = 'https://$DOMAIN/'
database.default.hostname = localhost
database.default.database = $DB_NAME
database.default.username = $DB_USER
database.default.password = $DB_PASS
database.default.DBDriver = MySQLi
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci
encryption.key = $KEY
ENV
    chown "$DEPLOY_USER":www-data "$ENV_FILE"
    chmod 640 "$ENV_FILE"
    echo "   Created $ENV_FILE (keep a copy of encryption.key off the server)."
else
    echo "   $ENV_FILE exists; left unchanged."
fi

echo "== PHP limits (phone photos up to 10 MB, D-043)"
cat > "/etc/php/$PHP/fpm/conf.d/90-spmb.ini" <<INI
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
expose_php = Off
date.timezone = Asia/Jakarta
INI
systemctl restart "php$PHP-fpm"

echo "== Nginx"
cat > /etc/nginx/sites-available/spmb <<NGINX
server {
    listen 127.0.0.1:80;
    server_name $DOMAIN;
    root $DEPLOY_PATH/current/public;
    index index.php;
    client_max_body_size 12m;

    # Real visitor IP from Cloudflare (traffic arrives through the tunnel on localhost).
    set_real_ip_from 127.0.0.1;
    real_ip_header CF-Connecting-IP;

    location ~ /\.(?!well-known) { deny all; }
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHP-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
    }
}
NGINX
ln -sfn /etc/nginx/sites-available/spmb /etc/nginx/sites-enabled/spmb
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo
echo "Done. Next (docs/deployment.md, 'Go-live'):"
echo " 1. Cloudflare Zero Trust → Tunnels: create a tunnel, run the shown 'cloudflared service install <token>'."
echo "    Public hostnames: $DOMAIN → http://localhost:80, and an SSH hostname → ssh://localhost:22 (behind Access)."
echo " 2. Add the CI public key to /home/$DEPLOY_USER/.ssh/authorized_keys."
echo " 3. Run the Deploy workflow on GitHub, then in $DEPLOY_PATH/current: php spark spmb:install"
echo "    and php spark spmb:branding clients/<school>/branding"
