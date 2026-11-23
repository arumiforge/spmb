# Deployment

Docker on one Ubuntu Server 24.04 VPS, reached only through Cloudflare Tunnel (D-066…D-069).
Staging and production run as two stacks on the same server, from the one `main` branch (D-067).
The server pulls and builds; there is no deploy key in the repository or in GitHub (D-068).

| Piece | Where |
| --- | --- |
| Images | `docker/Dockerfile` (targets `app` = PHP-FPM 8.5, `web` = Nginx with `public/` only), `docker/nginx/`, `docker/php/` |
| Stack | `deploy/compose.yaml` (services `db` MySQL 8.4, `app`, `web`; volumes `db`, `writable`) |
| Server preparation | `deploy/server/setup.sh` (once per server, idempotent) |
| Server CLI | `deploy/server/spmb` → `/usr/local/bin/spmb` (`init`, `deploy`, `rollback`, `status`, `logs`, `backup`, `restore`, `spark`) |
| Agent how-to | `.claude/skills/spmb-deploy/SKILL.md` |

## Topology

```
Browser ──HTTPS──▶ Cloudflare (DNS, TLS, WAF, Access)
                      │  Cloudflare Tunnel (outbound from the server; only SSH is open, key-only)
                      ▼
 Ubuntu 24.04 host ── cloudflared ──┬─▶ 127.0.0.1:<prod port>    ─▶ spmb-production: web (Nginx) ─▶ app (PHP-FPM) ─▶ db (MySQL)
   (Cockpit, sshd,                  ├─▶ 127.0.0.1:<staging port> ─▶ spmb-staging:    web ─▶ app ─▶ db
    Docker, ufw)                    ├─▶ 127.0.0.1:9090  Cockpit (browser console)       [Access]
                                    └─▶ 127.0.0.1:<ssh port>  sshd                     [Access]
```

- Every app stack has its own network, database and volumes, and publishes **one** port on
  `127.0.0.1` from the port list `/opt/apps/PORTS` (8100–8199). Only sshd listens publicly, with key-only
  login (D-077); more apps can share the server and Docker's iptables rules never open a public port.
- `cloudflared` and Cockpit run on the host, not in Docker: admin access survives a broken Docker or
  a broken sshd (Cockpit has a terminal, services, logs and reboot).
- The VPS is a NAT VPS without a provider console. **Losing SSH and Cockpit means losing the server.**
  Every step below keeps a working way in before closing another.

### Server layout

```
/opt/apps/PORTS                      "<port> <name>" per line (spmb-staging, spmb-production, other apps)
/opt/apps/spmb/src/                  git checkout (developer's own read access); follows staging's commit
/opt/apps/spmb/staging/              stack.env (port, image tag, DB passwords), app.env (CodeIgniter .env),
/opt/apps/spmb/production/           compose.yaml (from the deployed commit), backups/, deploys.log
```

`stack.env` and `app.env` are made by `spmb init` and never leave the server (except inside backups).
`app.env` holds `encryption.key`: **keep a copy in the password manager** — 2FA secrets cannot be read
without it.

### Images

`spmb deploy` builds from `git archive <commit>`, so an image is always one exact commit (label
`org.opencontainers.image.revision`). Tag = the first 12 characters of the commit.

- `assets` stage: `node:24` runs `npm ci && npm run build` (Tailwind scans `app/`).
- `app`: `php:8.5-fpm` + intl, mysqli, gd (jpeg, webp, freetype), zip, exif, pcntl; `php.ini-production`
  with `docker/php/spmb.ini` (upload 10M, post 12M, memory 256M, `expose_php Off`, Asia/Jakarta, OPcache
  without timestamp checks); Composer `--no-dev --optimize`. Code is owned by root and read-only;
  only `writable/` belongs to `www-data`, which runs PHP-FPM. No `spark optimize` (it edits
  `app/Config/Optimize.php`).
- `web`: `nginx:1.28-alpine` with `public/` and the built assets only. Real client IP from
  `CF-Connecting-IP` (trusted from loopback and the Docker bridge ranges), `HTTPS on` for PHP when
  `X-Forwarded-Proto: https`, `client_max_body_size 12m`, dotfiles denied, only `index.php` runs PHP,
  `/build/` cached for a year (URLs carry `?v=<mtime>`), `/assets/` for a week, `/healthz` for Docker.
- Not in the image (`.dockerignore`): `flowbite/` (licensed Pro source, D-018), `node_modules`, `vendor`
  (rebuilt), `.git`, `tests`, `docs`, `ai`, `deploy`, client `originals/` and `source/`, `writable/*` content,
  `.env`.

Examples of both settings files, with every key explained and no real values, are in
`deploy/env/production/` and `deploy/env/staging/` (`stack.env.example`, `app.env.example`). `spmb init` writes
the real files with fresh passwords and keys; use the examples to check or repair a file by hand.

## First server (once)

Prerequisites: a VPS with Ubuntu Server 24.04 (2 vCPU, 2–4 GB RAM, 40 GB disk is enough for the first year),
root access (password from the provider panel), the domain on the developer's Cloudflare account.
Server IPs, ports and passwords stay out of the repository.

1. **Upload and run `setup.sh`.** From Bitvise (SFTP window) upload `deploy/server/setup.sh` to `/root/`
   (if it was edited on Windows: `sed -i 's/\r$//' setup.sh`). In the terminal:

   ```bash
   sudo DOMAIN=spmbsmpnualmarufkudus.web.id ADMIN_USER=<name> bash setup.sh
   ```

   It asks for the new admin user's password (Cockpit refuses root). It installs Docker (Docker's apt
   repository, `live-restore`, log limits), cloudflared, Cockpit on `127.0.0.1:9090`, unattended security
   updates (no automatic reboot), swap if none, journald limits, ufw (deny incoming) and `/opt/apps`, and
   prints the next steps. Run it again any time; it changes only what differs.

   Lock-out guards (D-065, D-066):
   - Every port sshd listens on (22, or the provider's custom port, read from `ssh.socket` or `sshd -T`)
     stays open in ufw for good (D-077), and so does the port of the SSH session running the script.
     `KEEP_SSH_OPEN=0` skips the first rule only.
   - Password login is switched off only when root or a sudo user has an SSH key **and** the session
     running the script signed in with a key (found in sshd's log); it goes into
     `/etc/ssh/sshd_config.d/00-spmb.conf` (wins over `50-cloud-init.conf`) after `sshd -t` accepts it.
     Otherwise it stays on with a warning. `SSH_PASSWORD_LOGIN=keep` never touches it.
   - sshd is reloaded, never restarted; the network configuration is not touched (Cockpit is installed
     without NetworkManager).

2. **Tunnel.** Cloudflare Zero Trust → Networks → Tunnels → *Create a tunnel* (cloudflared) → copy the
   `cloudflared service install <token>` command and run it on the server. Public hostnames (always
   `127.0.0.1`, not `localhost`):

   | Hostname | Service |
   | --- | --- |
   | `ssh.<domain>` | `ssh://127.0.0.1:<ssh port>` (printed by `setup.sh`) |
   | `konsol.<domain>` | `http://127.0.0.1:9090` (Cockpit) |
   | `staging.<domain>` | `http://127.0.0.1:<staging port>` (after `spmb init staging`) |
   | `<domain>` | `http://127.0.0.1:<production port>` (after `spmb init production`) |

3. **Access policies.** Zero Trust → Access → Applications → *Self-hosted*: one application for
   `ssh.<domain>`, `konsol.<domain>` and `staging.<domain>`, policy *Allow* for the developers' emails
   (one-time PIN or Google). The production domain stays public. SSL/TLS mode "Full", "Always Use HTTPS" on.

4. **Test the tunnel, then make SSH key-only (D-077).** The direct SSH port stays open on purpose: the
   VPS has no provider console, so direct SSH with a key is the last way in when the tunnel is down.
   - Cockpit: open `https://konsol.<domain>`, sign in as the admin user.
   - SSH through the tunnel: install `cloudflared` on Windows, run
     `cloudflared access tcp --hostname ssh.<domain> --url 127.0.0.1:2222`, then connect Bitvise to
     `127.0.0.1` port `2222`. OpenSSH clients can use `ProxyCommand cloudflared access ssh --hostname %h`
     in `~/.ssh/config` instead.
   - Make a key in Bitvise: *Client key manager* → *Generate New* (ed25519, with a passphrase) → *Export*
     the public key in OpenSSH format. Keep a copy of the private key in the password manager: without it
     and without passwords there is no way in.
   - Add the public key on the server for root and the admin user
     (`~/.ssh/authorized_keys`, mode 600, folder 700). In Bitvise set *Initial method* to *publickey*,
     choose the key and sign in with it **while the password session stays open**.
   - From the key session run `sudo bash setup.sh` again (same variables): it sees the key login and
     switches password login off (`/etc/ssh/sshd_config.d/00-spmb.conf`). Open one more key session to
     check before you close the old ones.
   - Do not install fail2ban or similar: on a NAT VPS every SSH client appears to come from the provider's
     gateway address, so a ban would lock everyone out. Key-only login already defeats password guessing.

5. **Repository access (developer).** Give the server read access yourself — a read-only deploy key
   created on the server (`ssh-keygen -t ed25519`, public key added to the repository's *Deploy keys*) or a
   fine-grained read-only token in the clone URL's credential helper. Never put the key or token in the
   repository, in GitHub secrets or in these docs. Then:

   ```bash
   git clone <repository URL> /opt/apps/spmb/src
   ```

6. **Staging.**

   ```bash
   bash /opt/apps/spmb/src/deploy/server/spmb init staging staging.<domain>   # links /usr/local/bin/spmb
   spmb deploy staging                                                        # origin/main
   spmb spark staging spmb:install                                            # first Super Admin (asks)
   spmb spark staging spmb:branding clients/<school>/branding
   ```

   Add the staging port (printed by `init`, also in `/opt/apps/PORTS`) as the `staging.<domain>` public hostname.
   Staging runs with `CI_ENVIRONMENT = production`, so `--demo` accounts are refused there too; create
   test accounts in the admin. Staging data is test data only; production personal data never goes to
   staging (UU PDP, D-067), and `spmb restore` refuses a production backup on staging.

7. **Production.**

   ```bash
   spmb init production <domain>
   spmb deploy production                    # promotes the image running on staging
   spmb spark production spmb:install
   spmb spark production spmb:branding clients/<school>/branding
   ```

## Everyday commands

| Task | Command |
| --- | --- |
| Deploy the latest `main` to staging | `spmb deploy staging` |
| Deploy a branch, tag or commit to staging | `spmb deploy staging <ref>` |
| Promote what staging runs to production | `spmb deploy production` (asks; `--yes` to skip) |
| Production from a given ref (not tested on staging; warns) | `spmb deploy production <ref>` |
| Back to the previous image | `spmb rollback <env>` |
| Overview | `spmb status` |
| Logs | `spmb logs <env> [web|app|db]` |
| Backup now | `spmb backup <env>` |
| Restore | `spmb restore <env> /opt/apps/spmb/<env>/backups/<file>.tar` |
| Spark command | `spmb spark <env> <command> [args]`, e.g. `spmb spark production spmb:install` |

### What `spmb deploy` does

1. `git fetch` in `/opt/apps/spmb/src`, resolve the ref (staging default `origin/main`).
2. Build `spmb-app:<tag>` and `spmb-web:<tag>` from `git archive <commit>` (skipped when they exist).
   Production without a ref reuses staging's image — **same image, no rebuild**.
3. Staging only: the checkout moves to that commit (detached), so the CLI matches what runs.
4. Copy `deploy/compose.yaml` of that commit into the environment folder.
5. Back up the database and uploads (when the stack already runs).
6. `docker compose up -d --wait` (healthchecks: MySQL ping, PHP-FPM port, Nginx `/healthz`).
7. `php spark migrate --all`, then `php spark referensi:import --bawaan` (a no-op unless the bundled
   SD/MI list changed, D-058).
8. Health check: HTTP 200 on `/` through the published port.
9. Keep the newest 5 image tags plus every tag in use or recorded as previous; remove older ones.

If step 6, 7 or 8 fails, the stack switches back to the previous image automatically. Migrations that
already ran are **not** undone: if the old version misbehaves, restore the backup made in step 5.

Deploys and rollbacks are logged in `/opt/apps/spmb/<env>/deploys.log`. Only one `spmb` command runs at a
time (lock file).

## Backups

- `spmb init` enables `spmb-backup@<env>.timer` (systemd): daily at 01:30 WIB (± 20 min), plus one before
  every deploy and every restore.
- An archive `backups/spmb-<env>-<time>.tar` holds `db.sql.gz` (`mysqldump --single-transaction`),
  `uploads.tar.gz` (`writable/uploads`: documents, photos, branding), `app.env` (with `encryption.key`)
  and `backup.info` (environment, time, image, commit). Archives are `0600` in a `0700` folder; they are
  kept 14 days.
- **Copy production backups off the server** (they hold personal data: encrypted storage only, e.g. a
  private bucket or the developer's encrypted disk). Pull them over the tunnel, e.g. with Bitvise SFTP or
  `rsync` through `cloudflared access ssh`.
- Restore drill: monthly, restore a **staging** backup on staging (`spmb restore staging <file>`).

### Restore

`spmb restore <env> <file>` asks first, makes a safety backup, drops and recreates the database, loads the
dump, replaces `writable/uploads`, takes `encryption.key` from the archive when it differs (the old
`app.env` is kept as `app.env.before-restore`), restarts the app, runs migrations and the health check.
The stack must be running (deploy first on a new server).

## Moving to another server

1. Old server: `spmb backup production` (and staging if wanted); copy the archives off the server.
2. New server: steps 1–5 of "First server", then `spmb init production <domain>` and
   `spmb deploy production <ref>` (the commit in `backup.info`, or newer).
3. `spmb restore production <file>` — brings the data, uploads and `encryption.key`.
4. Point the tunnel's public hostname to the new server: install the same tunnel on the new server
   (`cloudflared service install <token>`) and uninstall it on the old one, or create a new tunnel and
   move the hostnames. Check the site, then retire the old server.

## Troubleshooting

| Symptom | Check |
| --- | --- |
| 502 from the site | `spmb status`; `spmb logs <env> app` |
| Site down, SSH down | Cockpit at `https://konsol.<domain>` → Terminal |
| Cockpit down, SSH works | `systemctl status cockpit.socket` |
| Both down | Direct SSH with your key on the provider's SSH port (Bitvise) still works (D-077); then check `systemctl status cloudflared` and the Cloudflare dashboard → Tunnels |
| Disk full | `docker system df`; `docker image prune`; old backups in `/opt/apps/spmb/<env>/backups/` |
| Reboot needed (`/var/run/reboot-required`) | Reboot in a quiet hour; containers come back (`restart: unless-stopped`) |

## First client: SMP NU Al Ma'ruf Kudus

| Item | Value |
| --- | --- |
| Domain | `spmbsmpnualmarufkudus.web.id`; staging `staging.spmbsmpnualmarufkudus.web.id`; console `konsol.…`; SSH `ssh.…` |
| School name (Pengaturan → Identitas Sekolah) | SMP NU Al Ma'ruf Kudus |
| Installation | One installation and database per school (D-050) |
| Cloudflare | Arumi Studios' account (developer); the domain's nameservers point there |
| Branding pack | `clients/smp-nu-al-maruf-kudus/branding` (logos, favicon, app icon, share image, green `#005c2f`, LP Ma'arif NU footer logo) |
| VPS | `spmb-prod`, Ubuntu 24.04.5, NAT VPS, sshd on a provider-set custom port, root with a password from Bitvise, **no provider console**; IP and port are in the provider panel, not in the repository |

### Go-live checklist

The school checks the site on its own domain before registration opens; it enters its waves itself
(no wave = the online form shows "pendaftaran belum dibuka").

| # | Step | Who |
| --- | --- | --- |
| 1 | Domain on Cloudflare (registrar nameservers → Cloudflare) | Arumi Studios |
| 2 | `setup.sh` on `spmb-prod` with `DOMAIN=spmbsmpnualmarufkudus.web.id` and an admin user (First server, step 1) | Arumi Studios |
| 3 | Tunnel + public hostnames + Access (steps 2–3) | Arumi Studios |
| 4 | Cockpit and SSH through the tunnel work from the Windows PC; SSH key added and password login off; the direct SSH port stays open, key-only (step 4) | Arumi Studios |
| 5 | Repository read access on the server and the clone (step 5) | Arumi Studios |
| 6 | `spmb init staging staging.spmbsmpnualmarufkudus.web.id`, `spmb deploy staging`, `spmb spark staging spmb:install`, branding pack; test on staging | Arumi Studios |
| 7 | `spmb init production spmbsmpnualmarufkudus.web.id`, `spmb deploy production`, `spmb spark production spmb:install`, `spmb spark production spmb:branding clients/smp-nu-al-maruf-kudus/branding` | Arumi Studios |
| 8 | Save both `encryption.key` values in the password manager; check `spmb status` shows the backup timer's first archive the next day | Arumi Studios |
| 9 | Create the Admin Sekolah account (Pengguna → Tambah), hand over the temporary password in person; the school fills in address/contact, waves, documents and card settings | School |

While the school reviews, no wave is open, so nobody can register by accident. Never use `--demo` on
the server. Later schools get their own `clients/<school>/` folder and installation.
