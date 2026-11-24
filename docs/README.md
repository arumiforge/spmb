# SPMB Documentation

White-label school admission system (Sistem Penerimaan Murid Baru) by Arumi Studios.
First client: **SMP NU Al Ma'ruf Kudus**, academic year 2027/2028.

Documentation is written in English and grows phase by phase (see `roadmap.md`).
User-facing guides for each role are in Indonesian and live inside the app: `/panduan` for applicants and parents, `/admin/panduan` for staff (D-097).

| Document | Purpose |
| --- | --- |
| [progress.md](progress.md) | **Read first.** Current state, active branches, next steps, blockers |
| [project-brief.md](project-brief.md) | The school's brief, summarised in English |
| [requirements.md](requirements.md) | Functional and non-functional requirements, roles |
| [architecture.md](architecture.md) | Stack, modules, data model outline, security |
| [decisions.md](decisions.md) | Decision log (accepted and proposed) |
| [roadmap.md](roadmap.md) | Delivery phases and status |
| [deployment.md](deployment.md) | Ubuntu 24.04 server with Docker (staging + production), Cloudflare Tunnel, Cockpit, CI |
| [development.md](development.md) | Local setup, conventions, git rules |
| [cloud-environment.md](cloud-environment.md) | Claude Code cloud environment for any Claude account (setup script in `.claude/cloud/`) |
| [coordination.md](coordination.md) | How parallel sessions work (coordinator, workers, integration branch) |
| [platform-plan.md](platform-plan.md) | Platform round: Docker, PHP 8.5/Node 24, visual rules, Tanya Jawab, Codex briefs |
| [interview/](interview/) | Staged requirements interview, one file per round |
| [modules/](modules/) | One file per feature module (added as modules are built) |

Related: [`../CLAUDE.md`](../CLAUDE.md) (agent rules), [`../ai/`](../ai/) (Codex briefs for illustrations and visuals),
[`../CHANGELOG.md`](../CHANGELOG.md).
