# AI Image Prompts

Illustrations for the UI are generated with **ChatGPT image generation** from the
prompts in this folder, then optimised into `public/assets/img/`.

## Workflow

Roles are split (D-028):

1. **Briefs** — the developer or Claude adds a brief to `prompts/` (see `.claude/skills/spmb-ai-image`)
   and lists it in the queue below with status `requested`.
2. **ChatGPT makes the images** — paste the starter prompt below into ChatGPT. It follows
   [CHATGPT.md](CHATGPT.md): reads the style guide and briefs, generates the queued images one by one
   (with dark variants `<name>-dark.png`), and opens **one pull request** from `art/illustrations-<date>`
   that adds them to [`generated/`](generated/README.md) and sets their status to `generated`. It never merges.
3. **Claude reviews and installs them** — Claude checks the images (style, no text, empty areas,
   transparency), asks for regenerations if needed, converts the approved ones to WebP at each brief's
   `target`, wires them into the views with the Indonesian alt text and sets the status to `done`.
   The PNG originals stay in `generated/` as the source for later re-exports.

Statuses: `requested` → `generated` (original delivered) → `done` (installed in the app).
Style rules: [style-guide.md](style-guide.md) (painted storybook look, D-027). New briefs: copy
[prompts/_template.md](prompts/_template.md). `output/` is a git-ignored scratch folder for local work.

## ChatGPT starter prompt

Needs ChatGPT with GitHub access to `arumiforge/spmb` that can create a branch and a pull request
(GitHub connector with write access, or ChatGPT agent/Codex). The commits must be made as the
`arumiforge` account, or the commit-policy check fails. Without write access ChatGPT still makes the
images and tells you how to upload them.

```
Open the GitHub repository arumiforge/spmb (branch main) and read ai/CHATGPT.md. Follow it exactly. Your only job is to make the illustrations and deliver them: generate the images listed as "requested" in ai/README.md one at a time, in ID order, from the briefs in ai/prompts/, in the painted storybook style of ai/style-guide.md (hand-painted gouache/watercolour, stylised characters, not realistic, not 3D, no text in any image). Then put them only in the folder ai/generated/ (and set their status to generated) on a new branch art/illustrations-<today's date> and open one pull request into main. Do not change anything else, do not merge, and add no AI attribution. Talk to me in Indonesian. Start with image 001 and wait for my "lanjut" after each image.
```

## Queue

| ID | File | Used on | Requested size | Status |
| --- | --- | --- | --- | --- |
| 001 | [hero-beranda.png](generated/hero-beranda.png) | Public home hero | 1536×1024 | done |
| 002 | [daftar-online.png](generated/daftar-online.png) | Online registration page | 1024×1536 | done |
| 003 | [masuk-dasbor.png](generated/masuk-dasbor.png) ([dark](generated/masuk-dasbor-dark.png)) | Staff login | 1024×1536 | done |
| 004 | [kosong-data.png](generated/kosong-data.png) ([dark](generated/kosong-data-dark.png)) | Empty lists | 1024×1024 | done |
| 005 | [kosong-pencarian.png](generated/kosong-pencarian.png) ([dark](generated/kosong-pencarian-dark.png)) | No search results | 1024×1024 | done |
| 006 | [sukses-pendaftaran.png](generated/sukses-pendaftaran.png) | Registration success | 1024×1024 | done |
| 007 | [tidak-ditemukan.png](generated/tidak-ditemukan.png) ([dark](generated/tidak-ditemukan-dark.png)) | 404 page | 1024×1024 | done |
| 008 | [gangguan-sistem.png](generated/gangguan-sistem.png) ([dark](generated/gangguan-sistem-dark.png)) | 500 page | 1024×1024 | done |
| 009 | [kosong-riwayat.png](generated/kosong-riwayat.png) ([dark](generated/kosong-riwayat-dark.png)) | Riwayat Perubahan empty state | 1024×1024 | done |
| 010 | [fitur-tambahan.png](generated/fitur-tambahan.png) ([dark](generated/fitur-tambahan-dark.png)) | Fitur page empty state | 1024×1024 | done |
| 011 | [panduan-petugas.png](generated/panduan-petugas.png) | Staff guide cover | 1536×1024 | done |
| 012 | [panduan-calon-murid.png](generated/panduan-calon-murid.png) | Applicant guide cover | 1536×1024 | done |
| 013 | [aksen-hijau](prompts/013-aksen-hijau.md) — `<name>-hijau.png` for 001–012 | Green-accent set (setting "Warna aksen ilustrasi") | same as originals | requested |
| 014 | [varian-gelap](prompts/014-varian-gelap.md) — `<name>-dark.png` for 001, 002, 006, 011, 012 | Dark mode of hero, form, success, guide covers | same as originals | requested |

002 and 006 are used on `/daftar` and `/daftar/selesai` (phase 2).

013 and 014 are edits of the delivered originals (image editing), not new paintings: upload the original to
ChatGPT with the brief's prompt.

Guide covers: one shared staff cover (011) and one applicant cover (012); per-role covers can follow once roles are confirmed.
