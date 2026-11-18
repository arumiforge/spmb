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

No inbound ports open on the server firewall (`ufw default deny incoming`).

## Server preparation (once)

1. Packages: `nginx`, `mysql-server`, `php8.3-fpm php8.3-{cli,intl,mbstring,mysql,gd,zip,xml,curl}`, `unzip`, `cloudflared`.
2. Deploy user (e.g. `deploy`), member of `www-data`; SSH key auth only; password login disabled.
3. Sudoers rule for reloads only: `deploy ALL=NOPASSWD: /usr/bin/systemctl reload php8.3-fpm`.
4. Directories: `/var/www/spmb/{releases,shared/writable}`; create `/var/www/spmb/shared/.env` from `env` with production values (`CI_ENVIRONMENT = production`, `app.baseURL`, database credentials, `encryption.key` from `php spark key:generate --show`).
5. MySQL: database `spmb`, user with privileges on that database only, `utf8mb4_unicode_ci`.
   After the first release is uploaded, run `php spark spmb:install` once in `current/`
   to create the first Super Admin (later deploys only run migrations).
6. Nginx server block: `root /var/www/spmb/current/public;` `try_files $uri $uri/ /index.php?$query_string;`, PHP via FPM socket, deny dotfiles, `client_max_body_size 12m;` matching the upload limit (php-fpm: `upload_max_filesize = 10M`, `post_max_size = 12M`, in line with `Berkas.imageUploadLimitKb` — phone photos are 3–8 MB), `real_ip_header CF-Connecting-IP` with Cloudflare ranges.
7. Cloudflare Tunnel: ingress `https://<domain>` → `http://localhost:80` and `ssh://<ssh-host>` → `ssh://localhost:22`; protect the SSH hostname with a Cloudflare Access application allowing a **service token** (for CI) and the admins' emails.

## GitHub configuration

Settings → Environments → `production` (add required reviewers if wanted):

| Type | Name | Value |
| --- | --- | --- |
| Variable | `SSH_HOST` | Access-protected SSH hostname |
| Variable | `SSH_USER` | `deploy` |
| Variable | `DEPLOY_PATH` | `/var/www/spmb` |
| Secret | `SSH_PRIVATE_KEY` | Private key whose public key is in `deploy`'s `authorized_keys` |
| Secret | `SSH_KNOWN_HOSTS` | Output of `ssh-keyscan` for the host, taken from a trusted machine |
| Secret | `CF_ACCESS_CLIENT_ID` / `CF_ACCESS_CLIENT_SECRET` | Cloudflare Access service token |

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
