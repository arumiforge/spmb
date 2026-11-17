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
Status: proposed
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
