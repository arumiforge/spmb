# Changelog

All notable changes are documented here ([Keep a Changelog](https://keepachangelog.com/en/1.1.0/)).

## [Unreleased]

### Added
- Phase 2 foundation: modules Referensi, Pendaftaran, Berkas, Kartu and Situs (registry entries, menus, public
  navigation, audit labels); migrations for `ref_sekolah`, `waves`, `registration_counters`, `applicants`,
  `document_types`, `applicant_documents`, `card_tokens` with audited models; settings defaults
  `Pendaftaran`, `Berkas`, `Kartu`, `Referensi`; permission `referensi.kelola`; applicant session grant
  (`service('applicantAccess')`); module `requires`, panels (`module_panels()`), `Config/PublicMenu.php`,
  `Config/Audit.php` and installer seeders (D-033); `docs/coordination.md` and `docs/phase-2-plan.md`.
- Dependencies `setasign/fpdf` and `phpoffice/phpspreadsheet` (MIT, D-030).
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
- Frontend build: Tailwind CSS v4, Flowbite, ApexCharts (`package.json`, `resources/`, output `public/build/`);
  primary colour scale derived from the school's colour setting.
- Layouts `admin` (full width, collapsible sidebar, mobile drawer, user menu, breadcrumbs, toasts),
  `auth` and `public`; partials for breadcrumbs, toasts, empty state, form field, status badge, pagination; dark mode.
- Indonesian sign-in screen and two-step verification step (`/verifikasi`) with Google Authenticator codes or recovery codes.
- Profile pages: change password, set up / turn off two-step verification with QR code, recovery codes shown once.
- School identity settings (`/admin/pengaturan/sekolah`) with logo and favicon upload; security policy
  (`/admin/pengaturan/keamanan`) to require two-step verification per role; permission `sistem.keamanan`.
- User management (`/admin/pengguna`): search, filters, create with temporary password, edit roles,
  deactivate/activate, reset password, reset two-step verification.
- Module registry (enable/disable via settings) and sidebar menu built from module menus and permissions.
- Guide pages `/panduan` and `/panduan/<peran>` assembled from module `Guide/` Markdown files.
- Public home page placeholder; friendly "no access" page.
- Tests for two-step flow, user management, settings, guides and UI helpers (71 tests); phase 1 finish brings the suite to 95 tests.

- Fitur page (`/admin/pengaturan/fitur`, `sistem.modul`) to switch feature modules on/off, audited; core modules always on; module `Config/Module.php` metadata (D-025).
- Audit log viewer Riwayat Perubahan (`/admin/riwayat`, `audit.view`): filters by staff, activity, data type, record and date range in the query string, pagination, detail page with readable before/after values, stacked cards on mobile.
- Friendly Indonesian 404 and 500 pages in the public layout, admin variant for signed-in staff, self-contained fallback (D-026).
- Guide sections for Fitur (Super Admin) and Riwayat Perubahan (Admin Sekolah); icons `grid`, `clock`, `compass`, `arrow-right`.
- Illustration briefs 001–012 rewritten in detail for a painted storybook style with a recurring cast; `ai/CHATGPT.md` lets ChatGPT generate the queue from the repository (D-027) and deliver it by pull request into `ai/generated/` for Claude to install (D-028); originals 001–012 delivered (PR #7).
- Painted illustrations installed (WebP in `public/assets/img/`, dark variants for dark mode) on the home page, sign-in, empty states, 404/500 pages and guide pages; `illustration()` view helper.
- `THIRD_PARTY_NOTICES.md` listing third-party licences.

### Changed
- Project licence is now proprietary (© Arumi Studios), replacing the inherited CodeIgniter MIT notice (D-017).
- CSRF protection and secure headers enabled for every request; sign-in and verification are rate limited.
- `composer test` runs PHPUnit without coverage (same as CI).
- Locale `id`, time zone `Asia/Jakarta`, Indonesian framework messages (`codeigniter4/translations`); URLs without `index.php`.
- Switched from the CodeIgniter framework distribution to the appstarter layout; framework 4.7 installed via Composer.
