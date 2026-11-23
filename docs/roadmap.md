# Roadmap

Phases deliver in the school's priority order (brief §9). Each phase ends with
docs, guides, changelog and a demo on staging.

| Phase | Scope | Status |
| --- | --- | --- |
| 0 — Foundation | Repo layout, git rules, CI/CD workflows, agent skills, docs skeleton, `ai/` prompts, interview round 1 | done (PR #1) |
| 1 — Platform core | Frontend build (Tailwind v4 + Flowbite), layouts (admin full width, public, auth), breadcrumbs, settings + white label, module registry, auth + roles/permissions, optional 2FA, audit log, Panduan skeleton | **done** (PRs #1, #2, #4): auth + roles, audit log + viewer, 2FA, installer, frontend build, layouts, settings + white label, user management, module registry/menu + on/off page, Panduan, friendly 404/500, illustrations 001–012 |
| 2 — Registration | Waves, online + offline form, numbering, duplicate check, school reference data, document upload/resize/verification, PDF card + QR, status check, basic public pages | **done** (PRs #9–#17, merged via #14); national SD/MI list ships as default data (D-058) |
| 3 — Placement test | Test calendar, sessions/rooms/quotas, automatic allocation, QR/search attendance, rescheduling log | **done** (PRs #18–#22, merged via #23); see `phase-3-plan.md` |
| Platform round | Docker deployment (staging + production on one VPS, `setup.sh`, `spmb` server CLI, backups, rollback), PHP 8.5.13 / Node 24 / MySQL 8.4, Lucide icons and visual rules, public Tanya Jawab page, Codex briefs and identity, cloud environment setup | **done** (PRs #26–#32, D-066…D-077); see `platform-plan.md` |
| 4 — Re-registration & reports | Re-registration, uniform status, dashboard stats, filters, XLSX/CSV/PDF export, XLSX import | planned |
| 5 — Engagement | Public site content and announcements, compact visitor analytics (D-053), messages/criticism/suggestions from parents with committee replies — not live chat (D-052) | planned |
| 6 — Launch | Server setup, backups + restore drill, security review, load test, role guides complete, staff training | partly done: server setup script, daily backups and restore command (platform round); go-live, restore drill, security review, load test and training remain |
| Later | WhatsApp notifications, uniform stock/packages, digital payment, trend analysis, integrations | backlog |

Target dates depend on when wave 1 opens (interview round 1, Q2). Go-live for the school's review comes before
phase 4 (D-056): `docs/deployment.md` → "First server".
