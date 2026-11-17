# AI Image Prompts

Illustrations for the UI are generated with **ChatGPT image generation** from the
prompts in this folder, then optimised into `public/assets/img/`.

## Workflow

1. Prompt files are added to `prompts/` (by the developer or Claude, see `.claude/skills/spmb-ai-image`)
   and listed in the queue below. Each file has a detailed brief and a ready prompt.
2. In ChatGPT (with access to this repository), paste the starter prompt from the "ChatGPT" section below.
   ChatGPT follows [CHATGPT.md](CHATGPT.md): it reads the style guide and generates the queued images one
   by one, including dark variants (`<name>-dark.png`).
3. Download each image under the file name ChatGPT gives (the `file` field of the prompt) and send the
   images to Claude in the Claude Code chat. `output/` is git-ignored; originals stay local.
4. Claude resizes/converts them to WebP at `target`, wires them into the views with the Indonesian alt
   text, and marks the row `done`.

Style rules: [style-guide.md](style-guide.md) (painted storybook look, D-027). New prompts: copy
[prompts/_template.md](prompts/_template.md).

The delivered PNG originals are versioned in [`generated/`](generated/README.md) at the user's request.
`generated` means the image is delivered and application integration is pending; `done` is reserved
for resized assets that are installed in the views. `output/` remains ignored for scratch work.

## ChatGPT starter prompt

Requires the GitHub connector in ChatGPT with access to `arumiforge/spmb`. Without it, attach
`ai/CHATGPT.md`, `ai/style-guide.md`, `ai/README.md` and the `ai/prompts/` files to the chat instead
and replace the first sentence with "Use the attached files".

```
Open the GitHub repository arumiforge/spmb (branch main) and read ai/CHATGPT.md. Follow it exactly: read ai/style-guide.md and the queue in ai/README.md, then generate the images listed as "requested" one at a time, in ID order, using each file in ai/prompts/. Use the painted storybook style from the style guide for every image: hand-painted gouache/watercolour, stylised characters, not realistic, not 3D, and no text in any image. Talk to me in Indonesian. Start with image 001 and wait for my "lanjut" after each image.
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
