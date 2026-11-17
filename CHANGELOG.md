# Changelog

All notable changes are documented here ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/)).

## [Unreleased]

### Added
- Phase 0 foundation: project rules (`CLAUDE.md`), repo agent skills (`.claude/skills/`),
  staged documentation (`docs/`), interview round 1, AI image prompt workflow (`ai/`).
- `docs/progress.md` and the `spmb-session` skill for handoff between sessions and Claude accounts.
- GitHub Actions: CI (lint, coding standard, PHPUnit on MySQL 8.0), commit policy, manual release deploy over Cloudflare Access SSH.
- `.githooks/commit-msg` enforcing Arumi Studios authorship and no AI attribution.
- `Modules\` namespace for feature modules (`app/Modules`).

- Auth with CodeIgniter Shield: seven staff roles with Indonesian titles, permission matrix, routes `/masuk`, `/keluar`, `/verifikasi`; self-registration and magic links disabled.
- Migrations: profile fields on `users`, `audit_logs`, `user_two_factor` (plus Shield and Settings tables).
- Audit trail service and `Auditable` model trait (old/new values, redaction of secrets).
- TOTP two-factor service with encrypted secret, replay protection and single-use recovery codes.
- `php spark spmb:install` installer (idempotent) and demo account seeder for development.
- `School` config for white-label identity defaults; protected `/admin` dashboard placeholder.
- Tests: roles/permissions matrix, audit trail, 2FA, demo seeder, auth routes (29 tests on MySQL).

- `THIRD_PARTY_NOTICES.md` listing third-party licences.

### Changed
- Project licence is now proprietary (© Arumi Studios), replacing the inherited CodeIgniter MIT notice (D-017).
- Locale `id`, time zone `Asia/Jakarta`, Indonesian framework messages (`codeigniter4/translations`); URLs without `index.php`.
- Switched from the CodeIgniter framework distribution to the appstarter layout; framework 4.7 installed via Composer.
