# Server playbook

One flow for every server, from "I just got SSH access" to "the site answers", and for every new
Docker app on a server that already runs others (D-112). The commands are idempotent: running a step
again changes only what is missing, so the flow can be restarted at any point.
Details of each command: [deployment.md](deployment.md). Agent how-to: `.claude/skills/spmb-deploy`.

## Always start here: the check

```bash
sudo bash check.sh          # before the repository is on the server: upload deploy/server/check.sh next to setup.sh
spmb check                  # afterwards (same script, from the checkout)
```

`check.sh` changes nothing. It reads the host (OS, RAM, disk, pending reboot), Docker (where it came from,
engine, Compose, Buildx, `daemon.json`, running containers, ports published on public addresses), what
`setup.sh` did (marker `/opt/apps/.server-setup`), firewall and SSH, the tunnel and Cockpit, the apps in
`/opt/apps/PORTS`, and each SPMB environment (initialised, image, containers, HTTP 200 on its local port,
Super Admin present, reachable from the internet, backup timer). It ends with **State** and **Next**:
paste that block into a chat and the next step is clear without re-explaining the server.

## States

The first step that is not done decides the state. Each row is safe to repeat.

| State | Meaning | Next command |
| --- | --- | --- |
| S0 | Fresh server: no Docker, nothing listening except SSH | `sudo DOMAIN=<domain> ADMIN_USER=<name> bash setup.sh` |
| S0b | Server already in use: Docker (docker-ce or Ubuntu's docker.io) and/or other public services | same, plus `ALLOW_PORTS="<ports check.sh lists>"` for services that must stay reachable |
| S0x | Docker from snap (blocker) | see "Docker from snap" below, then check again |
| S1 | `setup.sh` done, tunnel not connected | Cloudflare: create the tunnel, `cloudflared service install <token>`, routes `ssh.` and `konsol.` behind Access ([deployment.md](deployment.md) steps 2–4) |
| S2 | Tunnel up, no repository on the server | deploy key + `git clone` ([deployment.md](deployment.md) step 5; `check.sh` prints the commands) |
| S3 | Repository ready, staging not created | `bash /opt/apps/spmb/src/deploy/server/spmb up staging staging.<domain>` |
| S4 / S4b | Staging created but not running / not answering 200 | `spmb up staging` (starts the same image again; deploys when there is none); `spmb logs staging app` |
| S5 | Staging runs, no Super Admin | `spmb spark staging spmb:install` (`spmb up` asks for it when run in a terminal) |
| S6 / S6b | Staging live, production not running / not answering | `spmb up production <domain>`; `spmb logs production app`, `spmb rollback production` |
| S7 | Production runs, no Super Admin | `spmb spark production spmb:install`, then the school's branding pack |
| S9 | Live | everyday: `spmb deploy staging` → check → `spmb deploy production` |

Warnings that do not change the state (password login still on, off-site backups missing, pending reboot,
a container published on `0.0.0.0`, a published port missing from `/opt/apps/PORTS`, low disk) are listed
under **Needs attention**. A port missing from the list comes with the exact `echo "<port> <project>" >> /opt/apps/PORTS`
line to record it, so the next app never takes the same port.

```
check.sh ─▶ Docker from snap? ──yes──▶ S0x: move containers, snap remove docker ─▶ check again
              │no
              ▼
          setup.sh done? ──no──▶ fresh (S0) or in use (S0b) ─▶ setup.sh [ALLOW_PORTS=…] ─▶ check again
              │yes
              ▼
          tunnel ─▶ repository ─▶ spmb up staging <domain> ─▶ spmb up production <domain> ─▶ S9 live
```

## Fresh server vs a server already in use

`setup.sh` is the same script for both; it looks before it changes anything.

| Item | Fresh server (S0) | Server already in use (S0b) |
| --- | --- | --- |
| Docker packages | Installs Docker's own `docker-ce`, Compose and Buildx plugins | Keeps what is there: `docker-ce` (adds missing plugins) or Ubuntu's `docker.io` (adds `docker-compose-v2`, `docker-buildx`). Never replaces an engine: that would stop every container |
| `/etc/docker/daemon.json` | Writes `live-restore` and log limits (10 MB × 5) | **Merges** them into the existing file (backup `daemon.json.bak-<time>`); other keys stay; a different log driver chosen on purpose stays; an invalid file is left alone with a warning |
| Docker restart | Restarts Docker to apply the file (nothing runs yet) | Never while containers run: reloads Docker (applies `live-restore`) and asks for a restart in a quiet hour for the log defaults |
| Firewall (ufw) | Deny incoming, SSH port(s) open | Lists services listening publicly. ufw is **not** switched on while one of them is not in `ALLOW_PORTS` (it would cut them off); `ALLOW_PORTS="80 443"` keeps them, `FIREWALL=on` closes them, `FIREWALL=off` leaves ufw alone. An ufw that is already active only gets rules added |
| Containers published on `0.0.0.0` | none | Warned: Docker's iptables rules bypass ufw, so these ports are public whatever ufw says. Move them to `127.0.0.1:<port>` and route them through the tunnel |
| SSH, Cockpit, cloudflared, swap, updates | As in [deployment.md](deployment.md) "First server" | The same; lock-out guards unchanged (SSH port always open, password login off only after a proven key, sshd reloaded never restarted, network config untouched) |
| Ports for SPMB | `spmb init` takes 8100–8199 from `/opt/apps/PORTS` | The same; ports already used by a listener **or** by any container (also without a proxy process) are skipped |

### Docker from snap

The snap package runs its own engine with its own data folder; installing Docker from apt next to it gives
two engines fighting over `/var/run/docker.sock` and iptables. Neither script removes it. By hand, in a quiet hour:

1. `snap run docker ps -a` and `docker volume ls`: note what runs and which volumes hold data.
2. Back up each volume's data (or the app's own export), stop the stacks, `sudo snap remove --purge docker`.
3. `sudo bash setup.sh` (installs Docker from apt), then start the stacks again from their compose files and
   restore their data.

## Adding another Docker app (same server or a new one)

Every app follows the same layout, so nothing has to be re-decided:

| Rule | Why |
| --- | --- |
| Folder `/opt/apps/<app>/` (checkout in `src/`, settings next to it, never in git) | One place per app; `check.sh` and backups know where to look |
| One line `<port> <app>-<env>` in `/opt/apps/PORTS`, a free port in 8100–8199 (`spmb` takes them from the bottom; pick from the top for other apps, e.g. 8199, 8198) | No two stacks fight over a port |
| Publish **only** `127.0.0.1:<port>:<container port>` | Docker bypasses ufw; loopback keeps the app private until the tunnel exposes it |
| Compose project name `<app>-<env>`, own network and named volumes | Stacks never share databases or volumes by accident |
| `restart: unless-stopped`, a `healthcheck` on every service, json-file logging with `max-size`/`max-file` | Survives reboots; `up --wait` knows when it is ready; logs cannot fill the disk |
| Secrets in an `.env`/`stack.env` next to the compose file, mode 600, made on the server (`openssl rand -hex 24`) | Never in the repository |
| A public hostname on the same tunnel: `<host> → http://127.0.0.1:<port>`; staff-only apps behind Access | The only way in from outside |
| Repository access with the app's own read-only deploy key and SSH alias `github-<app>` | One key per repository (GitHub's rule) |
| Its own backup (database dump + uploads) in `/opt/apps/<app>/<env>/backups/` | Restores never mix apps |

Starting point for the compose file: [`deploy/templates/app.compose.yaml`](../deploy/templates/app.compose.yaml).
Steps on the server:

```bash
sudo bash /opt/apps/spmb/src/deploy/server/check.sh     # or `spmb check`: state, free RAM/disk, taken ports
mkdir -p /opt/apps/<app>/production && cd /opt/apps/<app>/production
cp /opt/apps/spmb/src/deploy/templates/app.compose.yaml compose.yaml   # then edit image, port, healthcheck
echo "8199 <app>-production" >> /opt/apps/PORTS                       # a port no line in PORTS uses
docker compose -p <app>-production --env-file stack.env up -d --wait
curl -sI http://127.0.0.1:8199/                                         # then add the tunnel route
```

On a **new server**, run S0 → S2 first (`check.sh`, `setup.sh`, tunnel); an app that is not SPMB then
skips S3–S7 and uses the steps above. Memory: each MySQL takes about 300–400 MB; `check.sh` shows free RAM.

## Asking Claude about a server

Start the chat with the output of `sudo bash check.sh` (or `spmb check`). The state and the warnings tell
the agent where the server is, so no earlier conversation has to be repeated. The agent follows this page
and records any new rule here and in [decisions.md](decisions.md).
