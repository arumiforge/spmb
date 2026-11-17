# AI Image Prompts

Illustrations for the UI are generated with **ChatGPT image generation** from the
prompts in this folder, then optimised into `public/assets/img/`.

## Workflow

1. A prompt file is added to `prompts/` (by the developer or the agent — see
   `.claude/skills/spmb-ai-image`) and listed in the queue below.
2. Open the prompt file, paste the **Prompt** section into ChatGPT, request the listed size.
3. Save the result as `output/<file>` (name from the prompt's front matter).
   `output/` is git-ignored; originals stay local.
4. The agent resizes/converts it to WebP at `target`, wires it into the view with the
   Indonesian alt text, and marks the row `done`.

Style rules: [style-guide.md](style-guide.md). New prompts: copy [prompts/_template.md](prompts/_template.md).

## Queue

| ID | File | Used on | Size | Status |
| --- | --- | --- | --- | --- |
| 001 | hero-beranda.png | Public home hero | 1792×1024 | requested |
| 002 | daftar-online.png | Online registration page | 1024×1365 | requested |
| 003 | masuk-dasbor.png | Staff login | 1024×1365 | requested |
| 004 | kosong-data.png | Empty lists | 1024×1024 | requested |
| 005 | kosong-pencarian.png | No search results | 1024×1024 | requested |
| 006 | sukses-pendaftaran.png | Registration success | 1024×1024 | requested |
| 007 | tidak-ditemukan.png | 404 page | 1024×1024 | requested |
| 008 | gangguan-sistem.png | 500 page | 1024×1024 | requested |
| 009 | kosong-riwayat.png | Riwayat Perubahan empty state | 1024×1024 | requested |
| 010 | fitur-tambahan.png | Fitur page empty state | 1024×1024 | requested |
| 011 | panduan-petugas.png | Staff guide cover | 1792×1024 | requested |
| 012 | panduan-calon-murid.png | Applicant guide cover | 1792×1024 | requested |

Guide covers: one shared staff cover (011) and one applicant cover (012); per-role covers can follow once roles are confirmed.
