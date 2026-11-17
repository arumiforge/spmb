# Interview — Round 1 (2026-11-17)

Focus: decisions that shape the data model and the build order.
Each question lists the recommended default; unanswered items proceed with the default.

## Questions for Arumi Studios

| # | Question | Recommended default | Affects | Answer |
| --- | --- | --- | --- | --- |
| Q1 | Will one installation serve one school, or many schools from one installation? | One installation per school (D-004) | Data model, roles, hosting | — |
| Q2 | When does wave 1 registration open, and when must the system be live? | Live 2 weeks before wave 1 | Roadmap dates, scope per phase | — |
| Q3 | Do applicants need an account with a password? | No account: registration number + date of birth (D-008) | Public flow, support load | — |
| Q4 | Confirm roles: add *Kepala Sekolah* (read-only) and *Petugas Seragam*? Any other role (e.g. *Bendahara*)? | Yes to both, no others yet | Permissions, guides | — |
| Q5 | "Plugin visitor track" — is there a specific plugin you meant? | Built-in light analytics (D-009) | Analitik module | — |
| Q6 | Chat: who talks to whom? (a) public visitors ↔ committee help desk, (b) applicants ↔ committee about their data, (c) internal staff chat. Real-time needed? | (a)+(b) as one help-desk inbox, near-real-time (poll/SSE), WebSocket later | Chat module, server setup | — |
| Q7 | Previous schools: SD only, or SD + MI? Only Central Java, or also outside? | SD + MI, Central Java first, others via "sekolah lain" | Referensi module, importer | — |
| Q8 | Branding and domain: school domain, logo files, primary colours, who owns the Cloudflare account? | Use the school's domain on the school's Cloudflare account; Arumi Studios gets admin access | Deploy, white label | — |

## For the school committee (from brief §10)

To be answered by the school; send this list back to them.

- [ ] Exact dates of waves 1, 2, 3; are tests held per wave or after all waves?
- [ ] Required documents (KK, akta, rapor, foto, ...); which may follow later and the deadline.
- [ ] Daily test quota, test dates, sessions per day, rooms and room capacity.
- [ ] Card printable immediately after registration, or only after initial verification?
- [ ] Re-registration period and what makes it "complete" (documents, payment, ...).
- [ ] Uniform: is status enough for launch, or are sizes/packages/payments needed?
- [ ] Domain, hosting/server owner, technology PIC at school, backup responsibility.
- [ ] Number format confirmation: `PMB-2027-0001` (resets each academic year?).
- [ ] Data fields required on the form (NISN, NIK, KK number, parent occupation/income, ...).

## Outcomes

_To be filled after answers arrive._
