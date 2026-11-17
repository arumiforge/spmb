# Decision Log

Append only. Supersede rather than edit. Format: see `.claude/skills/spmb-docs`.

## D-001 — Core stack (2026-11-17)
Status: accepted
Context: Defined by Arumi Studios.
Decision: CodeIgniter 4, MySQL, Nginx, Ubuntu Server 24.04, Cloudflare Tunnel, SSH, Tailwind + Flowbite.
Consequences: PHP 8.3 (Ubuntu 24.04 default) is the target runtime; CI runs on 8.3.

## D-002 — Appstarter layout, framework via Composer (2026-11-17)
Status: accepted
Context: The repo started as the CI4 framework distribution with `system/` committed.
Decision: Use the appstarter layout; `codeigniter4/framework` is a Composer dependency in `vendor/`.
Consequences: Framework upgrades via `composer update`; `system/` removed from git.

## D-003 — Sole contributor, no AI attribution (2026-11-17)
Status: accepted
Context: Arumi Studios must be the only contributor on GitHub.
Decision: All commits authored by `Arumi Studios <arumiforge@gmail.com>`; no co-author or AI trailers in commits or PRs; enforced by `.githooks/commit-msg` and the `Commit policy` workflow. No `dependabot.yml` version-update PRs (they would add a bot contributor); enable Dependabot security alerts only.
Consequences: Dependency updates are done manually in regular maintenance commits.

## D-004 — One installation per school (2026-11-17)
Status: proposed (interview round 1, Q1)
Context: The product is white label and may be sold to other schools.
Decision: Single-tenant: each school gets its own deployment and database; branding and rules come from settings.
Consequences: Simpler security and data isolation (important for student personal data); updates rolled out per deployment.

## D-005 — Modular structure (2026-11-17)
Status: accepted
Decision: Features live in `app/Modules/<Name>` under namespace `Modules\`, with routes, permissions, menu, migrations, language files and guides per module; modules can be disabled.

## D-006 — Authentication and 2FA (2026-11-17)
Status: accepted (implemented in phase 1: Shield 1.4, TOTP service; login screen wiring pending)
Decision: CodeIgniter Shield for sessions, groups and permissions; custom TOTP action using `pragmarx/google2fa`; 2FA optional per user, enforceable per role by Super Admin; recovery codes.

## D-007 — Documents, export and QR libraries (2026-11-17)
Status: proposed
Decision: Dompdf (PDF), PhpSpreadsheet (XLSX import/export, CSV), chillerlan/php-qrcode (QR), CI4 Image + GD for resize/WebP.

## D-008 — Applicants have no account (2026-11-17)
Status: proposed (interview round 1, Q3)
Decision: Applicants check status, correct data and reprint the card with registration number + date of birth (rate-limited), plus a private link sent after registration.
Consequences: No password resets for parents; lower support load.

## D-009 — Light visitor analytics built in (2026-11-17)
Status: proposed (interview round 1, Q5)
Decision: Own `Analitik` module: page views, daily unique visitors (salted daily hash, no raw IP stored), top pages, referrers, device type. No third-party tracking script.

## D-010 — UI kit (2026-11-17)
Status: accepted
Decision: Tailwind CSS v4 + open-source Flowbite components; licensed Flowbite Pro templates (in `flowbite/`) used as markup reference. The repository stays private while it contains Pro source.

## D-011 — Release-based deploy through Cloudflare Access (2026-11-17)
Status: accepted
Decision: GitHub Actions builds a release, uploads it over SSH via `cloudflared access ssh` with a service token, and `deploy/activate.sh` links shared `.env`/`writable`, migrates and switches the `current` symlink. Manual trigger only.
Consequences: Server needs no GitHub access and no open inbound ports; rollback = repoint `current`.

## D-012 — Previous-school normalisation (2026-11-17)
Status: proposed
Decision: `ref_sekolah` table seeded from public Dapodik data at https://dapo.kemendikdasmen.go.id/ (SD, Central Java) or https://sekolah.data.kemendikdasmen.go.id/ (alternative source) and an XLSX import for MI and out-of-province schools; free-text fallback mapped later by admins.

## D-013 — Indonesian locale and staff-only accounts (2026-11-17)
Status: accepted
Decision: App locale `id`, time zone `Asia/Jakarta`, `codeigniter4/translations` for framework messages. Shield routes moved to `/masuk`, `/keluar`, `/verifikasi` (route names unchanged). Self-registration and magic-link login are disabled; staff accounts are created by an admin or `spmb:install`.
Consequences: Password reset for staff is done by an admin until email delivery is configured (magic link can be enabled then).

## D-014 — Roles are configuration, assignments are data (2026-11-17)
Status: proposed
Context: FR asks that Admin Sekolah can edit role permissions. Shield defines groups and the permission matrix in `Config\AuthGroups`; user ↔ role assignments and per-user extra permissions are stored in the database.
Decision: For launch, roles and their permissions are fixed in config (reviewed with the school); Admin Sekolah assigns roles and can grant individual extra permissions per user. A database-backed role editor is deferred.
Consequences: Changing what a role can do requires a release; adding a role is a small code change with tests (`AuthGroupsTest`).

## D-015 — Audit trail through model callbacks (2026-11-17)
Status: accepted
Decision: `audit_logs` table; models opt in with the `Auditable` trait (insert/update/delete with only changed columns as old/new JSON); explicit events via `service('audit')->log()`. Passwords, secrets and tokens are redacted.
Consequences: Writes that bypass models (raw query builder) are not audited — modules must write applicant data through models.

## D-016 — Two-factor storage (2026-11-17)
Status: accepted
Decision: `user_two_factor` table: TOTP secret encrypted with the app key, 8 single-use recovery codes stored as password hashes, last used time step stored to block code replay. Codes accepted within ±1 time step (30 s).
Consequences: Rotating `encryption.key` invalidates all 2FA setups — document in the key rotation procedure.

## D-017 — Flowbite Pro as markup reference (2026-11-17)
Status: accepted (refines D-010)
Context: The user chose Flowbite Pro for the UI. The licensed templates (admin dashboard v2.2.0, landing pages v1.1.0) are Hugo sources in `flowbite/`.
Decision: Use them only as a markup reference: copy and adapt the minimum markup into `app/Views`, never publish Pro source or assets, never modify `flowbite/`. The repository stays private. Runtime uses only the open-source `flowbite` npm package. Tailwind source detection is disabled (`source(none)`) so `flowbite/` is never compiled into our CSS.
Consequences: Making the repository public requires removing `flowbite/` first (and from history).

## D-018 — Frontend build and white-label colours (2026-11-17)
Status: accepted
Decision: `@tailwindcss/cli` for CSS and esbuild for JS (`app.js` everywhere, `charts.js` with ApexCharts only on chart pages), output in git-ignored `public/build/`. The primary colour scale is derived in the browser from one `--brand` variable (`color-mix()` in `@theme`), set from `setting('School.primaryColor')`.
Consequences: No per-school CSS build; requires browsers with `color-mix()` (all evergreen browsers since 2023, same baseline as Tailwind v4).

## D-019 — Module registry, menus and guides (2026-11-17)
Status: accepted
Decision: Modules are discovered from `app/Modules/*`; enabled unless listed in `setting('Features.disabled')`; Pengaturan, Pengguna, Profil and Panduan are core. Each module may provide `Config/Routes.php`, `Config/Menu.php`, `Language/id/<Name>.php` and `Guide/<peran>.md` (`semua-petugas.md` applies to every staff role). Permission keys stay in `Config\AuthGroups` (one place, tested) rather than per-module files.
Consequences: A UI to switch modules (`sistem.modul`) still has to be built; guides are versioned with the code.

## D-020 — Staff account lifecycle (2026-11-17)
Status: accepted
Decision: Admins never type passwords for others: creating an account or resetting a password generates a one-time temporary password (shown once) and forces a change at next sign-in. Deactivation uses Shield's ban status (data kept, sign-in refused, reversible); there is no hard delete in the UI. Super Admin and Admin Sekolah accounts and roles can only be managed with `pengguna.kelola-admin` (Super Admin). Admins cannot deactivate themselves or change their own roles. An admin can turn off a user's 2FA when the phone and recovery codes are lost.
Consequences: Without email delivery, temporary passwords are handed over in person.

## D-021 — Two-step verification enforcement (2026-11-17)
Status: accepted (implements D-006)
Decision: `TotpAction` (Shield login action) asks for a code only from users who turned 2FA on. Super Admin chooses roles that must use it (`setting('TwoFactor.requiredRoles')`, permission `sistem.keamanan`); members of those roles are redirected to the setup page until it is on, and cannot turn it off. Five wrong codes cancel the pending sign-in. Recovery codes are shown once after setup or regeneration.
Consequences: Enforcement happens after the first password sign-in (setup cannot be done before signing in).

## D-022 — CSRF and security headers globally (2026-11-17)
Status: accepted
Decision: `csrf` (session token) and `secureheaders` run on every request; `auth-rates` throttles `/masuk` and `/verifikasi`. In the `testing` environment CSRF and throttling are skipped so feature tests can post forms.
Consequences: Every form must include `csrf_field()`.
