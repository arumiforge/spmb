---
name: spmb-session
description: Start and end every SPMB work session the same way so work continues safely across sessions and different Claude accounts — sync, identity check, read docs/progress.md, claim a branch, and hand off by updating progress.md before finishing. Use at the start of every session and before ending one (or when context is getting long).
---

# Session start and handoff

Claude accounts share nothing: no chat history, no memory, no environment
settings. **The repository is the only shared memory.** Every session must
leave it in a state the next session (possibly another account) can pick up.

## Start of session

1. **Sync**
   ```bash
   git fetch origin
   git status
   git log --oneline -5 origin/main
   ```
2. **Identity and hooks** — new environments often default to an AI identity:
   ```bash
   git config user.name "Arumi Studios"
   git config user.email "arumiforge@gmail.com"
   git config core.hooksPath .githooks
   ```
   Run `composer install` if `vendor/` is missing. In cloud sessions MySQL does not
   survive an idle container: if `mysqladmin ping` fails, run `service mysql start`.
3. **Read context, in order**: `CLAUDE.md` → `docs/progress.md` → latest
   `docs/interview/round-NN.md` → last entries of `docs/decisions.md`.
4. **Claim work**: check *Active work* in `progress.md` and open PRs. Never
   commit to a branch another session has claimed. Use the branch this session
   was given, or create one from `origin/main`; add/update your row
   (branch, session label, task, status) and commit it early.
5. Tell the user in one or two lines where things stand and what you will do.

## During the session

- Commit small and push often (`spmb-commit`), so work is never only in a
  container that may be reclaimed.
- Record decisions in `decisions.md` as they happen, not at the end.

## End of session / handoff

Do this before finishing, and also proactively when the conversation is long.

1. Update `docs/progress.md`:
   - *Current phase* and *Active work* row (status: in progress / PR open / merged).
   - *Next steps*: concrete, ordered, startable by someone with no chat context.
   - *Blockers / waiting on*: questions for the user or school, environment limits.
   - *Session log*: one line — date · session label · branch — what was done; next.
     Keep the last 10 lines; delete older ones (git keeps history).
2. Update docs and `CHANGELOG.md` per `spmb-docs`.
3. Commit (`docs(progress): ...` or together with the work), push, open/update the PR.

## Environment differences between accounts

Per-account settings are not in the repo; mention them in *Blockers* when they block work:
- GitHub must be connected with access to `arumiforge/spmb`.
- Network allowlist (e.g. `dapo.kemendikdasmen.go.id`, `sekolah.data.kemendikdasmen.go.id` for the school importer).
- Setup script / installed tools.

Never write account names, emails, tokens or personal data into `progress.md`.
Use neutral session labels (e.g. "session 3", "akun 2").
