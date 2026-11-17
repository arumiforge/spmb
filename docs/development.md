# Development

## Setup

```bash
git clone git@github.com:arumiforge/spmb.git && cd spmb
git config user.name "Arumi Studios"
git config user.email "arumiforge@gmail.com"
composer install          # also sets core.hooksPath=.githooks
cp env .env               # set CI_ENVIRONMENT = development, database.default.*
php spark migrate --all
php spark serve
```

Tests use SQLite in memory by default; CI runs them against MySQL 8.0.

## Commands

| Command | Purpose |
| --- | --- |
| `composer test` | PHPUnit |
| `composer lint` / `composer fix` | Check / apply CodeIgniter coding standard |
| `php spark routes` | List routes (check Indonesian slugs) |
| `php spark migrate --all` | Run migrations of app and all modules |

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
