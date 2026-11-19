#!/usr/bin/env bash
# One-time server preparation for one SPMB installation (docs/deployment.md, "Server preparation").
# Run as root on a fresh Ubuntu Server 24.04. Safe to run again (idempotent).
#
# Usage:
#   sudo DOMAIN=spmbsmpnualmarufkudus.web.id bash provision.sh
#
# Optional variables: DEPLOY_USER (deploy), DEPLOY_PATH (/var/www/spmb), DB_NAME (spmb), DB_USER (spmb),
# KEEP_SSH_OPEN (auto; 0 = never add the temporary SSH rule described below).
# It installs packages, creates the deploy user, folders, database, shared .env, Nginx site and
# PHP limits. Traffic arrives through Cloudflare Tunnel (`cloudflared service install <token>`,
# token from the Cloudflare dashboard, step 7 in the docs), so the firewall denies all inbound ports.
#
# Lock-out guards (D-065):
# - While the tunnel is not running yet, the port(s) sshd listens on (22 or a provider's custom port)
#   stay open in the firewall; close them by hand once SSH through the tunnel works.
# - Password login is switched off only when root or a sudo user already has an SSH key.
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

# Port(s) sshd listens on: from ssh.socket (Ubuntu 24.04 socket activation) or from sshd's config.
ssh_ports() {
    if systemctl is-active --quiet ssh.socket 2>/dev/null; then
        systemctl show -p Listen --value ssh.socket | grep -oE ':[0-9]+ ' | tr -d ': '
    else
        sshd -T 2>/dev/null | awk '$1 == "port" {print $2}'
    fi | sort -un
}
SSH_PORTS="$(ssh_ports | tr '\n' ' ' || true)"
SSH_PORTS="${SSH_PORTS% }"
SSH_PORTS="${SSH_PORTS:-22}"

echo "== Firewall: no inbound ports (Cloudflare Tunnel only)"
ufw default deny incoming >/dev/null
ufw default allow outgoing >/dev/null
TEMP_SSH=0
if [ "${KEEP_SSH_OPEN:-auto}" != "0" ] && ! systemctl is-active --quiet cloudflared 2>/dev/null; then
    for port in $SSH_PORTS; do
        ufw allow "$port/tcp" comment 'spmb: temporary SSH until the tunnel works' >/dev/null
    done
    TEMP_SSH=1
    echo "   Tunnel not running yet: SSH port(s) $SSH_PORTS stay open for now."
fi
ufw --force enable >/dev/null

echo "== Deploy user and folders"
id "$DEPLOY_USER" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$DEPLOY_USER"
usermod -aG www-data "$DEPLOY_USER"
mkdir -p "$DEPLOY_PATH"/{releases,shared/writable/{cache,logs,session,uploads,debugbar}}
chown -R "$DEPLOY_USER":www-data "$DEPLOY_PATH"
chmod -R 2775 "$DEPLOY_PATH/shared/writable"
echo "$DEPLOY_USER ALL=NOPASSWD: /usr/bin/systemctl reload php$PHP-fpm" > /etc/sudoers.d/spmb-deploy
chmod 0440 /etc/sudoers.d/spmb-deploy

echo "== SSH: keys only"
# A key line starts with its type; lines with options (e.g. a provider's command="..." stub) do not count.
has_admin_key() {
    local user home
    for user in root $(getent group sudo | cut -d: -f4 | tr ',' ' '); do
        home="$(getent passwd "$user" | cut -d: -f6)"
        [ -n "$home" ] && grep -qsE '^(ssh-|ecdsa-|sk-)' "$home/.ssh/authorized_keys" && return 0
    done
    return 1
}
# Drop-in named 00-* so it wins over 50-cloud-init.conf (sshd keeps the first value it reads).
SSHD_DROPIN=/etc/ssh/sshd_config.d/00-spmb.conf
if has_admin_key; then
    printf 'PasswordAuthentication no\nKbdInteractiveAuthentication no\n' > "$SSHD_DROPIN"
    if sshd -t; then
        systemctl reload ssh 2>/dev/null || true
        echo "   Password login off ($SSHD_DROPIN)."
    else
        rm -f "$SSHD_DROPIN"
        echo "   WARNING: sshd rejected $SSHD_DROPIN; removed it, SSH settings unchanged." >&2
    fi
else
    echo "   WARNING: neither root nor a sudo user has an SSH key; password login left ON." >&2
    echo "   Add your public key to ~/.ssh/authorized_keys, then run this script again." >&2
fi

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
echo "    Public hostnames: $DOMAIN → http://127.0.0.1:80, and ssh.$DOMAIN → ssh://127.0.0.1:${SSH_PORTS%% *} (behind Access)."
if [ "$TEMP_SSH" = 1 ]; then
    echo "    When SSH through the tunnel works, close the temporary rule(s):"
    for port in $SSH_PORTS; do echo "      ufw delete allow $port/tcp"; done
fi
echo " 2. Add the CI public key to /home/$DEPLOY_USER/.ssh/authorized_keys."
echo " 3. Run the Deploy workflow on GitHub, then in $DEPLOY_PATH/current:"
echo "    sudo -u $DEPLOY_USER php spark spmb:install"
echo "    sudo -u $DEPLOY_USER php spark spmb:branding clients/<school>/branding"
