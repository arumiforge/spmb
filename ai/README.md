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
   approved files from `ai/generated/install/` into `public/assets/img/` (WebP), `resources/img/` (SVG originals;
   the build minifies them into `public/assets/img/`, D-086) and `resources/css/visual/`,
   wires them into the views with the Indonesian alt text, and sets the status to `done`.

Statuses: `requested` → `generated` (delivered by Codex) → `done` (installed in the app).

## Codex starter prompt

```
Work in the GitHub repository arumiforge/spmb, starting from the latest main. Read ai/CODEX.md and follow it exactly; it points you to ai/style-guide.md and the briefs in ai/prompts/. Your only job is to make the visual assets of ONE batch from the queue in ai/README.md (rows with status "requested"; take the first batch, in the delivery order written under "Queue", that is not done yet), check them with python3 ai/tools/check_assets.py, and deliver them only in ai/generated/ (plus the status lines) on a new branch art/codex-<YYYYMMDD>-<batch>, with one pull request into main. Commit as Arumi Studios <arumiforge@gmail.com>. Do not change anything else, do not merge, and add no AI attribution anywhere. Respect the rules for religious elements in section 2 of the style guide above everything else.
```

## Queue

Batches are delivered in this order (home page first, user's request of 2026-11-23): `svg-kit` (no image
generator needed) → `tombol-seni` (no image generator needed) → `ikon-beranda` → `langkah` → `slide-beranda` →
`paintings-pages` → `kosong-states` → `paintings-states` → `variants-001-012`.

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
| 019 | [hero-beranda-santri](prompts/019-hero-beranda-santri.md) | paintings-pages | painting | Home hero, identity version | done |
| 020 | [beranda-kebersamaan](prompts/020-beranda-kebersamaan.md) | paintings-pages | painting | Home: learning together (Bhinneka) | done |
| 021 | [beranda-langkah](prompts/021-beranda-langkah.md) | paintings-pages | painting | Home: three steps to register | done |
| 022 | [persyaratan](prompts/022-persyaratan.md) | paintings-pages | painting | `/persyaratan` | done |
| 023 | [jadwal](prompts/023-jadwal.md) | paintings-pages | painting | `/jadwal` | done |
| 024 | [tanya-jawab](prompts/024-tanya-jawab.md) | paintings-pages | painting | `/tanya-jawab` | done |
| 025 | [cek-status](prompts/025-cek-status.md) | paintings-pages | painting | `/cek-status` | done |
| 026 | [kartu](prompts/026-kartu.md) | paintings-pages | painting | `/cek-status/kartu`, card settings | done |
| 027 | [tes](prompts/027-tes.md) | paintings-pages | painting | Test schedule and test pages | done |
| 035 | [bagikan-umum](prompts/035-bagikan-umum.md) | paintings-pages | painting | Default link-preview image | done |
| 028 | [akses-ditolak](prompts/028-akses-ditolak.md) | paintings-states | painting | 403 / no permission | requested |
| 029 | [sesi-berakhir](prompts/029-sesi-berakhir.md) | paintings-states | painting | Session expired | requested |
| 030 | [pendaftaran-ditutup](prompts/030-pendaftaran-ditutup.md) | paintings-states | painting | Registration not open | requested |
| 031 | [pemeliharaan](prompts/031-pemeliharaan.md) | paintings-states | painting | Maintenance (503) | requested |
| 032 | [kosong-jadwal](prompts/032-kosong-jadwal.md) | paintings-states | painting | No test schedule yet | requested |
| 033 | [kosong-berkas](prompts/033-kosong-berkas.md) | paintings-states | painting | No documents uploaded | requested |
| 034 | [tanpa-koneksi](prompts/034-tanpa-koneksi.md) | paintings-states | painting | Offline / connection lost | requested |
| 036 | [ikon-persyaratan](prompts/036-ikon-persyaratan.md) | ikon-beranda | painting | Home info card + art button "Lihat Persyaratan" | requested |
| 037 | [ikon-jadwal](prompts/037-ikon-jadwal.md) | ikon-beranda | painting | Home info card "Jadwal" | requested |
| 038 | [ikon-cek-status](prompts/038-ikon-cek-status.md) | ikon-beranda | painting | Home info card + art button "Cek Status" | requested |
| 039 | [ikon-tanya-jawab](prompts/039-ikon-tanya-jawab.md) | ikon-beranda | painting | Home info card "Tanya jawab" | requested |
| 040 | [ikon-panduan](prompts/040-ikon-panduan.md) | ikon-beranda | painting | Home info card + art button "Baca Panduan Pendaftaran" | requested |
| 041 | [ikon-daftar](prompts/041-ikon-daftar.md) | ikon-beranda | painting | Raised "Daftar" in the public bottom bar | requested |
| 042 | [ikon-alamat](prompts/042-ikon-alamat.md) | ikon-beranda | painting | Home contact card "Alamat" | requested |
| 043 | [ikon-telepon](prompts/043-ikon-telepon.md) | ikon-beranda | painting | Home contact card "Telepon" | requested |
| 044 | [ikon-pesan](prompts/044-ikon-pesan.md) | ikon-beranda | painting | Home contact card "WhatsApp" | requested |
| 045 | [ikon-email](prompts/045-ikon-email.md) | ikon-beranda | painting | Home contact card "Email" | requested |
| 046 | [langkah-1](prompts/046-langkah-1.md) | langkah | painting | Home "Cara mendaftar" step 1 (scene, D-090) | done |
| 047 | [langkah-2](prompts/047-langkah-2.md) | langkah | painting | Home "Cara mendaftar" step 2 (scene, D-090) | done |
| 048 | [langkah-3](prompts/048-langkah-3.md) | langkah | painting | Home "Cara mendaftar" step 3 (scene, D-090) | done |
| 072 | [latar-langkah](prompts/072-latar-langkah.md) | langkah | SVG + CSS | Art backgrounds of the three "Cara mendaftar" cards | done |
| 049 | [tombol-seni](prompts/049-tombol-seni.md) | tombol-seni | SVG + CSS | Art buttons `.tombol-seni` and the bottom-bar badge | requested |
| 050 | [hero-slide-belajar](prompts/050-hero-slide-belajar.md) | slide-beranda | painting | Home hero slideshow (default slide) | requested |
| 051 | [hero-slide-keluarga](prompts/051-hero-slide-keluarga.md) | slide-beranda | painting | Home hero slideshow (default slide) | requested |
| 052 | [kosong-jenis-berkas](prompts/052-kosong-jenis-berkas.md) | kosong-states | painting | Empty: no document types | requested |
| 053 | [kosong-antrean-berkas](prompts/053-kosong-antrean-berkas.md) | kosong-states | painting | Empty: document queue all checked | requested |
| 054 | [kosong-sekolah-asal](prompts/054-kosong-sekolah-asal.md) | kosong-states | painting | Empty: no origin schools | requested |
| 055 | [kosong-sekolah-lain](prompts/055-kosong-sekolah-lain.md) | kosong-states | painting | Empty: every school matched | requested |
| 056 | [kosong-penjadwalan](prompts/056-kosong-penjadwalan.md) | kosong-states | painting | Empty: nobody left to schedule | requested |
| 057 | [kosong-riwayat-jadwal](prompts/057-kosong-riwayat-jadwal.md) | kosong-states | painting | Empty: no schedule changes | requested |
| 058 | [kosong-sesi](prompts/058-kosong-sesi.md) | kosong-states | painting | Empty: no test sessions | requested |
| 059 | [kosong-ruang](prompts/059-kosong-ruang.md) | kosong-states | painting | Empty: no test rooms | requested |
| 060 | [kosong-peserta](prompts/060-kosong-peserta.md) | kosong-states | painting | Empty: no test participants | requested |
| 061 | [kosong-peserta-sesi](prompts/061-kosong-peserta-sesi.md) | kosong-states | painting | Empty: session without participants | requested |
| 062 | [kosong-sesi-hari-ini](prompts/062-kosong-sesi-hari-ini.md) | kosong-states | painting | Empty: no session on the chosen day | requested |
| 063 | [kosong-gelombang](prompts/063-kosong-gelombang.md) | kosong-states | painting | Empty: no registration waves | requested |
| 064 | [kosong-pendaftar](prompts/064-kosong-pendaftar.md) | kosong-states | painting | Empty: no applicants | requested |
| 065 | [kosong-tanya-jawab](prompts/065-kosong-tanya-jawab.md) | kosong-states | painting | Empty: no Q&A (public and dashboard) | requested |
| 066 | [kosong-jadwal-pendaftaran](prompts/066-kosong-jadwal-pendaftaran.md) | kosong-states | painting | Empty: `/jadwal` not announced | requested |
| 067 | [kosong-persyaratan](prompts/067-kosong-persyaratan.md) | kosong-states | painting | Empty: `/persyaratan` not announced | requested |
| 068 | [kartu-cari](prompts/068-kartu-cari.md) | kosong-states | painting | Empty: card lookup, nothing checked yet | requested |
| 069 | [kartu-tidak-ditemukan](prompts/069-kartu-tidak-ditemukan.md) | kosong-states | painting | Card lookup: not found | requested |
| 070 | [kosong-pengguna](prompts/070-kosong-pengguna.md) | kosong-states | painting | Empty: no matching users | requested |
| 071 | [kosong-dasbor](prompts/071-kosong-dasbor.md) | kosong-states | painting | Empty: dashboard summary | requested |
| 013 | [aksen-hijau](prompts/013-aksen-hijau.md) | variants-001-012 | image edit | Green-accent copies of 001–012 | requested |
| 014 | [varian-gelap](prompts/014-varian-gelap.md) | variants-001-012 | image edit | Dark copies of 001, 002, 006, 011, 012 | requested |

Notes

- 001–012 were painted by ChatGPT before D-073 (PNG originals in `generated/`); new work follows `CODEX.md`.
- Every new painting (019–034) comes with dark, green-accent and dark green-accent variants; 035 with a
  green-accent variant only.
- 017 uses the kawung tile from 015 and the small loader from 018, so the `svg-kit` batch ships together.
- **From 036 on (D-083)** every painting is transparent, reads on light and dark pages and comes with a
  green-accent variant only — no `-dark` copies. New areas: `ikon` (≤ 512 px, ≤ 60 KB) and `langkah`
  (≤ 640 px, ≤ 90 KB; the step scenes 046–048 ≤ 800 px, ≤ 110 KB since D-090). File names of 036–071 are fixed: the views already load them (`art_icon()`, empty
  states with `'fallback'`) and show a Lucide icon or a generic painting until they arrive.
- 049 replaces the interim `.tombol-seni` and `.nav-bawah-tengah-bulat` CSS in `resources/css/komponen.css`
  and holds the painted `ikon-daftar` (041), so deliver it after `ikon-beranda`.
- 019, 020 and 027 were repainted for D-089 in #49 and are installed. 019 is the first home hero slide; 020
  (school character section) and 027 (test pages) have no slot in the views yet.
- The `langkah` batch (046–048 scenes, 072 card backgrounds, D-090) is installed; 072's CSS replaced the interim
  `.kartu-langkah` block.
- Every empty state (052–071) shows its own scene; no two empty states may look alike, also not like 004,
  005, 009, 010 or 028–034.
