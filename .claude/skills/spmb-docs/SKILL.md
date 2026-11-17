---
name: spmb-docs
description: Maintain SPMB's staged English documentation in docs/ — requirements, architecture, decisions, roadmap, changelog, deployment. Use after any change to behaviour, schema, config, roles or infrastructure, and at the end of each phase.
---

# Staged documentation

Documentation grows phase by phase with the code. Write in English, concise,
present tense, describing what exists — not plans (plans go in `roadmap.md`).

## Map

| File | Update when |
| --- | --- |
| `docs/README.md` | A doc is added/renamed |
| `docs/project-brief.md` | Client brief changes (source documents from the school) |
| `docs/requirements.md` | A requirement is added, changed, or confirmed in the interview |
| `docs/architecture.md` | Modules, data model, security model, integrations change |
| `docs/decisions.md` | Any decision is made — append an entry, never rewrite history |
| `docs/roadmap.md` | Phase scope/status changes |
| `docs/modules/<module>.md` | A module is created or its behaviour changes (routes, permissions, tables, statuses) |
| `docs/deployment.md` | Server, Nginx, Cloudflare Tunnel, CI/CD, backup steps change |
| `docs/interview/round-NN.md` | Interview questions asked / answered |
| `CHANGELOG.md` | Every merged change, under `## [Unreleased]` (Keep a Changelog) |

## Decision entry format

```
## D-012 — Short title (YYYY-MM-DD)
Status: accepted | proposed | superseded by D-0xx
Context: why the decision was needed (1–3 lines)
Decision: what was chosen
Consequences: trade-offs, follow-ups
```

## Rules

- Each phase ends with: roadmap status updated, changelog section, module docs
  complete, open questions moved to the next interview round.
- Indonesian user guides are **not** here — they live in module `Guide/` folders (`spmb-copy`).
- Never paste secrets, real applicant data, server IPs, or tokens into docs.
