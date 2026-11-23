# Delivered assets

Codex delivers here (see [`../CODEX.md`](../CODEX.md) → "Where files go"):

- `<name>[-variant].webp` — painting masters at native size (new work, from 015 on);
- `install/img/<area>/…` and `install/css/…` — files ready to install, already trimmed and sized;
- `demo/index.html` — preview of the SVG/CSS assets (brand blue/green, light/dark);
- this README — one delivery table per pull request, newest first.

Claude installs from `install/` into `public/assets/img/` and `resources/css/visual/`, and sets the brief
status to `done`. Nothing here is served by the app directly.

## Codex deliveries

### svg-kit (2026-11-23)

| ID | File | Size (px) | KB | Background | Review notes |
| --- | --- | --- | --- | --- | --- |
| 015 | `install/img/pola/pola-kawung.svg` | 64×64 | 0.55 | Transparent | Outlined oval petals on a diagonal grid |
| 015 | `install/img/pola/pola-khatam.svg` | 72×72 | 0.87 | Transparent | Linked eight-point outlines |
| 015 | `install/img/pola/pola-anyaman.svg` | 48×48 | 0.53 | Transparent | Rounded strips with alternating crossings |
| 015 | `install/img/pola/pola-ceplok-daun.svg` | 64×64 | 0.55 | Transparent | Four leaves around a point |
| 015 | `install/css/pola.css` | 48, 64 or 72 px tiles | 1.55 | Masked brand colour or white | 7% opacity in light mode, 9% in dark mode, optional fade to the left |
| 016 | `install/img/pemisah/pemisah-lengkung.svg` | 1440×64 | 0.19 | Transparent | Asymmetric curve, stretched across the section |
| 016 | `install/img/pemisah/pemisah-tumpal.svg` | 96×40 | 0.18 | Transparent | Repeating triangles and dots over a continuous base |
| 016 | `install/img/pemisah/pemisah-mega.svg` | 1440×72 | 0.79 | Transparent | Three bands with full-width fill at the base |
| 016 | `install/img/pemisah/pemisah-bukit.svg` | 1440×80 | 0.30 | Transparent | Overlapping hills, rear silhouette at 50% |
| 016 | `install/css/pemisah.css` | 20–80 px tall | 2.39 | Masked next-section colour | Keeps tumpal tiles whole where CSS round() is supported, hides dividers in forced colours |
| 017 | `install/img/tombol/tombol-ornamen.svg` | 56×56 | 0.49 | Transparent | Eight-petal rosette, clipped at the button edge |
| 017 | `install/css/tombol-daftar.css` | 48 or 56 px minimum height | 6.96 | Brand gradient | Includes keyboard focus, busy and disabled states, full-width option and forced colours |
| 018 | `install/img/memuat/memuat.svg` | 96×96 | 1.40 | Transparent | Segmented star in currentColor with a still frame under reduced motion |
| 018 | `install/img/memuat/memuat-kecil.svg` | 20×20 | 0.70 | Transparent | Open-ring mask with a static view for reduced motion |
| 018 | `install/css/memuat.css` | 72 px default, 1.1em inline | 1.48 | Brand colour or currentColor | Large status and inline loader styles |
| 015–018 | `demo/index.html` | Tested at 360×800 and 1280×800 | 17.06 | White or gray-900 | Switches for blue and green brands and light and dark modes |

To open the preview, serve `ai/generated/` with a local static server and open `/demo/`.

## 001–012 (ChatGPT, before D-073)

This folder contains the 12 illustration originals and seven dark variants from queue IDs
001–012. All 19 files retain their original PNG bytes. Application integration is intentionally
pending for the next agent; this delivery only changes files under `ai/`.

### Handoff

1. Follow the [style guide](../style-guide.md) and
   [image workflow](../../.claude/skills/spmb-ai-image/SKILL.md).
2. Resize and convert each original to its target under `public/assets/img/`, preserving alpha for
   transparent illustrations. Targets below are relative to that directory. Dark variants use
   the same directory and the `-dark.webp` suffix.
3. Reference the assets from the intended views with the Indonesian alt text below and use the
   matching dark variant in dark mode. Registration-page assets 002 and 006 are ready for phase 2;
   do not invent registration routes to consume them.
4. Mark queue and prompt statuses `done` only after integration and verification. Current status
   `generated` means delivery is complete and integration is pending.

The square originals are 1254×1254; the style guide permits resizing from the generator's native
output size. Landscape and portrait originals match the requested dimensions. The three landscape
images have white backgrounds; all other originals have verified alpha transparency.

### Files and targets

| ID | Original files | Original size | Target | Background | Indonesian alt text |
| --- | --- | --- | --- | --- | --- |
| 001 | [hero-beranda.png](hero-beranda.png) | 1536×1024 | `hero/hero-beranda.webp` | white | Orang tua dan calon murid tersenyum di depan sekolah, siap mendaftar. |
| 002 | [daftar-online.png](daftar-online.png) | 1024×1536 | `form/daftar-online.webp` | transparent | Orang tua dan anak mengisi formulir pendaftaran online bersama. |
| 003 | [masuk-dasbor.png](masuk-dasbor.png); [masuk-dasbor-dark.png](masuk-dasbor-dark.png) | 1024×1536 | `auth/masuk-dasbor.webp` | transparent | Petugas sekolah membuka dasbor dengan aman. |
| 004 | [kosong-data.png](kosong-data.png); [kosong-data-dark.png](kosong-data-dark.png) | 1254×1254 | `state/kosong-data.webp` | transparent | Belum ada data. |
| 005 | [kosong-pencarian.png](kosong-pencarian.png); [kosong-pencarian-dark.png](kosong-pencarian-dark.png) | 1254×1254 | `state/kosong-pencarian.webp` | transparent | Data yang dicari tidak ditemukan. |
| 006 | [sukses-pendaftaran.png](sukses-pendaftaran.png) | 1254×1254 | `state/sukses-pendaftaran.webp` | transparent | Pendaftaran berhasil, calon murid memegang kartu pendaftaran. |
| 007 | [tidak-ditemukan.png](tidak-ditemukan.png); [tidak-ditemukan-dark.png](tidak-ditemukan-dark.png) | 1254×1254 | `state/tidak-ditemukan.webp` | transparent | Halaman yang Anda cari tidak ditemukan. |
| 008 | [gangguan-sistem.png](gangguan-sistem.png); [gangguan-sistem-dark.png](gangguan-sistem-dark.png) | 1254×1254 | `state/gangguan-sistem.webp` | transparent | Petugas sedang memperbaiki gangguan kecil di sistem. |
| 009 | [kosong-riwayat.png](kosong-riwayat.png); [kosong-riwayat-dark.png](kosong-riwayat-dark.png) | 1254×1254 | `state/kosong-riwayat.webp` | transparent | Buku catatan yang masih kosong. |
| 010 | [fitur-tambahan.png](fitur-tambahan.png); [fitur-tambahan-dark.png](fitur-tambahan-dark.png) | 1254×1254 | `state/fitur-tambahan.webp` | transparent | Susunan balok yang menggambarkan fitur yang bisa ditambahkan. |
| 011 | [panduan-petugas.png](panduan-petugas.png) | 1536×1024 | `guide/panduan-petugas.webp` | white | Dua petugas sekolah membaca panduan bersama. |
| 012 | [panduan-calon-murid.png](panduan-calon-murid.png) | 1536×1024 | `guide/panduan-calon-murid.webp` | white | Orang tua dan calon murid membaca panduan pendaftaran di ponsel. |

### Review notes

- 001 and 011: corrected once to keep the title area empty.
- 003: corrected once to place the character in the lower two thirds.
- 004 dark: corrected once to reduce the halo.
- 008: corrected once to remove an extra plant.
- 012: corrected once to clear the title area and remove an extra rug.
- Dark variants retain the light illustration's composition and palette with slightly lifted edges.
