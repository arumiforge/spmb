# Delivered illustration originals

This folder contains the 12 illustration originals and seven dark variants from queue IDs
001–012. All 19 files retain their original PNG bytes. Application integration is intentionally
pending for the next agent; this delivery only changes files under `ai/`.

## Handoff

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

## Files and targets

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

## Review notes

- 001 and 011: corrected once to keep the title area empty.
- 003: corrected once to place the character in the lower two thirds.
- 004 dark: corrected once to reduce the halo.
- 008: corrected once to remove an extra plant.
- 012: corrected once to clear the title area and remove an extra rug.
- Dark variants retain the light illustration's composition and palette with slightly lifted edges.
