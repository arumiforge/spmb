# Roadmap

Phases deliver in the school's priority order (brief §9). Each phase ends with
docs, guides, changelog and a demo on staging.

| Phase | Scope | Status |
| --- | --- | --- |
| 0 — Foundation | Repo layout, git rules, CI/CD workflows, agent skills, docs skeleton, `ai/` prompts, interview round 1 | done (PR #1) |
| 1 — Platform core | Frontend build (Tailwind v4 + Flowbite), layouts (admin full width, public, auth), breadcrumbs, settings + white label, module registry, auth + roles/permissions, optional 2FA, audit log, Panduan skeleton | **finishing** (`feat/phase-1-finish`) — done: auth + roles, audit log + viewer, 2FA service and screens, installer, frontend build, layouts, settings + white label, user management, module registry/menu + on/off page, Panduan skeleton, friendly 404/500; remaining: illustrations (prompts in `ai/`) |
| 2 — Registration | Waves, online + offline form, numbering, duplicate check, school reference data, document upload/resize/verification, PDF card + QR, status check, basic public pages | **done** (PRs #9–#17, merged via #14); next: school list import, go-live for review |
| 3 — Placement test | Test calendar, sessions/rooms/quotas, automatic allocation, QR/search attendance, rescheduling log | **done** on `phase-3` (PRs #18–#22), awaiting merge into `main`; see `phase-3-plan.md` |
| 4 — Re-registration & reports | Re-registration, uniform status, dashboard stats, filters, XLSX/CSV/PDF export, XLSX import | planned |
| 5 — Engagement | Public site content and announcements, compact visitor analytics (D-053), messages/criticism/suggestions from parents with committee replies — not live chat (D-052) | planned |
| 6 — Launch | Server setup, backups + restore drill, security review, load test, role guides complete, staff training | planned |
| Later | WhatsApp notifications, uniform stock/packages, digital payment, trend analysis, integrations | backlog |

Target dates depend on when wave 1 opens (interview round 1, Q2).
