# SPMB — Sistem Penerimaan Murid Baru

White-label, modular school admission system by **Arumi Studios**.
First deployment: SMP NU Al Ma'ruf Kudus, academic year 2027/2028.

Tracks every applicant from registration (online and offline, one database) →
registration card with QR → placement test scheduling and attendance →
re-registration and uniform → committee dashboard and reports.

## Stack

CodeIgniter 4.7 · PHP 8.3 · MySQL 8 · Nginx + PHP-FPM · Ubuntu Server 24.04 ·
Cloudflare Tunnel · Tailwind CSS v4 + Flowbite

## Quick start

```bash
composer install
cp env .env        # set CI_ENVIRONMENT and database.default.*
php spark migrate --all
php spark serve
```

## Documentation

- [docs/](docs/README.md) — brief, requirements, architecture, decisions, roadmap, deployment
- [docs/development.md](docs/development.md) — local setup, conventions, git rules
- [CLAUDE.md](CLAUDE.md) — rules for AI coding agents working in this repo
- [ai/](ai/README.md) — Codex briefs for visual assets (illustrations, patterns, special buttons, animation)
- [AGENTS.md](AGENTS.md) — rules for Codex and other agents

## License

Proprietary — © 2026 Arumi Studios, all rights reserved. Use, copying or distribution
requires a written agreement with Arumi Studios; see [LICENSE](LICENSE).
Third-party components keep their own licences: [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
Flowbite Pro is used under Arumi Studios' commercial licence and must never be redistributed.
