# Claude cloud environment (any Claude account)

Claude accounts share nothing but this repository (`spmb-session`). Each account that works
on SPMB in Claude Code on the web needs its own cloud environment, set up once as below. The
setup itself lives in the repository (`.claude/cloud/setup.sh`), so every account stays in step:
change the script here, not in each account.

Official reference: <https://code.claude.com/docs/en/cloud-environments>.

## One-time setup in a new account

1. **GitHub**: connect GitHub in claude.ai with access to `arumiforge/spmb` (private), and make
   sure the Claude GitHub App is installed on the repository.
2. **Create the environment**: in a Claude Code session, open the cloud environment menu in the
   session's title bar → add a new environment (or Edit an existing one).

| Setting | Value |
| --- | --- |
| Name | `SPMB` |
| Repositories | `arumiforge/spmb` |
| Network access | Full (unrestricted). If the account must use Limited, allow at least the hosts listed below and keep "Allow package managers" ticked |
| Environment variables | `COMPOSER_ALLOW_SUPERUSER=1` and `TZ=Asia/Jakarta` (no secrets here: they are stored in plain text) |
| Setup script | the bootstrap line below |

Setup script (paste exactly; it runs the script from the repository):

```bash
if [ -f spmb/.claude/cloud/setup.sh ]; then bash spmb/.claude/cloud/setup.sh; else echo "spmb/.claude/cloud/setup.sh not found"; fi
```

Until the branch that adds `.claude/cloud/setup.sh` is merged into `main`, a session started from
`main` will print "not found". Either start sessions from a branch that has the file, or paste the
whole content of `.claude/cloud/setup.sh` into the field instead of the bootstrap.

3. **Start a new session** on the environment. The setup script runs only when a container is built
   fresh; a resumed session keeps its old container. When it finishes:
   - `php -v` shows PHP 8.5.13 or newer, `node -v` shows v24;
   - MySQL runs with databases `spmb` (dev) and `spmb_test` (tests), user `spmb`/`spmb`;
   - `.env` is created for development, dependencies are installed, assets are built;
   - `spmb:install --demo` has run; the dev Super Admin password is in `/root/spmb-superadmin.txt`
     inside that container only.
4. **Begin work with the `spmb-session` skill** (git identity Arumi Studios, hooks, `docs/progress.md`).

## What the script does

`.claude/cloud/setup.sh` (runs as root from the folder that holds the checkout):

1. Installs MySQL server and `software-properties-common`.
2. Adds the `ppa:ondrej/php` PPA and installs `php8.5-{cli,intl,mbstring,mysql,gd,zip,xml,curl}`,
   then makes `php` point to PHP 8.5 (D-069).
3. Installs Node.js 24 from NodeSource and points the image's own `node`/`npm`/`npx` links
   (`/opt/node22/bin`, `/usr/local/bin`) at it.
4. Starts MySQL, creates the databases and the `spmb` user.
5. In `spmb/`: `composer install`, `npm ci`, `npm run build`, writes `.env` if missing (development
   values, local database only), `php spark key:generate`, `php spark spmb:install --demo`.

It is idempotent: running it again changes nothing that is already in place.

## Hosts needed with Limited network access

Package registries (Packagist, npm) are covered by "Allow package managers". Add:

| Host | Why |
| --- | --- |
| `github.com`, `api.github.com`, `codeload.github.com`, `objects.githubusercontent.com` | Composer downloads package archives from GitHub |
| `ppa.launchpadcontent.net`, `api.launchpad.net`, `keyserver.ubuntu.com` | PHP 8.5 from the ondrej PPA and its signing key |
| `deb.nodesource.com` | Node.js 24 |
| `registry-1.docker.io`, `auth.docker.io`, `production.cloudflare.docker.com` | Docker images when testing the deployment image (D-066) |
| `dapo.kemendikdasmen.go.id`, `sekolah.data.kemendikdasmen.go.id` | School data importer (only when that task needs it) |

Docker Hub can move its download hosts; if a pull is refused, add the host named in the error.

## When something fails

- **"Setup script failed"** when a session starts: the session cannot recover; fix the cause, then
  start a new session. Read the last lines of the error (for example a Composer platform message
  means the PHP version is wrong).
- **Composer says the PHP version does not satisfy `^8.5.13`**: the environment still runs the old
  inline script with the image's PHP 8.3; replace the setup script with the bootstrap above.
- **MySQL stopped** after the container was idle: `service mysql start`.
- Keep this file and `.claude/cloud/setup.sh` in step with `composer.json`, `package.json` and CI
  whenever runtime versions change.
