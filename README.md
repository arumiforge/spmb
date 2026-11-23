# SPMB — Sistem Penerimaan Murid Baru

White-label, modular school admission system by **Arumi Studios**.
First deployment: SMP NU Al Ma'ruf Kudus, academic year 2027/2028.

Tracks every applicant from registration (online and offline, one database) →
registration card with QR → placement test scheduling and attendance →
re-registration and uniform → committee dashboard and reports.

## Status

| Done | Next |
| --- | --- |
| Platform core (sign-in, roles, 2FA, audit log, white label, modules, guides) | Go-live on the production server (`docs/deployment.md` → "First server") |
| Registration: waves, online/offline form, documents, card with QR, status check | Phase 4: re-registration, uniform, reports and exports |
| Placement test: rooms, calendar, sessions, allocation, attendance by QR | Visual assets from Codex (`ai/README.md`) |
| Public Tanya Jawab page, Lucide icons and visual rules, Docker deployment | |

Details: [`docs/roadmap.md`](docs/roadmap.md) and [`docs/progress.md`](docs/progress.md).

## Stack

CodeIgniter 4.7 · PHP 8.5.13+ · MySQL 8.4 · Nginx + PHP-FPM · Node.js 24 (asset build) ·
Tailwind CSS v4 + Flowbite · Lucide icons · ApexCharts

Production and staging run as two Docker Compose stacks on one Ubuntu Server 24.04 VPS. The server
pulls and builds from `main`; Cloudflare Tunnel serves the sites and Cockpit gives a browser console.
Only SSH is open, with key-only login. Local development uses Windows + Laragon (Nginx, MySQL).

## Quick start (development)

```bash
composer install
npm ci && npm run build
cp env .env        # set CI_ENVIRONMENT = development, app.baseURL, database.default.*
php spark spmb:install --demo
php spark serve
```

Full setup, including Laragon on Windows and the test database: [`docs/development.md`](docs/development.md).
Checks: `composer test` (PHPUnit, about six minutes) and `composer lint`.

## Deployment (summary)

```bash
spmb deploy staging       # build main, back up, migrate, health check
spmb deploy production    # promote the image that runs on staging
```

Server setup, Cloudflare Tunnel, backups, rollback and moving servers: [`docs/deployment.md`](docs/deployment.md).
Example settings files: [`deploy/env/`](deploy/env/).

## Documentation

- [docs/](docs/README.md) — brief, requirements, architecture, decisions, roadmap, deployment, modules
- [docs/development.md](docs/development.md) — local setup, conventions, git rules
- [docs/cloud-environment.md](docs/cloud-environment.md) — Claude Code cloud environment for any Claude account
- [CLAUDE.md](CLAUDE.md) — rules for AI coding agents working in this repo
- [AGENTS.md](AGENTS.md) — rules for Codex and other agents
- [ai/](ai/README.md) — Codex briefs for visual assets (illustrations, patterns, special buttons, animation)
- [CHANGELOG.md](CHANGELOG.md) — what changed

## License

Proprietary — © 2026 Arumi Studios, all rights reserved. Use, copying or distribution
requires a written agreement with Arumi Studios; see [LICENSE](LICENSE).
Third-party components keep their own licences: [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
Flowbite Pro is used under Arumi Studios' commercial licence and must never be redistributed.
