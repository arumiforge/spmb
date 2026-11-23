# SPMB — Agent Guide

School admission system (Sistem Penerimaan Murid Baru) built by **Arumi Studios**.
First client: SMP NU Al Ma'ruf Kudus, academic year 2027/2028. The product is
white-label: nothing school-specific is hard-coded.

**Start every session with the `spmb-session` skill** (new Claude account: set up the cloud
environment from `docs/cloud-environment.md` first) and read `docs/progress.md`
(current state, claimed branches, next steps); update it before you finish. Work
happens across several Claude accounts that share nothing but this repository.
Docs index: `docs/README.md`. Decisions live in `docs/decisions.md`; open questions
in `docs/interview/`. Never implement against an assumption that contradicts a
recorded decision — ask, or record a new decision.

## Language rules (strict)

| Surface | Language |
| --- | --- |
| Replies to the user in chat | **English**, even when the user writes Indonesian |
| `docs/`, `*.md`, code comments, commit messages, PRs | English |
| UI text, URLs/route slugs, emails, PDFs, guide pages (`/panduan`) | Friendly, plain **Indonesian** |
| Super-admin-only screens | Technical Indonesian/English terms allowed |

Users are non-technical school staff, students and parents. Avoid jargon outside
the super admin area — see the `spmb-copy` skill for the glossary.

## Commit and contributor rules (strict)

- Author and committer: `Arumi Studios <arumiforge@gmail.com>`. Check with
  `git config user.name` / `git config user.email` before committing.
- **No AI attribution anywhere**: no `Co-Authored-By`, no `Claude-Session`, no
  "Generated with" lines in commits, PR bodies or comments. Arumi Studios is the
  only contributor.
- Enforced locally by `.githooks/commit-msg` (`git config core.hooksPath .githooks`)
  and in CI by `.github/workflows/commit-policy.yml`.
- Conventional Commits: `feat(pendaftaran): ...`, `fix:`, `docs:`, `chore:`, `ci:`.

## Stack

CodeIgniter 4.7 (PHP 8.5.13+) · MySQL 8.4 · Nginx + PHP-FPM · Node.js 24 (asset build) ·
Docker Compose on Ubuntu Server 24.04 (staging + production stacks, server pulls and builds) ·
Cloudflare Tunnel + Cockpit on the host (only SSH open, key-only) · Tailwind CSS v4 + Flowbite ·
Lucide icons. Local development: Windows + Laragon (Nginx, MySQL).
The framework is a Composer dependency (`vendor/codeigniter4/framework`); never edit `vendor/`.

## Architecture (summary — full version in `docs/architecture.md`)

- **Modular**: each feature is a module in `app/Modules/<Name>/` (Config/Routes,
  Controllers, Models, Entities, Views, Database/Migrations, Language/id, Guide).
  Modules can be switched on/off from settings. Use the `spmb-module` skill.
- **Configurable / white label**: school name, logo, colours, registration number
  format, waves, quotas, required documents — all from the `settings` table, never constants.
- **Roles & permissions**: permission-based (not role-name checks). Roles: Super Admin
  (Arumi Studios), Admin Sekolah, Panitia, and applicants without an account (D-063, D-008).
- **Security**: uploaded files outside `public/`, served through an authorised
  controller; QR codes carry a random token, never NIK/NISN; audit log for every
  change to applicant data; optional TOTP 2FA (Google Authenticator) for dashboard users.
- **Admin dashboard**: full width, responsive, friendly breadcrumbs on every page.

## Commands

```bash
composer install
php spark serve                 # local dev server
php spark spmb:install --demo   # migrations + Super Admin + demo accounts (dev)
php spark migrate --all         # run all module migrations
composer test                   # PHPUnit
composer lint                   # php-cs-fixer dry run
npm ci && npm run build         # frontend assets (Node 24)
npm run icons                   # regenerate Lucide icons after editing resources/icons.json
```

Server (Docker, staging + production): `spmb deploy staging`, `spmb deploy production`,
`spmb status`, `spmb backup <env>` — see the `spmb-deploy` skill and `docs/deployment.md`.

## Project skills (`.claude/skills/`)

| Skill | Use when |
| --- | --- |
| `spmb-session` | Start and end of every session (sync, identity, progress handoff) |
| `spmb-module` | Creating or changing a feature module |
| `spmb-ui` | Building any page/view (Flowbite, layout, breadcrumbs, responsive, visual rules, charts) |
| `spmb-copy` | Writing UI text, guide pages, error messages in Indonesian (with `bahasa-indonesia-plain`) |
| `bahasa-indonesia-plain` | Writing standard for all Indonesian text: plain language, EYD, inclusive, RASA check |
| `spmb-docs` | Updating documentation after a change (staged docs) |
| `spmb-ai-image` | An illustration, special button, pattern or other visual is needed → write a Codex brief in `ai/` |
| `spmb-deploy` | Docker image, server setup, staging/production deploys, backups, server moves |
| `spmb-interview` | Continuing the requirements interview with the user |
| `spmb-commit` | Committing, pushing, opening PRs |

## Flowbite Pro

`flowbite/` holds licensed Flowbite Pro templates used as a **reference** for
markup. The licence proof is kept by Arumi Studios outside the repository. The
repository must stay **private** while it contains Pro source. Copy only the
markup you need into `app/Views`, adapt it, and never publish Pro source.

## Licence

Proprietary, © Arumi Studios (`LICENSE`, D-017). Never add an open-source licence
header to project files. When adding a dependency, check its licence is compatible
(MIT, BSD, Apache-2.0, ISC are fine; ask before GPL/AGPL or commercial terms) and
update `THIRD_PARTY_NOTICES.md`.
