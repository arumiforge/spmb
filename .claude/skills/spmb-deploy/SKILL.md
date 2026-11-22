---
name: spmb-deploy
description: Build the SPMB Docker images, prepare a server, deploy staging/production, roll back, back up, restore and move servers (Docker on Ubuntu 24.04 behind Cloudflare Tunnel). Use for any change to docker/, deploy/ or the server, and whenever a deploy, backup or server question comes up.
---

# Deploying SPMB

Full reference: `docs/deployment.md`. Decisions: D-065 (lock-out guards), D-066 (Docker, host
cloudflared + Cockpit, no Proxmox), D-067 (staging + production on one VPS from `main`), D-068 (server
pulls and builds, no deploy key in the repo/GitHub), D-069 (PHP 8.5.13, Node 24, MySQL 8.4).

## Files

| File | Role |
| --- | --- |
| `docker/Dockerfile` | Multi-stage: `assets` (node:24, `npm ci && npm run build`), `vendor` (Composer `--no-dev`), targets `app` (php:8.5-fpm, www-data) and `web` (nginx, `public/` only) |
| `docker/php/spmb.ini`, `docker/php/www.conf` | PHP limits (upload 10M / post 12M, D-043), OPcache, FPM pool |
| `docker/nginx/*.conf` | Real IP from `CF-Connecting-IP`, HTTPS flag from `X-Forwarded-Proto`, caching, only `index.php` runs |
| `.dockerignore` | Keeps `flowbite/` (licensed, D-018), `.git`, tests, docs, `.env`, `writable/*` out of the context |
| `deploy/compose.yaml` | `db` (mysql:8.4), `app`, `web` on `127.0.0.1:${SPMB_PORT}`; volumes `db`, `writable`; `app.env` mounted read-only |
| `deploy/server/setup.sh` | One-time server preparation (Docker, cloudflared, Cockpit, ufw, updates, swap), idempotent |
| `deploy/server/spmb` | Server CLI: `init`, `deploy`, `rollback`, `status`, `logs`, `backup`, `restore`, `spark` |

## Rules

- **Never lock the admin out.** The VPS has no provider console. Any change to `setup.sh` must keep:
  the SSH port(s) open while `cloudflared` is not running; the port of the session in use open when it is
  not a tunnel session; password login off only with a proven key (`sshd -t` first, drop-in `00-spmb.conf`);
  sshd reloaded, never restarted; no network configuration changes (no NetworkManager, no netplan edits).
- Nothing listens publicly: stacks publish on `127.0.0.1` only, with a port from `/opt/apps/PORTS`.
- No secrets, IPs, ports, emails or tokens in the repository. The server's git access is the developer's
  own business; scripts never create or read keys.
- The committed Dockerfile must build on a normal server: no proxy settings, no CA bundles.
- Production runs only images that ran on staging, unless the developer gives a ref on purpose.
- Production personal data never goes to staging (`spmb restore` refuses it; UU PDP).
- No `spark optimize` in images (it rewrites `app/Config/Optimize.php`).
- Runtime file writes only under `writable/` (a volume). Uploads stay outside `public/`.

## Typical tasks

```bash
# Build locally (repository root)
docker build -f docker/Dockerfile --target app -t spmb-app:local .
docker build -f docker/Dockerfile --target web -t spmb-web:local .

# On the server (root)
spmb deploy staging                 # origin/main → staging
spmb deploy staging <branch|tag|sha>
spmb deploy production              # promote staging's image (asks; --yes)
spmb rollback production            # previous image; database not rolled back
spmb backup production              # also daily via spmb-backup@production.timer
spmb restore production /opt/apps/spmb/production/backups/spmb-production-<time>.tar
spmb spark production spmb:install  # any spark command in the app container
spmb status; spmb logs staging app
```

## Changing the stack

- New PHP extension or ini value → `docker/Dockerfile` / `docker/php/spmb.ini`; keep the extension check
  line in the Dockerfile in step.
- New persistent folder → it must live under `writable/` (the volume); add it to the backup in
  `cmd_backup_unlocked` if it holds user data.
- New service or volume → `deploy/compose.yaml`; it is copied from the deployed commit, so old images keep
  their old compose file on rollback.
- New deploy step (a data import, a cache clear) → `activate()` in `deploy/server/spmb`, idempotent.
- After changing a script: `bash -n` and `shellcheck` on it.

## Verify a change (cloud container)

1. `dockerd > /tmp/dockerd.log 2>&1 &` (Docker Hub may answer 429: retry with backoff).
2. In Claude's cloud container the egress proxy needs its CA for npm/Composer inside builds. Make a
   **scratch copy** of the Dockerfile that mounts `/root/.ccr` (`--build-context ccr=/root/.ccr`) for
   `npm ci`, and copy a `vendor/` built on the host (`composer install --no-dev` with PHP 8.5) instead of
   `composer install` if GitHub dists are blocked. Never commit those changes.
3. Test checkout: `git clone /home/user/spmb <scratch>/opt/spmb/src`; build the images with the label
   `org.opencontainers.image.revision=<sha>` and tag `<sha:0:12>`; then
   `SPMB_ROOT=<scratch>/opt spmb init staging staging.example.test` and
   `SPMB_ROOT=<scratch>/opt spmb deploy staging <ref>` (existing images are not rebuilt).
4. `spmb spark staging spmb:install …`, load `/`, `/masuk`, `/admin` through the port, screenshots at
   360 and 1280 px; `spmb backup`, `spmb restore`, `spmb deploy production`, `spmb rollback`.
5. `setup.sh`: run it in a privileged systemd `ubuntu:24.04` container (own network namespace, never with
   `--network host`), with `SSH_CONNECTION` set to simulate a session; run it twice. A privileged
   systemd container shares the kernel clock and can shift it by the time-zone offset (systemd's RTC
   "warp"), which breaks TLS ("certificate is not yet valid"): remove the container afterwards and reset
   the clock from an HTTP `Date` header (`date -s "<Date>"`).
