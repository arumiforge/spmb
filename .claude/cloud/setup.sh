#!/usr/bin/env bash
# SPMB — setup script for a Claude Code cloud environment (any Claude account).
#
# The environment's "Setup script" field only needs this bootstrap (docs/cloud-environment.md):
#
#   if [ -f spmb/.claude/cloud/setup.sh ]; then bash spmb/.claude/cloud/setup.sh; else echo "spmb/.claude/cloud/setup.sh not found"; fi
#
# It runs as root after the repository is cloned, from the folder that holds the checkout
# (so the repository is ./spmb). It installs PHP 8.5.13+ and Node.js 24 (D-069) and MySQL,
# creates the dev and test databases, installs dependencies, builds the frontend and runs
# `spmb:install --demo`. Safe to run again. Never put secrets in this file.
set -e
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq software-properties-common mysql-server
# PHP 8.5.13+ (D-069) from the ondrej/php PPA
add-apt-repository -y ppa:ondrej/php
apt-get install -y -qq php8.5-cli php8.5-intl php8.5-mbstring php8.5-mysql php8.5-gd php8.5-zip php8.5-xml php8.5-curl
update-alternatives --set php /usr/bin/php8.5
# Node.js 24 (D-069) from NodeSource; point the image's own node links at it
curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
apt-get install -y -qq nodejs
for d in /opt/node22/bin /usr/local/bin; do
  for b in node npm npx; do
    if [ -e "$d/$b" ]; then ln -sf "/usr/bin/$b" "$d/$b"; fi
  done
done
service mysql start
mysql -e "CREATE DATABASE IF NOT EXISTS spmb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -e "CREATE DATABASE IF NOT EXISTS spmb_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -e "CREATE USER IF NOT EXISTS 'spmb'@'localhost' IDENTIFIED BY 'spmb'"
mysql -e "GRANT ALL ON spmb.* TO 'spmb'@'localhost'; GRANT ALL ON spmb_test.* TO 'spmb'@'localhost'"
if [ -d spmb ]; then
  cd spmb
  export COMPOSER_ALLOW_SUPERUSER=1
  composer install --no-interaction --no-progress
  npm ci --no-audit --no-fund
  npm run build
  if [ ! -f .env ]; then
    cp env .env
    cat >> .env <<'ENV'
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'
database.default.hostname = 127.0.0.1
database.default.database = spmb
database.default.username = spmb
database.default.password = spmb
database.default.DBDriver = MySQLi
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci
database.tests.hostname = 127.0.0.1
database.tests.database = spmb_test
database.tests.username = spmb
database.tests.password = spmb
database.tests.DBDriver = MySQLi
database.tests.charset = utf8mb4
database.tests.DBCollat = utf8mb4_unicode_ci
ENV
    php spark key:generate
  fi
  # Dev Super Admin gets a random password, kept only inside this container.
  pw="Dev-$(openssl rand -hex 8)-2027!"
  if php spark spmb:install --demo --username superadmin --email super@demo.test \
      --name "Pengelola Sistem" --password "$pw" </dev/null; then
    echo "superadmin / super@demo.test : $pw" > /root/spmb-superadmin.txt
  else
    echo "spmb:install failed; run it manually (docs/development.md)"
  fi
  cd ..
fi
