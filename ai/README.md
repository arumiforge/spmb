# AI Image Prompts

Illustrations for the UI are generated with **ChatGPT image generation** from the
prompts in this folder, then optimised into `public/assets/img/`.

## Workflow

Roles are split (D-028):

1. **Briefs** — the developer or Claude adds a brief to `prompts/` (see `.claude/skills/spmb-ai-image`)
   and lists it in the queue below with status `requested`.
2. **ChatGPT makes the images** — paste the starter prompt below into ChatGPT. It follows
   [CHATGPT.md](CHATGPT.md): reads the style guide and briefs, generates the queued images one by one
   (with dark variants `<name>-dark.png`), and opens **one pull request** that only adds files to
   [`incoming/`](incoming/) on a branch `art/illustrations-<date>`. It never merges.
3. **Claude installs them** — Claude reviews the images in that pull request (style, no text, empty
   areas, transparency), asks for regenerations if needed, converts the approved ones to WebP at each
   brief's `target`, wires them into the views with the Indonesian alt text, marks the queue rows `done`,
   removes the originals from `incoming/` and squash-merges once CI is green and the user agrees.

Style rules: [style-guide.md](style-guide.md) (painted storybook look, D-027). New briefs: copy
[prompts/_template.md](prompts/_template.md). `output/` is a git-ignored scratch folder for local work.

The delivered PNG originals are versioned in [`generated/`](generated/README.md) at the user's request.
`generated` means the image is delivered and application integration is pending; `done` is reserved
for resized assets that are installed in the views. `output/` remains ignored for scratch work.

## ChatGPT starter prompt

Needs ChatGPT with GitHub access to `arumiforge/spmb` that can create a branch and a pull request
(GitHub connector with write access, or ChatGPT agent/Codex). The commits must be made as the
`arumiforge` account, or the commit-policy check fails. Without write access ChatGPT still makes the
images and tells you how to upload them.

```
Open the GitHub repository arumiforge/spmb (branch main) and read ai/CHATGPT.md. Follow it exactly. Your only job is to make the illustrations and deliver them: generate the images listed as "requested" in ai/README.md one at a time, in ID order, from the briefs in ai/prompts/, in the painted storybook style of ai/style-guide.md (hand-painted gouache/watercolour, stylised characters, not realistic, not 3D, no text in any image). Then put them only in the folder ai/incoming/ on a new branch art/illustrations-<today's date> and open one pull request into main. Do not change any other file, do not merge, and add no AI attribution. Talk to me in Indonesian. Start with image 001 and wait for my "lanjut" after each image.
```

## Queue

| ID | File | Used on | Requested size | Status |
| --- | --- | --- | --- | --- |
| 001 | [hero-beranda.png](generated/hero-beranda.png) | Public home hero | 1536×1024 | generated |
| 002 | [daftar-online.png](generated/daftar-online.png) | Online registration page | 1024×1536 | generated |
| 003 | [masuk-dasbor.png](generated/masuk-dasbor.png) ([dark](generated/masuk-dasbor-dark.png)) | Staff login | 1024×1536 | generated |
| 004 | [kosong-data.png](generated/kosong-data.png) ([dark](generated/kosong-data-dark.png)) | Empty lists | 1024×1024 | generated |
| 005 | [kosong-pencarian.png](generated/kosong-pencarian.png) ([dark](generated/kosong-pencarian-dark.png)) | No search results | 1024×1024 | generated |
| 006 | [sukses-pendaftaran.png](generated/sukses-pendaftaran.png) | Registration success | 1024×1024 | generated |
| 007 | [tidak-ditemukan.png](generated/tidak-ditemukan.png) ([dark](generated/tidak-ditemukan-dark.png)) | 404 page | 1024×1024 | generated |
| 008 | [gangguan-sistem.png](generated/gangguan-sistem.png) ([dark](generated/gangguan-sistem-dark.png)) | 500 page | 1024×1024 | generated |
| 009 | [kosong-riwayat.png](generated/kosong-riwayat.png) ([dark](generated/kosong-riwayat-dark.png)) | Riwayat Perubahan empty state | 1024×1024 | generated |
| 010 | [fitur-tambahan.png](generated/fitur-tambahan.png) ([dark](generated/fitur-tambahan-dark.png)) | Fitur page empty state | 1024×1024 | generated |
| 011 | [panduan-petugas.png](generated/panduan-petugas.png) | Staff guide cover | 1536×1024 | generated |
| 012 | [panduan-calon-murid.png](generated/panduan-calon-murid.png) | Applicant guide cover | 1536×1024 | generated |

Guide covers: one shared staff cover (011) and one applicant cover (012); per-role covers can follow once roles are confirmed.
