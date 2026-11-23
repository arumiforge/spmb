# Visual assets (Codex briefs)

Every illustration, pattern, divider, special button and animation in SPMB is made by **Codex** from the
briefs in this folder (D-073). Claude writes the briefs, reviews what Codex delivers and installs it in
the app. Codex reads everything here directly in the repository.

| File | For |
| --- | --- |
| [CODEX.md](CODEX.md) | Codex's instructions: scope, git rules, where files go, how to check and deliver |
| [style-guide.md](style-guide.md) | The identity (santri, Islamic, NU, Indonesian, Bhinneka), respect rules, palette, cast, painting and SVG rules |
| [prompts/](prompts/) | One brief per asset; templates `_template.md` (painting) and `_template-svg.md` (SVG/CSS) |
| [generated/](generated/README.md) | Codex's deliveries: masters, `install/` (ready to install), `demo/` (preview page) |
| [tools/check_assets.py](tools/check_assets.py) | Checks delivered files against their briefs (`python3 ai/tools/check_assets.py [IDs]`) |
| `output/` | Git-ignored scratch folder |

## Workflow

1. **Brief** — Claude adds `prompts/<NNN>-<name>.md` from a template (skill `spmb-ai-image`) and a row
   below with status `requested`.
2. **Codex makes it** — the user starts Codex with the starter prompt below. Codex follows
   [CODEX.md](CODEX.md), makes one batch, runs the checker and opens **one pull request** from
   `art/codex-<date>-<batch>` into `main` that only touches `ai/generated/` and the status lines. It never merges.
3. **Claude installs it** — Claude reviews every file, asks for fixes in the pull request, copies the
   approved files from `ai/generated/install/` into `public/assets/img/` and `resources/css/visual/`,
   wires them into the views with the Indonesian alt text, and sets the status to `done`.

Statuses: `requested` → `generated` (delivered by Codex) → `done` (installed in the app).

## Codex starter prompt

```
Work in the GitHub repository arumiforge/spmb, starting from the latest main. Read ai/CODEX.md and follow it exactly; it points you to ai/style-guide.md and the briefs in ai/prompts/. Your only job is to make the visual assets of ONE batch from the queue in ai/README.md (rows with status "requested"; take the first batch in the table that is not done yet), check them with python3 ai/tools/check_assets.py, and deliver them only in ai/generated/ (plus the status lines) on a new branch art/codex-<YYYYMMDD>-<batch>, with one pull request into main. Commit as Arumi Studios <arumiforge@gmail.com>. Do not change anything else, do not merge, and add no AI attribution anywhere. Respect the rules for religious elements in section 2 of the style guide above everything else.
```

## Queue

Batches are delivered in this order: `svg-kit` (no image generator needed) → `paintings-pages` →
`paintings-states` → `variants-001-012`.

| ID | Brief | Batch | Kind | Used on | Status |
| --- | --- | --- | --- | --- | --- |
| 001 | [hero-beranda](prompts/001-hero-beranda.md) | — | painting | Public home hero | done |
| 002 | [daftar-online](prompts/002-daftar-online.md) | — | painting | Online registration page | done |
| 003 | [masuk-dasbor](prompts/003-masuk-dasbor.md) | — | painting | Staff sign-in | done |
| 004 | [kosong-data](prompts/004-kosong-data.md) | — | painting | Empty lists | done |
| 005 | [kosong-pencarian](prompts/005-kosong-pencarian.md) | — | painting | No search results | done |
| 006 | [sukses-pendaftaran](prompts/006-sukses-pendaftaran.md) | — | painting | Registration success | done |
| 007 | [tidak-ditemukan](prompts/007-tidak-ditemukan.md) | — | painting | 404 page | done |
| 008 | [gangguan-sistem](prompts/008-gangguan-sistem.md) | — | painting | 500 page | done |
| 009 | [kosong-riwayat](prompts/009-kosong-riwayat.md) | — | painting | Riwayat Perubahan empty state | done |
| 010 | [fitur-tambahan](prompts/010-fitur-tambahan.md) | — | painting | Fitur page empty state | done |
| 011 | [panduan-petugas](prompts/011-panduan-petugas.md) | — | painting | Staff guide cover | done |
| 012 | [panduan-calon-murid](prompts/012-panduan-calon-murid.md) | — | painting | Applicant guide cover | done |
| 015 | [pola](prompts/015-pola.md) | svg-kit | SVG + CSS | Tileable patterns: kawung, khatam, anyaman, ceplok daun | done |
| 016 | [pemisah](prompts/016-pemisah.md) | svg-kit | SVG + CSS | Full-width section dividers: lengkung, tumpal, mega, bukit | done |
| 017 | [tombol-daftar](prompts/017-tombol-daftar.md) | svg-kit | SVG + CSS | Special "Daftar sekarang" button, all states | done |
| 018 | [memuat](prompts/018-memuat.md) | svg-kit | SVG + CSS | Loading animation, large and in-button | done |
| 019 | [hero-beranda-santri](prompts/019-hero-beranda-santri.md) | paintings-pages | painting | Home hero, identity version | generated |
| 020 | [beranda-kebersamaan](prompts/020-beranda-kebersamaan.md) | paintings-pages | painting | Home: learning together (Bhinneka) | generated |
| 021 | [beranda-langkah](prompts/021-beranda-langkah.md) | paintings-pages | painting | Home: three steps to register | generated |
| 022 | [persyaratan](prompts/022-persyaratan.md) | paintings-pages | painting | `/persyaratan` | generated |
| 023 | [jadwal](prompts/023-jadwal.md) | paintings-pages | painting | `/jadwal` | generated |
| 024 | [tanya-jawab](prompts/024-tanya-jawab.md) | paintings-pages | painting | `/tanya-jawab` | requested |
| 025 | [cek-status](prompts/025-cek-status.md) | paintings-pages | painting | `/cek-status` | requested |
| 026 | [kartu](prompts/026-kartu.md) | paintings-pages | painting | `/cek-status/kartu`, card settings | requested |
| 027 | [tes](prompts/027-tes.md) | paintings-pages | painting | Test schedule and test pages | requested |
| 035 | [bagikan-umum](prompts/035-bagikan-umum.md) | paintings-pages | painting | Default link-preview image | requested |
| 028 | [akses-ditolak](prompts/028-akses-ditolak.md) | paintings-states | painting | 403 / no permission | requested |
| 029 | [sesi-berakhir](prompts/029-sesi-berakhir.md) | paintings-states | painting | Session expired | requested |
| 030 | [pendaftaran-ditutup](prompts/030-pendaftaran-ditutup.md) | paintings-states | painting | Registration not open | requested |
| 031 | [pemeliharaan](prompts/031-pemeliharaan.md) | paintings-states | painting | Maintenance (503) | requested |
| 032 | [kosong-jadwal](prompts/032-kosong-jadwal.md) | paintings-states | painting | No test schedule yet | requested |
| 033 | [kosong-berkas](prompts/033-kosong-berkas.md) | paintings-states | painting | No documents uploaded | requested |
| 034 | [tanpa-koneksi](prompts/034-tanpa-koneksi.md) | paintings-states | painting | Offline / connection lost | requested |
| 013 | [aksen-hijau](prompts/013-aksen-hijau.md) | variants-001-012 | image edit | Green-accent copies of 001–012 | requested |
| 014 | [varian-gelap](prompts/014-varian-gelap.md) | variants-001-012 | image edit | Dark copies of 001, 002, 006, 011, 012 | requested |

Notes

- 001–012 were painted by ChatGPT before D-073 (PNG originals in `generated/`); new work follows `CODEX.md`.
- Every new painting (019–034) comes with dark, green-accent and dark green-accent variants; 035 with a
  green-accent variant only.
- 017 uses the kawung tile from 015 and the small loader from 018, so the `svg-kit` batch ships together.
