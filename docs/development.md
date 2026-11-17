# Development

## Dev install

Requirements: PHP 8.3 (intl, mbstring, mysqli, gd, zip), Composer 2, MySQL 8.0.

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
php spark serve                        # http://localhost:8080/masuk
```

`spmb:install` is idempotent: run it again after pulling to apply new migrations.
Options: `--username --email --name --password` (non-interactive), `--demo`,
`--demo-password`. Demo accounts are refused in production.

### Demo accounts (`--demo`, development only)

Password `Demo-SPMB-2027` unless `--demo-password` is given. Sign in with the email.

| Email | Role |
| --- | --- |
| admin@demo.test | Admin Sekolah |
| kepsek@demo.test | Kepala Sekolah |
| panitia@demo.test | Panitia Pendaftaran |
| petugastes@demo.test | Petugas Tes |
| petugasdaftarulang@demo.test | Petugas Daftar Ulang |
| petugasseragam@demo.test | Petugas Seragam |

## Commands

| Command | Purpose |
| --- | --- |
| `composer test` | PHPUnit |
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
- UI wording and glossary: `.claude/skills/spmb-copy/SKILL.md`.
- Illustrations: `ai/README.md`.
