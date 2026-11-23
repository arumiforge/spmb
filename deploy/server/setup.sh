#!/usr/bin/env bash
# One-time preparation of a fresh Ubuntu Server 24.04 for Docker app stacks (D-066).
# Run as root. Safe to run again (idempotent). docs/deployment.md, "First server".
#
#   sudo DOMAIN=example.sch.id ADMIN_USER=arumi bash setup.sh
#
# Variables (all optional):
#   DOMAIN               main domain; used for the Cockpit origin and the printed next steps
#   CONSOLE_HOST         Cockpit hostname (default konsol.$DOMAIN)
#   ADMIN_USER           sudo account to create for Cockpit (Cockpit refuses root); asks for
#                        its password when run in a terminal
#   SWAP_SIZE            swap file size when the server has no swap (default 2G; 0 = none)
#   KEEP_SSH_OPEN        auto (default) | 0 — see "Lock-out guards" (0 = do not add the SSH rule)
#   SSH_PASSWORD_LOGIN   auto (default) | keep — "keep" never touches sshd's password setting
#
# What it does: base packages, unattended security updates (no automatic reboot), swap,
# journald and Docker log limits, Docker Engine + compose plugin (Docker's apt repository,
# live-restore), cloudflared (Cloudflare's apt repository; the tunnel token is added later),
# Cockpit on 127.0.0.1:9090 only, firewall (deny incoming), /opt/apps with the port list.
#
# Lock-out guards (D-065, D-066) — the server has no provider console, so losing SSH means
# losing the server:
# - Every port sshd listens on (22 or the provider's custom port) stays open in the firewall for
#   good (D-077): with no provider console, direct SSH with a key is the last way in when the
#   tunnel is down. The port of the SSH session running this script is always kept open too.
# - Password login is switched off only when root or a sudo user has an SSH key and the SSH
#   session running this script signed in with a key (checked in sshd's log), and only through
#   a drop-in that sshd validates first (`sshd -t`); otherwise it is left on.
# - Nothing here restarts sshd or changes the network configuration; NetworkManager is not
#   installed (Cockpit is installed without recommends).
set -Eeuo pipefail

DOMAIN="${DOMAIN:-}"
CONSOLE_HOST="${CONSOLE_HOST:-${DOMAIN:+konsol.$DOMAIN}}"
ADMIN_USER="${ADMIN_USER:-}"
SWAP_SIZE="${SWAP_SIZE:-2G}"
KEEP_SSH_OPEN="${KEEP_SSH_OPEN:-auto}"
SSH_PASSWORD_LOGIN="${SSH_PASSWORD_LOGIN:-auto}"
APPS_DIR=/opt/apps

step() { printf '\n== %s\n' "$*"; }
say()  { printf '   %s\n' "$*"; }
warn() { printf '   WARNING: %s\n' "$*" >&2; NOTES+=("$*"); }
NOTES=()

[ "$(id -u)" -eq 0 ] || { echo "Run as root (sudo bash setup.sh)." >&2; exit 1; }
# shellcheck disable=SC1091
. /etc/os-release
if [ "${ID:-}" != ubuntu ] || [ "${VERSION_ID:-}" != 24.04 ]; then
    warn "Written for Ubuntu 24.04; this is ${PRETTY_NAME:-unknown}. Continuing."
fi
CODENAME="${UBUNTU_CODENAME:-${VERSION_CODENAME:-noble}}"
ARCH="$(dpkg --print-architecture)"
export DEBIAN_FRONTEND=noninteractive

virt="$(systemd-detect-virt 2>/dev/null || true)"
case "$virt" in
    lxc|lxc-libvirt|openvz)
        warn "This server is a '$virt' container: Docker, swap and the firewall may not work here." ;;
esac

# A fresh server runs unattended-upgrades soon after boot and holds the dpkg lock; wait for it
# instead of failing (never kill it: an interrupted dpkg leaves packages half-installed).
wait_for_apt() {
    local waited=0
    # Match the worker (/usr/bin/unattended-upgrade), not the always-running
    # unattended-upgrade-shutdown service, whose process name is also "unattended-upgr".
    while pgrep -f 'bin/unattended-upgrade( |$)' >/dev/null || pgrep -x apt-get >/dev/null \
        || pgrep -x apt >/dev/null || pgrep -x dpkg >/dev/null; do
        [ "$waited" -eq 0 ] && say "Another package job is running (automatic updates?); waiting for it to finish..."
        waited=$((waited + 10))
        [ "$waited" -ge 1800 ] && { echo "Package manager still busy after 30 minutes; try again later." >&2; exit 1; }
        sleep 10
    done
}
apt_get() { wait_for_apt; apt-get -o DPkg::Lock::Timeout=600 "$@"; }

# ---------------------------------------------------------------------------------------
step "Base packages"
apt_get update -q
apt_get install -y -q --no-install-recommends \
    ca-certificates curl gnupg git openssl ufw unattended-upgrades apt-transport-https \
    rsync less nano htop jq cron
timedatectl set-timezone Asia/Jakarta 2>/dev/null || true

step "Security updates (automatic, no automatic reboot)"
cat > /etc/apt/apt.conf.d/20auto-upgrades <<'APT'
APT::Periodic::Update-Package-Lists "1";
APT::Periodic::Unattended-Upgrade "1";
APT::Periodic::AutocleanInterval "7";
APT
cat > /etc/apt/apt.conf.d/52spmb-unattended-upgrades <<'APT'
// Security updates only (the Ubuntu default origins); reboots are done by hand.
Unattended-Upgrade::Automatic-Reboot "false";
Unattended-Upgrade::Remove-Unused-Dependencies "true";
APT
systemctl enable --now unattended-upgrades >/dev/null 2>&1 || true
say "On; check /var/run/reboot-required now and then and reboot in a quiet hour."

step "Swap"
if [ -n "$(swapon --show --noheadings 2>/dev/null)" ]; then
    say "Swap already present: $(swapon --show --noheadings | awk '{print $1 " " $3}' | tr '\n' ' ')"
elif [ "$SWAP_SIZE" = 0 ]; then
    say "Skipped (SWAP_SIZE=0)."
else
    if { fallocate -l "$SWAP_SIZE" /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048 status=none; } \
        && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile; then
        grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
        say "Swap file /swapfile ($SWAP_SIZE) on."
    else
        swapoff /swapfile 2>/dev/null || true
        rm -f /swapfile
        warn "Could not create swap (not supported on this virtualisation?)."
    fi
fi
printf 'vm.swappiness = 10\n' > /etc/sysctl.d/60-spmb.conf
sysctl -q -p /etc/sysctl.d/60-spmb.conf 2>/dev/null || true

step "Log limits"
mkdir -p /etc/systemd/journald.conf.d
printf '[Journal]\nSystemMaxUse=500M\n' > /etc/systemd/journald.conf.d/60-spmb.conf
systemctl restart systemd-journald || true
say "journald: 500 MB; Docker: 10 MB × 5 files per container (below)."

# ---------------------------------------------------------------------------------------
step "Docker Engine"
if ! dpkg -s docker-ce >/dev/null 2>&1; then
    if dpkg -s docker.io >/dev/null 2>&1; then
        warn "Ubuntu's docker.io package is installed; leaving it. Remove it and run again to use Docker's own packages."
    else
        install -m 0755 -d /etc/apt/keyrings
        curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
        chmod a+r /etc/apt/keyrings/docker.asc
        echo "deb [arch=$ARCH signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $CODENAME stable" \
            > /etc/apt/sources.list.d/docker.list
        apt_get update -q
        apt_get install -y -q docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    fi
fi
DAEMON_JSON='{
  "live-restore": true,
  "log-driver": "json-file",
  "log-opts": { "max-size": "10m", "max-file": "5" }
}'
mkdir -p /etc/docker
if [ "$(cat /etc/docker/daemon.json 2>/dev/null || true)" != "$DAEMON_JSON" ]; then
    [ ! -f /etc/docker/daemon.json ] || cp /etc/docker/daemon.json "/etc/docker/daemon.json.bak-$(date +%Y%m%d%H%M%S)"
    printf '%s\n' "$DAEMON_JSON" > /etc/docker/daemon.json
    systemctl restart docker 2>/dev/null || warn "Docker did not restart; check: journalctl -u docker"
fi
systemctl enable --now docker >/dev/null 2>&1 || true
say "$(docker --version 2>/dev/null || echo 'docker not available'); $(docker compose version 2>/dev/null || true)"

step "cloudflared (Cloudflare Tunnel)"
if ! command -v cloudflared >/dev/null; then
    install -d -m 0755 /usr/share/keyrings
    curl -fsSL https://pkg.cloudflare.com/cloudflare-public-v2.gpg -o /usr/share/keyrings/cloudflare-public-v2.gpg
    echo "deb [signed-by=/usr/share/keyrings/cloudflare-public-v2.gpg] https://pkg.cloudflare.com/cloudflared any main" \
        > /etc/apt/sources.list.d/cloudflared.list
    apt_get update -q
    apt_get install -y -q cloudflared
fi
if systemctl is-active --quiet cloudflared 2>/dev/null; then
    say "cloudflared is running."
else
    say "Installed; the tunnel is connected later with 'cloudflared service install <token>'."
fi

step "Cockpit (browser console on 127.0.0.1:9090, reached through the tunnel)"
apt_get install -y -q --no-install-recommends cockpit-ws cockpit-system cockpit-bridge
mkdir -p /etc/systemd/system/cockpit.socket.d /etc/cockpit
cat > /etc/systemd/system/cockpit.socket.d/listen.conf <<'UNIT'
[Socket]
ListenStream=
ListenStream=127.0.0.1:9090
FreeBind=yes
UNIT
{
    echo '[WebService]'
    if [ -n "$CONSOLE_HOST" ]; then echo "Origins = https://$CONSOLE_HOST wss://$CONSOLE_HOST"; fi
    echo 'ProtocolHeader = X-Forwarded-Proto'
    echo 'AllowUnencrypted = true'
    echo
    echo '[Session]'
    echo 'IdleTimeout = 30'
} > /etc/cockpit/cockpit.conf
systemctl daemon-reload
systemctl enable cockpit.socket >/dev/null 2>&1 || true
systemctl restart cockpit.socket
say "Listening on 127.0.0.1:9090${CONSOLE_HOST:+ (origin https://$CONSOLE_HOST)}. Root cannot sign in to Cockpit."

if [ -n "$ADMIN_USER" ]; then
    step "Admin account '$ADMIN_USER' (sudo; for Cockpit and SSH)"
    if ! id "$ADMIN_USER" >/dev/null 2>&1; then
        adduser --disabled-password --gecos "" "$ADMIN_USER" >/dev/null
        say "Created."
    fi
    usermod -aG sudo "$ADMIN_USER"
    usermod -aG docker "$ADMIN_USER" 2>/dev/null || true
    admin_home="$(getent passwd "$ADMIN_USER" | cut -d: -f6)"
    if [ -s /root/.ssh/authorized_keys ] && [ ! -s "$admin_home/.ssh/authorized_keys" ]; then
        install -d -m 700 -o "$ADMIN_USER" -g "$ADMIN_USER" "$admin_home/.ssh"
        install -m 600 -o "$ADMIN_USER" -g "$ADMIN_USER" /root/.ssh/authorized_keys "$admin_home/.ssh/authorized_keys"
        say "Copied root's SSH keys."
    fi
    if passwd -S "$ADMIN_USER" | awk '{exit !($2 == "L" || $2 == "NP")}'; then
        if [ -t 0 ]; then
            say "Choose a password for $ADMIN_USER (Cockpit and sudo):"
            passwd "$ADMIN_USER" || warn "Password not set; run: passwd $ADMIN_USER"
        else
            warn "$ADMIN_USER has no password yet; run: passwd $ADMIN_USER"
        fi
    fi
fi

# ---------------------------------------------------------------------------------------
step "Firewall: deny incoming (traffic arrives through Cloudflare Tunnel)"
# Port(s) sshd listens on: ssh.socket (Ubuntu 24.04 socket activation) or sshd's own config.
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

# The SSH session running this script: "client_ip client_port server_ip server_port".
CURRENT_PORT=""
if [ -n "${SSH_CONNECTION:-}" ]; then
    read -r client_ip _ _ server_port <<< "$SSH_CONNECTION"
    case "$client_ip" in
        127.0.0.1|::1) ;;  # came through the tunnel (cloudflared on this host)
        *) CURRENT_PORT="$server_port" ;;
    esac
fi

OPEN_PORTS=""
if [ "$KEEP_SSH_OPEN" != 0 ]; then
    OPEN_PORTS="$SSH_PORTS"
fi
if [ -n "$CURRENT_PORT" ] && [[ " $OPEN_PORTS " != *" $CURRENT_PORT "* ]]; then
    OPEN_PORTS="${OPEN_PORTS:+$OPEN_PORTS }$CURRENT_PORT"
fi

ufw default deny incoming >/dev/null
ufw default allow outgoing >/dev/null
for port in $OPEN_PORTS; do
    ufw allow "$port/tcp" comment 'SSH (key-only), last way in when the tunnel is down' >/dev/null
done
if ufw --force enable >/dev/null; then
    if [ -n "$OPEN_PORTS" ]; then
        say "SSH port(s) kept open: $OPEN_PORTS"
    else
        say "No inbound port open."
    fi
else
    warn "ufw could not be enabled (virtualisation?); the server has no host firewall."
fi
say "Docker stacks publish on 127.0.0.1 only, so Docker's own iptables rules open nothing publicly."

step "SSH password login"
# A key line starts with its type; lines with options (a provider's command="..." stub) do not count.
has_admin_key() {
    local user home
    for user in root $(getent group sudo | cut -d: -f4 | tr ',' ' '); do
        home="$(getent passwd "$user" | cut -d: -f6)"
        [ -n "$home" ] && grep -qsE '^(ssh-|ecdsa-|sk-)' "$home/.ssh/authorized_keys" && return 0
    done
    return 1
}
# 0 = the SSH session running this script signed in with a key, 1 = with a password,
# 2 = unknown (not an SSH session, or sshd's log is not readable).
session_used_key() {
    local cip cport
    [ -n "${SSH_CONNECTION:-}" ] || return 2
    read -r cip cport _ <<< "$SSH_CONNECTION"
    local pattern="Accepted [a-z-]+ for [^ ]+ from $cip port $cport "
    local log
    log="$( { journalctl -u ssh -u ssh.service --since '-3 days' --no-pager -o cat 2>/dev/null; cat /var/log/auth.log 2>/dev/null; } \
        | grep -E "$pattern" | tail -n 1 || true)"
    [ -n "$log" ] || return 2
    case "$log" in *"Accepted publickey"*) return 0 ;; *) return 1 ;; esac
}
# Drop-in named 00-* so it wins over 50-cloud-init.conf (sshd keeps the first value it reads).
SSHD_DROPIN=/etc/ssh/sshd_config.d/00-spmb.conf
if [ "$SSH_PASSWORD_LOGIN" = keep ]; then
    say "Left as it is (SSH_PASSWORD_LOGIN=keep)."
elif has_admin_key && { session_used_key; rc=$?; [ "$rc" != 1 ]; }; then
    printf 'PasswordAuthentication no\nKbdInteractiveAuthentication no\n' > "$SSHD_DROPIN"
    if sshd -t 2>/dev/null; then
        systemctl reload ssh 2>/dev/null || true
        say "Off ($SSHD_DROPIN). Sessions already open stay open."
    else
        rm -f "$SSHD_DROPIN"
        warn "sshd rejected $SSHD_DROPIN; removed it, SSH settings unchanged."
    fi
elif has_admin_key; then
    warn "This SSH session signed in with a password, so the key is not proven yet: password login left ON. Sign in once with your key, then run this again."
else
    warn "Neither root nor a sudo user has an SSH key: password login left ON. Add a key, then run this again."
fi

step "App folder $APPS_DIR"
install -d -m 0755 "$APPS_DIR"
if [ ! -f "$APPS_DIR/PORTS" ]; then
    printf '# Local ports taken by app stacks on this server: <port> <name>\n' > "$APPS_DIR/PORTS"
    chmod 0644 "$APPS_DIR/PORTS"
fi
say "Each app lives in $APPS_DIR/<app>/ and takes a port from $APPS_DIR/PORTS (127.0.0.1 only)."

# ---------------------------------------------------------------------------------------
D="${DOMAIN:-<domain>}"
SSH_PORT_FIRST="${SSH_PORTS%% *}"
cat <<NEXT

Done. Next steps (docs/deployment.md, "First server"):

 1. Cloudflare Zero Trust → Networks → Tunnels → Create a tunnel (cloudflared), then run the
    shown command here:  cloudflared service install <token>
 2. Public hostnames of the tunnel (use 127.0.0.1, not localhost):
      ssh.$D       → ssh://127.0.0.1:$SSH_PORT_FIRST
      ${CONSOLE_HOST:-konsol.$D}  → http://127.0.0.1:9090
    and, after 'spmb init', the app hostnames → http://127.0.0.1:<port from $APPS_DIR/PORTS>.
    Put ssh.$D and ${CONSOLE_HOST:-konsol.$D} (and staging) behind a Cloudflare Access application.
 3. From your PC, test SSH through the tunnel (Bitvise: cloudflared access tcp --hostname ssh.$D
    --url 127.0.0.1:2222, then connect Bitvise to 127.0.0.1:2222) and Cockpit at https://${CONSOLE_HOST:-konsol.$D}.
NEXT
if [ -n "$OPEN_PORTS" ]; then
    echo "    The direct SSH port(s) $OPEN_PORTS stay open on purpose (D-077): the last way in if the tunnel is down."
    echo "    Make them key-only: add your public key to ~/.ssh/authorized_keys (root and ${ADMIN_USER:-your sudo user}), sign in"
    echo "    once with the key, then run this script again from that session to switch password login off."
fi
cat <<NEXT
 4. Give this server read access to the repository (your own deploy key or token; never commit
    it), then: git clone <repository URL> $APPS_DIR/spmb/src
 5. bash $APPS_DIR/spmb/src/deploy/server/spmb init staging staging.$D
    spmb deploy staging && spmb spark staging spmb:install
NEXT
if [ "${#NOTES[@]}" -gt 0 ]; then
    echo
    echo "Warnings:"
    for n in "${NOTES[@]}"; do echo " - $n"; done
fi
