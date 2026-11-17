---
name: spmb-interview
description: Run the staged requirements interview for SPMB — ask the next batch of questions, ingest documents from the school, record answers as requirements and decisions. Use when the user sends school documents, answers questions, or asks what is still undecided.
---

# Staged interview

The user (Arumi Studios) relays the school's needs over several rounds. Each
round is a file in `docs/interview/round-NN.md`.

## Each round

1. Read `docs/decisions.md`, `docs/requirements.md` and the latest round file.
2. If the user sent a document from the school, summarise it in English in
   `docs/project-brief.md` (append a dated "Source" section) and diff it against
   current requirements.
3. Ask **at most 8 questions**, highest impact first (things that change the data
   model or the build order before cosmetic ones). Each question: context in one
   line, options with a recommended default, and what it affects.
4. Ask in chat in English; the user may answer in Indonesian.
5. Record answers: requirement changes → `requirements.md`; choices →
   new entries in `decisions.md`; unanswered → carried to the next round.
6. Never block building on unanswered low-impact questions: use the recommended
   default, mark it `proposed` in `decisions.md`, and say so.
