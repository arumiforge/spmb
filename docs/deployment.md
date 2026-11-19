# Deployment

> Stage: outline + CI/CD. Server hardening and backup scripts are completed in phase 6.

## Topology

```
Browser ──HTTPS──▶ Cloudflare (DNS, TLS, WAF)
                        │  Cloudflare Tunnel (outbound from server)
                        ▼
              Ubuntu Server 24.04 ── cloudflared ──▶ Nginx :80 (localhost) ──▶ PHP-FPM 8.3 ──▶ MySQL 8 (localhost)
GitHub Actions ──cloudflared access ssh (service token)──▶ sshd (localhost via tunnel)
```

No inbound ports open on the server firewall (`ufw default deny incoming`) once SSH through the tunnel works.

## Server preparation (once)

`deploy/provision.sh` does steps 1–6 (D-056). Its lock-out guards (D-065):

- **SSH port stays open until the tunnel runs.** The script reads the port(s) sshd listens on (22, or a custom
  port set by the VPS provider; from `ssh.socket` or `sshd -T`) and, while `cloudflared` is not running, adds a
  temporary `ufw allow <port>/tcp` rule before enabling the firewall. `ufw allow OpenSSH` would only open port 22.
  Close the rule by hand (`ufw delete allow <port>/tcp`) after `ssh` through the tunnel works. `KEEP_SSH_OPEN=0`
  skips the rule.
- **Password login is switched off only when a key exists** for root or a sudo user (a key line starting with
  its type; a provider's `command="..."` stub does not count). It writes `/etc/ssh/sshd_config.d/00-spmb.conf`:
  sshd keeps the first value it reads, and Ubuntu cloud images ship `50-cloud-init.conf` with
  `PasswordAuthentication yes`, so editing the main `sshd_config` has no effect there. A server reached as
  root with a password keeps password login (with a warning) until a key is added and the script is run again.
- Keep the provider's web console (VNC/recovery) at hand as the last way in.

1. Packages: `nginx`, `mysql-server`, `php8.3-fpm php8.3-{cli,intl,mbstring,mysql,gd,zip,xml,curl}`, `unzip`, `cloudflared`.
2. Deploy user (e.g. `deploy`), member of `www-data`; SSH key auth only; password login disabled (see above).
3. Sudoers rule for reloads only: `deploy ALL=NOPASSWD: /usr/bin/systemctl reload php8.3-fpm`.
4. Directories: `/var/www/spmb/{releases,shared/writable}`; create `/var/www/spmb/shared/.env` from `env` with production values (`CI_ENVIRONMENT = production`, `app.baseURL`, database credentials, `encryption.key` from `php spark key:generate --show`).
5. MySQL: database `spmb`, user with privileges on that database only, `utf8mb4_unicode_ci`.
   After the first release is uploaded, run `sudo -u deploy php spark spmb:install` once in `current/`
   to create the first Super Admin (later deploys only run migrations). Run `spark` as `deploy`, never as
   root: files root creates in `writable/` cannot be rewritten by PHP-FPM.
6. Nginx server block: `root /var/www/spmb/current/public;` `try_files $uri $uri/ /index.php?$query_string;`, PHP via FPM socket, deny dotfiles, `client_max_body_size 12m;` matching the upload limit (php-fpm: `upload_max_filesize = 10M`, `post_max_size = 12M`, in line with `Berkas.imageUploadLimitKb` — phone photos are 3–8 MB), `real_ip_header CF-Connecting-IP` with Cloudflare ranges.
7. Cloudflare Tunnel: ingress `https://<domain>` → `http://127.0.0.1:80` and `ssh://<ssh-host>` → `ssh://127.0.0.1:<ssh-port>`
   (the port sshd listens on; `provision.sh` prints it); protect the SSH hostname with a Cloudflare Access application
   allowing a **service token** (for CI) and the admins' emails. Use `127.0.0.1`, not `localhost`: Nginx listens on
   IPv4 loopback only, and `localhost` may resolve to `::1`.
8. Admin access through the tunnel: OpenSSH clients use
   `ProxyCommand cloudflared access ssh --hostname %h` in `~/.ssh/config`. Clients without a proxy command
   (Bitvise, PuTTY) run `cloudflared access tcp --hostname <ssh-host> --url 127.0.0.1:2222` and connect to
   `127.0.0.1:2222`. Then remove the temporary firewall rule from `provision.sh`.

## GitHub configuration

Settings → Environments → `production` (add required reviewers if wanted):

| Type | Name | Value |
| --- | --- | --- |
| Variable | `SSH_HOST` | Access-protected SSH hostname |
| Variable | `SSH_USER` | `deploy` |
| Variable | `DEPLOY_PATH` | `/var/www/spmb` |
| Secret | `SSH_PRIVATE_KEY` | Private key whose public key is in `deploy`'s `authorized_keys` |
| Secret | `SSH_KNOWN_HOSTS` | `<ssh-host> <type> <key>` built on the server (below); `ssh-keyscan` cannot pass the tunnel |
| Secret | `CF_ACCESS_CLIENT_ID` / `CF_ACCESS_CLIENT_SECRET` | Cloudflare Access service token |

Deploy key and host key, made on the server so no extra tools are needed on the admin's laptop:

```bash
ssh-keygen -t ed25519 -N "" -C "github-deploy" -f ~/spmb_deploy
sudo install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
cat ~/spmb_deploy.pub | sudo tee -a /home/deploy/.ssh/authorized_keys
sudo chown deploy:deploy /home/deploy/.ssh/authorized_keys && sudo chmod 600 /home/deploy/.ssh/authorized_keys
ssh -i ~/spmb_deploy -p <ssh-port> deploy@127.0.0.1 'sudo -n systemctl reload php8.3-fpm && echo OK'
awk '{print "<ssh-host> "$1" "$2}' /etc/ssh/ssh_host_ed25519_key.pub   # → SSH_KNOWN_HOSTS
```

Copy `~/spmb_deploy` into `SSH_PRIVATE_KEY` (SFTP download, not terminal copy, so no line breaks are lost), keep a
copy in the password manager, then `shred -u ~/spmb_deploy`. The workflow connects on the default port through the
tunnel, so the known_hosts line carries the bare host name, without the custom port.

Run **Actions → Deploy → Run workflow** with the ref to deploy.

## What a deploy does

1. Builds a release (Composer `--no-dev`, frontend assets).
2. Uploads it to `releases/<timestamp>-<sha>/`.
3. `deploy/activate.sh`: links `shared/.env` and `shared/writable`, runs
   `php spark migrate --all` and `php spark optimize`, atomically switches
   `current`, reloads PHP-FPM, keeps the last 5 releases.

Rollback: `ln -sfn /var/www/spmb/releases/<previous> /var/www/spmb/current && sudo systemctl reload php8.3-fpm`
(database migrations are not rolled back automatically).

## Backups (phase 6)

Daily `mysqldump --single-transaction` + `writable/uploads` archive, kept 14 days
on the server and copied off-site; monthly restore drill on staging.

## First client: SMP NU Al Ma'ruf Kudus

| Item | Value |
| --- | --- |
| Domain | `spmbsmpnualmarufkudus.web.id` (`app.baseURL = 'https://spmbsmpnualmarufkudus.web.id/'`) |
| School name (Pengaturan → Identitas Sekolah) | SMP NU Al Ma'ruf Kudus |
| Installation | One installation and database for this school (D-050) |
| Cloudflare | Arumi Studios' account (developer); the domain's nameservers point there |
| Branding pack | `clients/smp-nu-al-maruf-kudus/branding` (logos, favicon, app icon, share image, green `#005c2f`, LP Ma'arif NU footer logo) |
| VPS | Ubuntu 24.04 with sshd on a provider-set custom port (not 22), first login as root with a password (Bitvise on Windows); IP and port are in the provider panel, not in the repository |

## Go-live for the school's review (first client)

The school checks the site on its own domain before registration opens; it enters its waves itself
(no wave = the online form shows "pendaftaran belum dibuka").

| # | Step | Who |
| --- | --- | --- |
| 1 | A VPS with Ubuntu Server 24.04 (2 vCPU, 2–4 GB RAM, 40 GB disk is enough for the first year) | Arumi Studios |
| 2 | Add `spmbsmpnualmarufkudus.web.id` to the developer's Cloudflare account and set the registrar's nameservers to Cloudflare's | Arumi Studios |
| 3 | Add the admin's SSH public key to `~/.ssh/authorized_keys` of root or a sudo user (otherwise the script leaves password login on). On the server: `sudo DOMAIN=spmbsmpnualmarufkudus.web.id bash provision.sh` (upload the script by SFTP; strip CRLF with `sed -i 's/\r$//'` if edited on Windows). The SSH port stays open until step 5 | Arumi Studios / Claude with SSH access |
| 4 | Cloudflare Zero Trust → Tunnels: create a tunnel, install it on the server, public hostnames `spmbsmpnualmarufkudus.web.id` → `http://127.0.0.1:80` and `ssh.spmbsmpnualmarufkudus.web.id` → `ssh://127.0.0.1:<ssh-port>` (the custom port printed by `provision.sh`); Access application with a service token on the SSH hostname; SSL/TLS "Full", "Always Use HTTPS" on | Arumi Studios |
| 5 | Log in through the tunnel (step 8 of "Server preparation"), then close the temporary SSH rule: `sudo ufw delete allow <ssh-port>/tcp` | Arumi Studios |
| 6 | GitHub → Environments → `production`: variables and secrets from the table above (deploy key and known_hosts line made on the server) | Arumi Studios |
| 7 | Actions → Deploy → Run workflow on `main` | Arumi Studios |
| 8 | On the server in `/var/www/spmb/current`: `sudo -u deploy php spark spmb:install` (Super Admin; also loads the national SD/MI list, ~1 min), then `sudo -u deploy php spark spmb:branding clients/smp-nu-al-maruf-kudus/branding` | Arumi Studios |
| 9 | Create the Admin Sekolah account (Pengguna → Tambah), hand over the temporary password in person; the school fills in address/contact, waves, documents and card settings | School |

While the school reviews, no wave is open, so nobody can register by accident. Do not use `--demo` on
the server. Later schools get their own `clients/<school>/` folder; when there are several, deploy only
the school's own folder with its installation.

