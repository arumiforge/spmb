# Development

## Dev install

Requirements (D-069): PHP 8.5.13+ (intl, mbstring, mysqli, gd, zip, exif), Composer 2, MySQL 8.4,
Node.js 24 + npm. The usual setup is Windows + Laragon (below); production runs in Docker
(`docs/deployment.md`).

### Windows + Laragon

1. Laragon (Full) with Nginx and MySQL. Add the runtimes Laragon does not ship yet:
   - **PHP 8.5.13+**: download the *NTS x64* zip from windows.php.net, unzip into
     `C:\laragon\bin\php\php-8.5.x-nts-Win32-vs17-x64\`, then Menu → PHP → Version → pick it. In that
     folder copy `php.ini-development` to `php.ini` and enable `extension=intl`, `mbstring`, `mysqli`,
     `gd`, `zip`, `exif`, `openssl`, `curl`, `fileinfo`; set `upload_max_filesize = 10M`,
     `post_max_size = 12M`, `date.timezone = Asia/Jakarta`.
   - **MySQL 8.4**: unzip the MySQL 8.4 *Windows ZIP* into `C:\laragon\bin\mysql\`, Menu → MySQL →
     Version → pick it (Laragon initialises the data folder on first start).
   - **Node.js 24**: unzip the Node 24 Windows zip into `C:\laragon\bin\nodejs\node-v24…\` (or install it
     normally), Menu → Node.js → Version.
   - Check in Laragon's terminal: `php -v` (8.5.13+), `mysql --version` (8.4), `node -v` (v24).
2. Clone into `C:\laragon\www\spmb`. Laragon's auto virtual host serves `http://spmb.test`; make sure
   the Nginx site's root is the `public/` folder (Menu → Nginx → sites-enabled → `auto.spmb.test.conf`,
   `root "C:/laragon/www/spmb/public";`), or use `php spark serve` on `http://localhost:8080` instead.
3. Continue with the steps below (databases, `.env`, `spmb:install --demo`, `npm ci && npm run build`).
   Use `app.baseURL = 'http://spmb.test/'` when Nginx serves the site.
4. Git: set `git config core.autocrlf input` so shell scripts keep LF line endings (the server scripts
   in `deploy/server/` fail with CRLF).

### Optional: the production images locally (Docker Desktop)

To check the Docker build before a deploy, with Docker Desktop (WSL 2) or any Docker Engine:

```bash
docker build -f docker/Dockerfile --target app -t spmb-app:local .
docker build -f docker/Dockerfile --target web -t spmb-web:local .
```

To run the stack, make a folder outside the repository with a `stack.env` (`SPMB_ENV=local`,
`SPMB_PORT=8090`, `SPMB_TAG=local`, `SPMB_DB_PASSWORD=…`, `SPMB_DB_ROOT_PASSWORD=…`) and an `app.env`
(as in `docs/deployment.md`, with `database.default.hostname = db`, `app.baseURL = 'http://127.0.0.1:8090/'`
and an `encryption.key`), copy `deploy/compose.yaml` there and run
`docker compose --env-file stack.env up -d`, then
`docker compose --env-file stack.env exec app php spark migrate --all`.
Day-to-day development stays on Laragon.

```bash
git clone git@github.com:arumiforge/spmb.git && cd spmb
git config user.name "Arumi Studios"
git config user.email "arumiforge@gmail.com"
composer install          # also sets core.hooksPath=.githooks
```

Create the databases (once):

```sql
CREATE DATABASE spmb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE spmb_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'spmb'@'localhost' IDENTIFIED BY 'spmb';
GRANT ALL ON spmb.* TO 'spmb'@'localhost';
GRANT ALL ON spmb_test.* TO 'spmb'@'localhost';
```

Copy `env` to `.env` and set:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

database.default.hostname = 127.0.0.1
database.default.database = spmb
database.default.username = spmb
database.default.password = spmb
database.default.DBDriver = MySQLi
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci

# Tests run against MySQL too (same as CI)
database.tests.hostname = 127.0.0.1
database.tests.database = spmb_test
database.tests.username = spmb
database.tests.password = spmb
database.tests.DBDriver = MySQLi
database.tests.charset = utf8mb4
database.tests.DBCollat = utf8mb4_unicode_ci
```

Then:

```bash
php spark key:generate                 # writes encryption.key into .env
php spark spmb:install --demo          # migrations + first Super Admin (prompted) + demo accounts
npm ci && npm run build                # Tailwind/Flowbite assets into public/build/
php spark serve                        # http://localhost:8080/masuk
```

`spmb:install` is idempotent: run it again after pulling to apply new migrations.
Options: `--username --email --name --password` (non-interactive), `--demo`,
`--demo-password`. Demo accounts are refused in production. CodeIgniter options take
the value after a **space** (`--username superadmin`, not `--username=superadmin`);
otherwise the installer falls back to prompting. The password must not contain parts of
the username, email or name. Non-interactive example:

```bash
php spark spmb:install --demo --username superadmin --email super@demo.test \
  --name "Pengelola Sistem" --password 'Pilih-Sandi-Kuat-2027!' </dev/null
```

### Demo accounts (`--demo`, development only)

Password `Demo-SPMB-2027` unless `--demo-password` is given. Sign in with the email.

| Email | Role |
| --- | --- |
| admin@demo.test | Admin Sekolah |
| panitia@demo.test | Panitia |

## Claude cloud environment (per account)

Environments are per Claude account and are not stored in the repo. In each account,
create a personal environment (Settings → Environments) named `spmb`, attached to
`arumiforge/spmb`, with package managers allowed, these hosts allowed:
`github.com`, `api.github.com`, `codeload.github.com`, `dapo.kemendikdasmen.go.id`,
`sekolah.data.kemendikdasmen.go.id`, and this setup script:

```bash
set -e
apt-get update -qq
DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mysql-server
service mysql start
mysql -e "CREATE DATABASE IF NOT EXISTS spmb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -e "CREATE DATABASE IF NOT EXISTS spmb_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -e "CREATE USER IF NOT EXISTS 'spmb'@'localhost' IDENTIFIED BY 'spmb'"
mysql -e "GRANT ALL ON spmb.* TO 'spmb'@'localhost'; GRANT ALL ON spmb_test.* TO 'spmb'@'localhost'"
if [ -d spmb ]; then (cd spmb && composer install --no-interaction --no-progress); fi
```

A session then only needs `.env` (see above), `php spark key:generate` and
`php spark spmb:install --demo`.

## Commands

| Command | Purpose |
| --- | --- |
| `composer test` | PHPUnit (`--no-coverage`, as in CI) |
| `npm run build` | Build CSS/JS into `public/build/` (run after changing views: Tailwind scans them) |
| `npm run dev` | Rebuild CSS/JS on change |
| `composer lint` / `composer fix` | Check / apply CodeIgniter coding standard |
| `php spark routes` | List routes (check Indonesian slugs) |
| `php spark spmb:install` | Migrations (all namespaces) + first Super Admin; idempotent |
| `php spark migrate --all` | Run migrations of app, packages and all modules |
| `php spark migrate:rollback -b 0 -f` | Roll everything back (dev only) |

## Git rules

- Branch from `main`; open a PR; CI and the commit policy must pass.
- Conventional Commits in English, scope = module (`feat(pendaftaran): ...`).
- Author is always Arumi Studios; no co-author or AI attribution lines (D-003).

## Conventions

- Docs and code comments: English. UI, URLs, guides: friendly Indonesian.
- Module structure, permissions, uploads, exports: `.claude/skills/spmb-module/SKILL.md`.
- Views, layout, breadcrumbs, theming: `.claude/skills/spmb-ui/SKILL.md`.
- Composer as root (cloud containers): `export COMPOSER_ALLOW_SUPERUSER=1`.
- Feature tests that span requests (sign-in → verification) call `$this->withSession()` before each
  follow-up request to carry `$_SESSION` forward (see `tests/feature/TwoFactorFlowTest.php`).
- UI wording and glossary: `.claude/skills/spmb-copy/SKILL.md`.
- Illustrations: `ai/README.md`.
