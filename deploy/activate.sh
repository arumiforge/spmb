#!/usr/bin/env bash
# Runs on the server. Activates an uploaded release atomically.
# Usage: activate.sh <deploy_path> <release_name>
#
# Layout:
#   <deploy_path>/releases/<release>/   uploaded code
#   <deploy_path>/shared/.env           production config (created once by hand)
#   <deploy_path>/shared/writable/      logs, cache, sessions, uploads (persistent)
#   <deploy_path>/current -> releases/<release>   Nginx root = current/public
set -euo pipefail

DEPLOY_PATH="$1"
RELEASE="$2"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.3-fpm}"
RELEASE_DIR="$DEPLOY_PATH/releases/$RELEASE"
SHARED="$DEPLOY_PATH/shared"

[ -f "$SHARED/.env" ] || { echo "Missing $SHARED/.env — see docs/deployment.md" >&2; exit 1; }

mkdir -p "$SHARED/writable"/{cache,logs,session,uploads,debugbar}
ln -sfn "$SHARED/.env" "$RELEASE_DIR/.env"
rm -rf "$RELEASE_DIR/writable"
ln -sfn "$SHARED/writable" "$RELEASE_DIR/writable"

cd "$RELEASE_DIR"
php spark migrate --all
php spark optimize || true

ln -sfn "$RELEASE_DIR" "$DEPLOY_PATH/current.tmp"
mv -Tf "$DEPLOY_PATH/current.tmp" "$DEPLOY_PATH/current"

# Requires a sudoers rule: <deploy_user> ALL=NOPASSWD: /usr/bin/systemctl reload php8.3-fpm
sudo -n systemctl reload "$PHP_FPM_SERVICE"

cd "$DEPLOY_PATH/releases"
ls -1dt -- */ | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf --

echo "Activated release $RELEASE"
