# Progress

Shared working memory across sessions and Claude accounts. **Read first, update
last** (see `.claude/skills/spmb-session`). Keep it short: current state only —
history lives in git, `CHANGELOG.md` and `decisions.md`. No secrets, emails or
personal data.

## Current phase

**Phase 0 — Foundation**: done in [PR #1](https://github.com/arumiforge/spmb/pull/1), waiting for CI and merge.

## Active work

One row per open branch. Do not work on a branch claimed here by another session;
create your own branch from `main` instead.

| Branch | Session label | Task | Status |
| --- | --- | --- | --- |
| `nty/gracious-dirac-v4pink` | session 1 | Phase 0 foundation | PR #1 open |

## Next steps

1. Merge PR #1, then `composer install` locally (framework now comes from Composer).
2. Collect answers to interview round 1 (`docs/interview/round-01.md`) and the school committee checklist.
3. Start phase 1: frontend build (Tailwind v4 + Flowbite), layouts, settings/white label, auth + roles, optional 2FA, audit log.

## Blockers / waiting on

- Interview round 1 answers (tenancy, timeline, applicant access, roles, chat, analytics, school data, branding).
- Confirmation that the Flowbite Pro license was bought from flowbite.com; remove `flowbite/**/@Futoruu.url`.
- Licence choice for the project (root `LICENSE` is still CodeIgniter's MIT notice).
- Dev environment network does not allow `dapo.kemendikdasmen.go.id` / `sekolah.data.kemendikdasmen.go.id` (needed in phase 2).

## Session log (newest first, keep the last 10)

- 2026-11-17 · session 1 · `nty/gracious-dirac-v4pink` — Phase 0: appstarter layout, CI/commit-policy/deploy workflows, commit-msg hook, CLAUDE.md, 8 skills, docs, `ai/` prompts, interview round 1. Next: merge PR #1, interview answers.
