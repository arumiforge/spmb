# SPMB — Agent Guide

School admission system (Sistem Penerimaan Murid Baru) built by **Arumi Studios**.
First client: SMP NU Al Ma'ruf Kudus, academic year 2027/2028. The product is
white-label: nothing school-specific is hard-coded.

**Start every session with the `spmb-session` skill** and read `docs/progress.md`
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

CodeIgniter 4.7 (PHP 8.3) · MySQL 8 · Nginx + PHP-FPM · Ubuntu Server 24.04 ·
Cloudflare Tunnel (no open inbound ports) · SSH deploy · Tailwind CSS v4 + Flowbite.
The framework is a Composer dependency (`vendor/codeigniter4/framework`); never edit `vendor/`.

## Architecture (summary — full version in `docs/architecture.md`)

- **Modular**: each feature is a module in `app/Modules/<Name>/` (Config/Routes,
  Controllers, Models, Entities, Views, Database/Migrations, Language/id, Guide).
  Modules can be switched on/off from settings. Use the `spmb-module` skill.
- **Configurable / white label**: school name, logo, colours, registration number
  format, waves, quotas, required documents — all from the `settings` table, never constants.
- **Roles & permissions**: permission-based (not role-name checks). Roles:
  super admin, admin sekolah, then operational roles (see `docs/requirements.md`).
- **Security**: uploaded files outside `public/`, served through an authorised
  controller; QR codes carry a random token, never NIK/NISN; audit log for every
  change to applicant data; optional TOTP 2FA (Google Authenticator) for dashboard users.
- **Admin dashboard**: full width, responsive, friendly breadcrumbs on every page.

## Commands

```bash
composer install
php spark serve                 # local dev server
php spark migrate --all         # run all module migrations
composer test                   # PHPUnit
composer lint                   # php-cs-fixer dry run
```

## Project skills (`.claude/skills/`)

| Skill | Use when |
| --- | --- |
| `spmb-session` | Start and end of every session (sync, identity, progress handoff) |
| `spmb-module` | Creating or changing a feature module |
| `spmb-ui` | Building any page/view (Flowbite, layout, breadcrumbs, responsive) |
| `spmb-copy` | Writing UI text, guide pages, error messages in Indonesian |
| `spmb-docs` | Updating documentation after a change (staged docs) |
| `spmb-ai-image` | An illustration/image is needed → write a ChatGPT prompt in `ai/` |
| `spmb-interview` | Continuing the requirements interview with the user |
| `spmb-commit` | Committing, pushing, opening PRs |

## Flowbite Pro

`flowbite/` holds licensed Flowbite Pro templates used as a **reference** for
markup. The repository must stay **private** while it contains Pro source. Copy
only the markup you need into `app/Views`, adapt it, and never publish Pro source.
