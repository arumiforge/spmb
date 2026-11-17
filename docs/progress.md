# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 0 — Foundation**: done. **Phase 1 — Platform core**: core merged in
[PR #1](https://github.com/arumiforge/spmb/pull/1); **phase 1 UI** in review in
[PR #2](https://github.com/arumiforge/spmb/pull/2) (`feat/phase-1-ui`).

Done in phase 1: Shield auth with 7 roles, audit trail, TOTP 2FA (service, sign-in step, setup UI,
per-role enforcement), installer, frontend build (Tailwind v4 + Flowbite), admin/auth/public layouts,
school identity + branding settings, user management, module registry + permission-based sidebar,
Panduan skeleton. 71 tests green on MySQL 8.0.

## Active work

One row per open branch. Do not work on a branch claimed here by another session;
create your own branch from `main` instead.

| Branch | Session label | Task | Status |
| --- | --- | --- | --- |
| `feat/phase-1-ui` | session 2 | Phase 1 UI: frontend build, layouts, login/2FA screens, settings, users, panduan | PR #2 open |

## Next steps

1. Review and merge PR #2. After pulling: `composer install`, `npm ci && npm run build`,
   `php spark spmb:install` (applies nothing new; no migrations were added).
2. Finish phase 1: Super Admin page to switch modules on/off (`sistem.modul`, `setting('Features.disabled')`),
   audit log viewer (`audit.view`), friendly Indonesian 404/500 pages in the public layout.
3. Illustrations: write prompts in `ai/` (spmb-ai-image) for the empty states, sign-in and guide pages.
4. Phase 2 (registration): waves, online/offline form, numbering, school reference data, uploads,
   PDF card + QR — start with `docs/interview/` answers on waves and documents.

## Blockers / waiting on

- Interview round 1 answers (tenancy, timeline, applicant access, roles, chat, analytics, school data, branding). Defaults in use: D-004, D-008, D-009, D-014.
- Admin Sekolah cannot create other Admin Sekolah accounts (`pengguna.kelola-admin` is Super Admin only, per the permission description). Confirm with the school whether that is wanted.
- Licence choice for the project (root `LICENSE` is still CodeIgniter's MIT notice).
- Dev environment network does not allow `dapo.kemendikdasmen.go.id` / `sekolah.data.kemendikdasmen.go.id` (needed in phase 2).

## Session log (newest first, keep the last 10)

- 2026-11-17 · session 2 · `feat/phase-1-ui` — Phase 1 UI: frontend build, layouts, Indonesian sign-in, 2FA step + setup, settings (identity, security), user management, module registry/menu, Panduan; docs D-017…D-022; PR #2. Next: module on/off UI, audit viewer.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Dev install (MySQL 8, `.env`), phase 1 core: Shield + roles, Indonesian auth routes, audit trail, TOTP service, migrations, installer + demo accounts, tests. Next: frontend build and layouts.
- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Phase 0: appstarter layout, CI/commit-policy/deploy workflows, commit-msg hook, CLAUDE.md, 8 skills, docs, `ai/` prompts, interview round 1. Next: merge PR #1, interview answers.
