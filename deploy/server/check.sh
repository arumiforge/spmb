#!/usr/bin/env bash
# Server check: where is this server on the way to a running SPMB, and what is the next command?
# Read-only: it changes nothing, so it is safe on any server, fresh or busy (D-112).
# docs/server-playbook.md explains every state and step.
#
#   sudo bash check.sh                       # before setup.sh: upload this file next to it
#   spmb check                               # once the repository is cloned
#   sudo DOMAIN=example.sch.id bash check.sh # fills the domain into the printed commands
#
# Variables (optional): DOMAIN (production domain, for the printed commands),
# SPMB_ROOT (default /opt/apps), OFFLINE=1 (skip the network checks: repository, public URLs).
#
# Output: one line per check ([ok] fine, [--] not done yet, [!!] needs attention), then the
# state of the server (S0…S9) and the next command(s). Exit code 0, or 1 when a blocker was found.
set -uo pipefail

SPMB_ROOT="${SPMB_ROOT:-/opt/apps}"
APP_DIR="$SPMB_ROOT/spmb"
SRC="${SPMB_SRC:-$APP_DIR/src}"
DOMAIN="${DOMAIN:-}"
OFFLINE="${OFFLINE:-0}"
MARKER="$SPMB_ROOT/.server-setup"

ok()   { printf '  [ok] %s\n' "$*"; }
todo() { printf '  [--] %s\n' "$*"; }
bad()  { printf '  [!!] %s\n' "$*"; WARNINGS+=("$*"); }
info() { printf '       %s\n' "$*"; }
head_() { printf '\n== %s\n' "$*"; }
WARNINGS=()
BLOCKER=0

ROOT=1
[ "$(id -u)" -eq 0 ] || ROOT=0

pkg_installed() { dpkg-query -W -f='${Status}' "$1" 2>/dev/null | grep -q 'ok installed'; }
have() { command -v "$1" >/dev/null 2>&1; }

# "<port> <process>" for every TCP listener not bound to loopback, one line per port.
public_listeners() {
    have ss || return 0
    ss -ltnpH 2>/dev/null | awk '{
        addr = $4; port = addr; sub(/.*:/, "", port)
        host = addr; sub(/:[^:]*$/, "", host)
        if (host ~ /^127\./ || host == "[::1]" || host ~ /^\[::ffff:127\./) next
        proc = "?"
        if (match($0, /users:\(\("[^"]+"/)) proc = substr($0, RSTART + 9, RLENGTH - 10)
        print port, proc
    }' | sort -u -k1,1n
}

# Ports sshd listens on (ssh.socket on Ubuntu 24.04, otherwise sshd's own config).
ssh_ports() {
    if systemctl is-active --quiet ssh.socket 2>/dev/null; then
        systemctl show -p Listen --value ssh.socket | grep -oE ':[0-9]+ ' | tr -d ': '
    else
        sshd -T 2>/dev/null | awk '$1 == "port" {print $2}'
    fi | sort -un | tr '\n' ' '
}

# Containers that publish a port on a non-loopback address: "<name> <host:port>".
docker_public_ports() {
    docker ps --format '{{.Names}}|{{.Ports}}' 2>/dev/null | while IFS='|' read -r name ports; do
        tr ',' '\n' <<< "$ports" | sed 's/^ *//' | grep -- '->' | while read -r p; do
            host="${p%%->*}"
            case "$host" in 127.*|'[::1]'*|'::1'*) ;; *) printf '%s %s\n' "$name" "$host" ;; esac
        done
    done | sort -u
}

stack_env_var() { sed -n "s/^$2=//p" "$APP_DIR/$1/stack.env" 2>/dev/null | tail -n 1; }

compose_running() {
    docker ps -q --filter "label=com.docker.compose.project=$1" --filter status=running 2>/dev/null | wc -l
}

# Number of Super Admin accounts in <env>'s database; "?" when it cannot be read.
superadmins() {
    local id
    id="$(docker ps -q --filter "label=com.docker.compose.project=spmb-$1" \
        --filter label=com.docker.compose.service=db 2>/dev/null | head -n 1)"
    [ -n "$id" ] || { echo '?'; return; }
    # shellcheck disable=SC2016
    docker exec "$id" sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -N -B -e "SELECT COUNT(*) FROM spmb.auth_groups_users WHERE \`group\` = '"'"'superadmin'"'"'" 2>/dev/null' \
        2>/dev/null | tr -dc '0-9' || true
}

http_code() {
    curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$@" 2>/dev/null || true
}

# ------------------------------------------------------------------------------------------
head_ "Host"
# shellcheck disable=SC1091
. /etc/os-release 2>/dev/null || true
if [ "${ID:-}" = ubuntu ] && [ "${VERSION_ID:-}" = 24.04 ]; then
    ok "${PRETTY_NAME:-Ubuntu 24.04}"
else
    bad "${PRETTY_NAME:-unknown OS}: the scripts are written for Ubuntu Server 24.04."
fi
[ "$ROOT" = 1 ] || bad "Not running as root: some checks are limited (run: sudo bash check.sh)."
virt="$(systemd-detect-virt 2>/dev/null || true)"
case "$virt" in
    lxc|lxc-libvirt|openvz) bad "This server is a '$virt' container: Docker, swap and the firewall may not work." ;;
    *) ok "Virtualisation: ${virt:-unknown}" ;;
esac
mem_total="$(awk '/^MemTotal/ {printf "%d", $2 / 1024}' /proc/meminfo)"
mem_avail="$(awk '/^MemAvailable/ {printf "%d", $2 / 1024}' /proc/meminfo)"
swap_total="$(awk '/^SwapTotal/ {printf "%d", $2 / 1024}' /proc/meminfo)"
if [ "${mem_total:-0}" -lt 1800 ]; then
    bad "RAM ${mem_total} MB (${mem_avail} MB free): staging + production need about 2 GB."
else
    ok "RAM ${mem_total} MB (${mem_avail} MB free), swap ${swap_total} MB"
fi
disk_dir="$SPMB_ROOT"; [ -d "$disk_dir" ] || disk_dir=/
disk_free_gb="$(df -Pk "$disk_dir" | awk 'NR == 2 {printf "%d", $4 / 1048576}')"
if [ "${disk_free_gb:-0}" -lt 8 ]; then
    bad "Disk: only ${disk_free_gb} GB free on $disk_dir (images, database and backups need room)."
else
    ok "Disk: ${disk_free_gb} GB free on $disk_dir"
fi
[ ! -f /var/run/reboot-required ] || bad "A reboot is pending (/var/run/reboot-required): reboot in a quiet hour."

# ------------------------------------------------------------------------------------------
head_ "Docker"
DOCKER_SRC=none
if have snap && snap list docker >/dev/null 2>&1; then DOCKER_SRC=snap
elif pkg_installed docker-ce; then DOCKER_SRC=docker-ce
elif pkg_installed docker.io; then DOCKER_SRC=docker.io
elif have docker; then DOCKER_SRC=other
fi
DOCKER_UP=0
CONTAINERS=0
PROJECTS=""
case "$DOCKER_SRC" in
    none)
        todo "Docker is not installed (setup.sh installs it)." ;;
    snap)
        bad "Docker comes from snap. SPMB needs Docker from apt: see docs/server-playbook.md, 'Docker from snap'."
        BLOCKER=1 ;;
    *)
        case "$DOCKER_SRC" in
            docker-ce) ok "Docker from Docker's own packages (docker-ce)" ;;
            docker.io) ok "Docker from Ubuntu's packages (docker.io); setup.sh keeps it and adds what is missing" ;;
            other) bad "A 'docker' command exists but not from apt (Podman? a manual install?): check by hand." ;;
        esac
        if docker info >/dev/null 2>&1; then
            DOCKER_UP=1
            ok "Engine $(docker version -f '{{.Server.Version}}' 2>/dev/null) is running"
        else
            bad "The Docker engine does not answer (systemctl status docker)."
        fi
        if docker compose version >/dev/null 2>&1; then
            ok "Compose $(docker compose version --short 2>/dev/null)"
        else
            todo "Compose v2 plugin missing (setup.sh installs it)."
        fi
        if docker buildx version >/dev/null 2>&1; then
            ok "Buildx (BuildKit) available"
        else
            todo "Buildx missing (setup.sh installs it; 'spmb deploy' builds with BuildKit)."
        fi
        if [ -f /etc/docker/daemon.json ] && have jq; then
            if ! jq -e . /etc/docker/daemon.json >/dev/null 2>&1; then
                bad "/etc/docker/daemon.json is not valid JSON; setup.sh will leave it alone."
            elif [ "$(jq -r '."live-restore" // false' /etc/docker/daemon.json)" = true ]; then
                ok "daemon.json: live-restore on (containers survive a Docker restart)"
            else
                todo "daemon.json: live-restore off (setup.sh adds it without restarting running containers)."
            fi
        else
            todo "No /etc/docker/daemon.json yet (setup.sh writes live-restore and log limits)."
        fi
        if [ "$DOCKER_UP" = 1 ]; then
            CONTAINERS="$(docker ps -q | wc -l)"
            PROJECTS="$(docker ps -a --format '{{.Label "com.docker.compose.project"}}' | sort -u | grep -v '^$' | tr '\n' ' ')"
            info "Containers running: $CONTAINERS; compose projects: ${PROJECTS:-none}"
            while read -r name hostport; do
                [ -n "$name" ] || continue
                bad "Container $name publishes $hostport on a public address. Docker bypasses ufw, so this port is open to the internet; publish on 127.0.0.1 and use the tunnel instead."
            done < <(docker_public_ports)
        fi ;;
esac

# ------------------------------------------------------------------------------------------
head_ "Server setup (setup.sh), firewall, SSH"
SETUP_DONE=0
if [ -f "$MARKER" ]; then
    SETUP_DONE=1
    ok "setup.sh has run ($(sed -n 's/^last_run=//p' "$MARKER"))"
    [ -n "$DOMAIN" ] || DOMAIN="$(sed -n 's/^domain=//p' "$MARKER")"
elif [ -f /etc/apt/apt.conf.d/52spmb-unattended-upgrades ]; then
    SETUP_DONE=1
    ok "setup.sh has run (an earlier version; running it again adds the marker file)"
else
    todo "setup.sh has not run on this server."
fi
SSH_PORTS="$(ssh_ports)"; SSH_PORTS="${SSH_PORTS% }"
info "sshd listens on: ${SSH_PORTS:-unknown}"

OTHER_PUBLIC=""
while read -r port proc; do
    [ -n "$port" ] || continue
    case " $SSH_PORTS " in *" $port "*) continue ;; esac
    case "$proc" in sshd|systemd) continue ;; docker-proxy) continue ;; esac
    OTHER_PUBLIC="${OTHER_PUBLIC:+$OTHER_PUBLIC }$port"
    info "Also listening publicly: port $port ($proc)"
done < <(public_listeners)

if have ufw && ufw status 2>/dev/null | grep -q '^Status: active'; then
    ok "Firewall (ufw) active"
    for p in $SSH_PORTS; do
        ufw status 2>/dev/null | grep -qE "^$p(/tcp)?[[:space:]]+ALLOW" \
            || bad "ufw is active but SSH port $p has no ALLOW rule: check before closing this session."
    done
elif [ "$ROOT" = 0 ]; then
    info "Firewall state needs root to read."
else
    todo "Firewall (ufw) not active yet (setup.sh enables it, keeping SSH open)."
fi

if [ "$ROOT" = 1 ] && have sshd; then
    if [ "$(sshd -T 2>/dev/null | awk '$1 == "passwordauthentication" {print $2}')" = no ]; then
        ok "SSH: password login off (key-only)"
    else
        todo "SSH: password login still on (docs/deployment.md, First server step 4: add a key, sign in with it, run setup.sh again)."
    fi
fi

# ------------------------------------------------------------------------------------------
head_ "Admin access (Cloudflare Tunnel, Cockpit)"
TUNNEL=0
if have cloudflared; then
    if systemctl is-active --quiet cloudflared 2>/dev/null; then
        TUNNEL=1
        ok "cloudflared runs as a service (tunnel connected)"
    else
        todo "cloudflared installed, tunnel not connected yet (cloudflared service install <token>)."
    fi
else
    todo "cloudflared not installed (setup.sh installs it)."
fi
if have ss && ss -ltnH 'sport = :9090' 2>/dev/null | grep -q '127.0.0.1:9090'; then
    ok "Cockpit on 127.0.0.1:9090"
elif systemctl is-enabled --quiet cockpit.socket 2>/dev/null; then
    ok "Cockpit socket enabled"
else
    todo "Cockpit not set up (setup.sh)."
fi

# ------------------------------------------------------------------------------------------
head_ "Apps on this server ($SPMB_ROOT/PORTS)"
if [ -f "$SPMB_ROOT/PORTS" ]; then
    while read -r port name; do
        case "$port" in ''|'#'*) continue ;; esac
        if [ "$(http_code "http://127.0.0.1:$port/")" = 000 ]; then
            info "$port $name — nothing answers"
        else
            info "$port $name — answers"
        fi
    done < "$SPMB_ROOT/PORTS"
else
    todo "No port list yet (setup.sh creates $SPMB_ROOT/PORTS)."
fi

# ------------------------------------------------------------------------------------------
head_ "SPMB"
SRC_OK=0
if [ -d "$SRC/.git" ] || [ -f "$SRC/.git" ]; then
    SRC_OK=1
    ok "Checkout $SRC ($(git -C "$SRC" log -1 --format='%h %cs' 2>/dev/null))"
    if [ "$OFFLINE" != 1 ]; then
        if timeout 25 git -C "$SRC" ls-remote -q origin HEAD >/dev/null 2>&1; then
            ok "The server can read the repository (git fetch works)"
        else
            bad "The server cannot read the repository: check its deploy key (ssh -T git@github-spmb) and network."
        fi
    fi
else
    todo "No checkout in $SRC yet (deploy key + git clone)."
fi
if [ -e /usr/local/bin/spmb ]; then ok "spmb command installed"; else todo "spmb command not linked yet (spmb init does it)."; fi

declare -A INIT=() TAG=() RUNNING=() HEALTH=() ADMINS=() ENVDOMAIN=() PORT=()
for env in staging production; do
    INIT[$env]=0; TAG[$env]=""; RUNNING[$env]=0; HEALTH[$env]=""; ADMINS[$env]="?"
    if [ ! -f "$APP_DIR/$env/stack.env" ]; then
        todo "$env: not initialised"
        continue
    fi
    INIT[$env]=1
    ENVDOMAIN[$env]="$(stack_env_var "$env" SPMB_DOMAIN)"
    PORT[$env]="$(stack_env_var "$env" SPMB_PORT)"
    TAG[$env]="$(stack_env_var "$env" SPMB_TAG)"
    if [ -z "${TAG[$env]}" ]; then
        todo "$env (${ENVDOMAIN[$env]}, port ${PORT[$env]}): initialised, never deployed"
        continue
    fi
    [ "$DOCKER_UP" = 1 ] && RUNNING[$env]="$(compose_running "spmb-$env")"
    if [ "${RUNNING[$env]}" -lt 3 ]; then
        bad "$env: ${RUNNING[$env]} of 3 containers running (image ${TAG[$env]}): spmb logs $env app"
        continue
    fi
    HEALTH[$env]="$(http_code -H "Host: ${ENVDOMAIN[$env]}" -H 'X-Forwarded-Proto: https' "http://127.0.0.1:${PORT[$env]}/")"
    ADMINS[$env]="$(superadmins "$env")"; ADMINS[$env]="${ADMINS[$env]:-?}"
    if [ "${HEALTH[$env]}" = 200 ]; then
        ok "$env runs ${TAG[$env]} on 127.0.0.1:${PORT[$env]} (HTTP 200), domain ${ENVDOMAIN[$env]}"
    else
        bad "$env answers HTTP ${HEALTH[$env]:-none} on 127.0.0.1:${PORT[$env]}: spmb logs $env app"
    fi
    case "${ADMINS[$env]}" in
        0) todo "$env: no Super Admin yet (spmb spark $env spmb:install)" ;;
        '?') info "$env: Super Admin count could not be read" ;;
        *) ok "$env: Super Admin account exists" ;;
    esac
    if [ "$OFFLINE" != 1 ] && [ "$TUNNEL" = 1 ] && [ "${HEALTH[$env]}" = 200 ]; then
        pub="$(http_code "https://${ENVDOMAIN[$env]}/")"
        case "$pub" in
            200) ok "https://${ENVDOMAIN[$env]}/ answers from the internet (200)" ;;
            302|401|403) ok "https://${ENVDOMAIN[$env]}/ answers ($pub: behind Cloudflare Access)" ;;
            *) bad "https://${ENVDOMAIN[$env]}/ answers ${pub:-nothing}: add the tunnel route ${ENVDOMAIN[$env]} -> http://127.0.0.1:${PORT[$env]}" ;;
        esac
    fi
    if [ -d /run/systemd/system ] && ! systemctl is-enabled --quiet "spmb-backup@$env.timer" 2>/dev/null; then
        bad "$env: daily backup timer is off (spmb init $env ${ENVDOMAIN[$env]} turns it on again)."
    fi
done
if [ "${INIT[production]}" = 1 ] && [ ! -f "$APP_DIR/offsite.env" ]; then
    todo "Off-site backups not set up (spmb offsite setup, docs/deployment.md)."
fi

# ------------------------------------------------------------------------------------------
# State: the first step that is not done yet decides what comes next.
D="${DOMAIN:-${ENVDOMAIN[production]:-<domain>}}"
SPMB_CMD=spmb
[ -e /usr/local/bin/spmb ] || SPMB_CMD="bash $SRC/deploy/server/spmb"
STATE="" ; NEXT=()
if [ "$DOCKER_SRC" = snap ]; then
    STATE="S0x  Docker from snap (blocker)"
    NEXT=("Move or stop the snap containers, then: snap remove docker   (docs/server-playbook.md, 'Docker from snap')"
          "Then run this check again.")
elif [ "$SETUP_DONE" = 0 ]; then
    allow=""; [ -z "$OTHER_PUBLIC" ] || allow=" ALLOW_PORTS=\"$OTHER_PUBLIC\""
    if [ "$DOCKER_SRC" = none ] && [ -z "$OTHER_PUBLIC" ]; then
        STATE="S0   Fresh server"
    else
        STATE="S0b  Server already in use (Docker: $DOCKER_SRC, $CONTAINERS containers, other public ports: ${OTHER_PUBLIC:-none})"
        NEXT+=("setup.sh keeps running containers up (no Docker restart while containers run) and keeps existing Docker packages.")
        [ -z "$OTHER_PUBLIC" ] || NEXT+=("Ports $OTHER_PUBLIC serve something today: ALLOW_PORTS keeps them open; leave a port out to close it.")
    fi
    NEXT+=("sudo DOMAIN=$D ADMIN_USER=<name>$allow bash setup.sh")
elif [ "$TUNNEL" = 0 ]; then
    STATE="S1   Base ready, tunnel not connected"
    NEXT=("Cloudflare Zero Trust → Networks → Tunnels → Create → run: cloudflared service install <token>"
          "Routes: ssh.$D, konsol.$D (docs/deployment.md, First server steps 2–4)")
elif [ "$SRC_OK" = 0 ]; then
    STATE="S2   Tunnel up, repository not on the server"
    NEXT=("Deploy key + clone (docs/deployment.md, First server step 5):"
          "ssh-keygen -t ed25519 -f /root/.ssh/spmb_deploy -N \"\" -C \"$(hostname) deploy (read-only)\""
          "printf 'Host github-spmb\\n  HostName github.com\\n  User git\\n  IdentityFile /root/.ssh/spmb_deploy\\n  IdentitiesOnly yes\\n' >> /root/.ssh/config"
          "cat /root/.ssh/spmb_deploy.pub   # add to GitHub → Settings → Deploy keys (read-only)"
          "mkdir -p $APP_DIR && git clone git@github-spmb:arumiforge/spmb.git $SRC")
elif [ "${INIT[staging]}" = 0 ] || [ -z "${TAG[staging]}" ] || [ "${RUNNING[staging]}" -lt 3 ]; then
    if [ "${INIT[staging]}" = 0 ]; then
        STATE="S3   Repository ready, staging not created"
        NEXT=("$SPMB_CMD up staging staging.$D")
    else
        STATE="S4   Staging created but not running"
        NEXT=("$SPMB_CMD up staging")
    fi
    NEXT+=("Tunnel route: staging.$D -> http://127.0.0.1:<port printed by init> (behind Access)")
elif [ "${HEALTH[staging]}" != 200 ]; then
    STATE="S4b  Staging runs but does not answer 200"
    NEXT=("spmb logs staging app" "spmb up staging   (redeploys the same image)")
elif [ "${ADMINS[staging]}" = 0 ]; then
    STATE="S5   Staging runs, no Super Admin"
    NEXT=("spmb spark staging spmb:install" "spmb spark staging spmb:branding clients/<school>/branding")
elif [ "${INIT[production]}" = 0 ] || [ -z "${TAG[production]}" ] || [ "${RUNNING[production]}" -lt 3 ]; then
    STATE="S6   Staging live, production not running"
    if [ "${INIT[production]}" = 0 ]; then NEXT=("spmb up production $D"); else NEXT=("spmb up production"); fi
    NEXT+=("Tunnel route: $D -> http://127.0.0.1:<port printed by init> (public)")
elif [ "${HEALTH[production]}" != 200 ]; then
    STATE="S6b  Production runs but does not answer 200"
    NEXT=("spmb logs production app" "spmb rollback production   (if the last deploy caused it)")
elif [ "${ADMINS[production]}" = 0 ]; then
    STATE="S7   Production runs, no Super Admin"
    NEXT=("spmb spark production spmb:install" "spmb spark production spmb:branding clients/<school>/branding")
else
    STATE="S9   Live: staging and production run"
    NEXT=("Everyday: spmb deploy staging → check → spmb deploy production   (spmb help)")
fi

head_ "Result"
printf '  State: %s\n' "$STATE"
printf '  Next:\n'
for n in "${NEXT[@]}"; do printf '    %s\n' "$n"; done
if [ "${#WARNINGS[@]}" -gt 0 ]; then
    printf '  Needs attention:\n'
    for w in "${WARNINGS[@]}"; do printf '    - %s\n' "$w"; done
fi
exit "$BLOCKER"
