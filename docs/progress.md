# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 0 — Foundation**: done. **Phase 1 — Platform core**: core merged in
[PR #1](https://github.com/arumiforge/spmb/pull/1); **phase 1 UI** in progress on `feat/phase-1-ui`.

Done in phase 1: Shield auth with 7 roles and permission matrix, Indonesian auth URLs,
audit trail, TOTP 2FA service, migrations, `php spark spmb:install` (+ `--demo`),
locale `id`, placeholder `/admin`. 29 tests green on MySQL 8.0.

## Active work

One row per open branch. Do not work on a branch claimed here by another session;
create your own branch from `main` instead.

| Branch | Session label | Task | Status |
| --- | --- | --- | --- |
| `nty/gracious-dirac-v4pink` | session 1 | Phase 0 + phase 1 core (auth, audit, 2FA, installer) | merged (PR #1) |
| `feat/phase-1-ui` | session 2 | Phase 1 UI: frontend build, layouts, login/2FA screens, settings, users, panduan | in progress |

## Next steps

1. Merge PR #1; locally `composer install`, then follow `docs/development.md` → Dev install.
2. Phase 1 UI: `package.json` (Tailwind v4 + Flowbite), build to `public/build/`; layouts
   `admin` (full width, sidebar from module menus, breadcrumbs), `public`, `auth`.
3. Replace Shield's default views (`Config\Auth::$views`) with Flowbite login and
   verification screens in Indonesian.
4. 2FA UI: profile page to enable/disable (QR via `chillerlan/php-qrcode`), Shield login
   action class that calls `service('twoFactor')->verify()` when enabled; option for
   Super Admin to require it per role.
5. Settings UI for `School.*` (white label) and user management (assign roles).
6. Panduan skeleton (`/panduan/<peran>`).

## Blockers / waiting on

- Interview round 1 answers (tenancy, timeline, applicant access, roles, chat, analytics, school data, branding). Defaults in use: D-004, D-008, D-009, D-014.
- Confirmation that the Flowbite Pro license was bought from flowbite.com; remove `flowbite/**/@Futoruu.url`.
- Licence choice for the project (root `LICENSE` is still CodeIgniter's MIT notice).
- Dev environment network does not allow `dapo.kemendikdasmen.go.id` / `sekolah.data.kemendikdasmen.go.id` (needed in phase 2).

## Session log (newest first, keep the last 10)

- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Dev install (MySQL 8, `.env`), phase 1 core: Shield + roles, Indonesian auth routes, audit trail, TOTP service, migrations, installer + demo accounts, tests. Next: frontend build and layouts.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Phase 0: appstarter layout, CI/commit-policy/deploy workflows, commit-msg hook, CLAUDE.md, 8 skills, docs, `ai/` prompts, interview round 1. Next: merge PR #1, interview answers.
