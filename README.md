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
- [ai/](ai/README.md) — ChatGPT prompts for UI illustrations

## License

Proprietary. © Arumi Studios. CodeIgniter 4 is MIT-licensed by the CodeIgniter Foundation.
Flowbite Pro templates are used under their commercial license and must not be redistributed.
