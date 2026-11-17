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

## ChatGPT starter prompt

Requires the GitHub connector in ChatGPT with access to `arumiforge/spmb`. Without it, attach
`ai/CHATGPT.md`, `ai/style-guide.md`, `ai/README.md` and the `ai/prompts/` files to the chat instead
and replace the first sentence with "Use the attached files".

```
Open the GitHub repository arumiforge/spmb (branch main) and read ai/CHATGPT.md. Follow it exactly: read ai/style-guide.md and the queue in ai/README.md, then generate the images listed as "requested" one at a time, in ID order, using each file in ai/prompts/. Use the painted storybook style from the style guide for every image: hand-painted gouache/watercolour, stylised characters, not realistic, not 3D, and no text in any image. Talk to me in Indonesian. Start with image 001 and wait for my "lanjut" after each image.
```

## Queue

| ID | File | Used on | Size | Status |
| --- | --- | --- | --- | --- |
| 001 | hero-beranda.png | Public home hero | 1536×1024 | requested |
| 002 | daftar-online.png | Online registration page | 1024×1536 | requested |
| 003 | masuk-dasbor.png (+ dark) | Staff login | 1024×1536 | requested |
| 004 | kosong-data.png (+ dark) | Empty lists | 1024×1024 | requested |
| 005 | kosong-pencarian.png (+ dark) | No search results | 1024×1024 | requested |
| 006 | sukses-pendaftaran.png | Registration success | 1024×1024 | requested |
| 007 | tidak-ditemukan.png (+ dark) | 404 page | 1024×1024 | requested |
| 008 | gangguan-sistem.png (+ dark) | 500 page | 1024×1024 | requested |
| 009 | kosong-riwayat.png (+ dark) | Riwayat Perubahan empty state | 1024×1024 | requested |
| 010 | fitur-tambahan.png (+ dark) | Fitur page empty state | 1024×1024 | requested |
| 011 | panduan-petugas.png | Staff guide cover | 1536×1024 | requested |
| 012 | panduan-calon-murid.png | Applicant guide cover | 1536×1024 | requested |

Guide covers: one shared staff cover (011) and one applicant cover (012); per-role covers can follow once roles are confirmed.
