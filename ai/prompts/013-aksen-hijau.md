---
id: 013
name: aksen-hijau
kind: edit
batch: variants-001-012
file: <name>-hijau.webp per illustration below (dark ones <name>-dark-hijau.webp); master in ai/generated/, installed copy in ai/generated/install/img/<area>/
target: public/assets/img/<area>/<name>-hijau.webp
usage: Optional "green accent" set for schools whose brand colour is green (first client: SMP NU Al Ma'ruf Kudus, brand #005C2F). Chosen in Pengaturan → Identitas Sekolah → Warna aksen ilustrasi.
shape: same as each original
size: same as each original
background: same as each original (transparent where the original is transparent)
variants: light (+ dark where the original has a dark version)
status: requested
---

# 013 — aksen-hijau (green-accent versions of 001–012)

## Purpose

The existing illustrations use one small accent in medium blue `#2563EB` so it can be recoloured for
other schools (style guide, palette). For a green-branded school the blue clashes with the green buttons
and logo. This task makes a recoloured copy of each illustration where **only the blue accent elements
become green**. Everything else stays identical.

## What to do, for each original in `ai/generated/`

Work from the delivered PNG originals, one at a time, in this order:
001 hero-beranda, 002 daftar-online, 003 masuk-dasbor (+ dark), 004 kosong-data (+ dark),
005 kosong-pencarian (+ dark), 006 sukses-pendaftaran, 007 tidak-ditemukan (+ dark),
008 gangguan-sistem (+ dark), 009 kosong-riwayat (+ dark), 010 fitur-tambahan (+ dark),
011 panduan-petugas, 012 panduan-calon-murid.

- Use **image editing on the original**, not a fresh generation, so the composition,
  characters, faces and brush texture stay the same.
- Change only the blue accent elements (e.g. a phone case, an icon, a card, a chart bar, a book cover,
  a shield) to a **deep leaf green `#1F7A45`** with the same painted texture and shading. Small highlights
  may use a soft yellow `#F2C94C` (taken from the school logo) on at most one tiny element.
- Do **not** recolour the navy uniforms (`#1E2A4A`), the sage/olive plants, skin, hijabs or the background.
- Keep the same background: transparent stays transparent (real alpha, no checkerboard).
- Save the master as `ai/generated/<original-name>-hijau.webp` (dark originals: `<original-name>-dark-hijau.webp`), quality 92, and the installed copy at the original's target size (see `ai/generated/README.md` → Files and targets) in `ai/generated/install/img/<area>/`.

## Avoid

- Any change to composition, faces, poses or proportions; any new object.
- Large green areas: the accent stays as small as the original blue accent.
- Logos, the NU emblem, Arabic script, flags or text of any kind (the school logo is shown by the app, never
  painted into illustrations).
- Everything listed under "Never" and "Rules for every image" in the style guide.

## Prompt (image edit on the original)

```
Edit this exact illustration. Keep everything identical — composition, characters, faces, poses, brush
texture, colours and the background (keep real transparency if it is transparent). Change ONLY the small
medium-blue (#2563EB) accent elements into a deep leaf green (#1F7A45) with the same painted gouache
texture and shading. Do not recolour the navy school uniforms, plants, skin or hijabs. Do not add anything.
Absolutely no text, letters, numbers, logos or symbols.
```

## Alt text (Indonesian, used in the view)

Same as each original (the views keep their existing alt text).

## Acceptance checks

- [ ] Composition, faces and brush texture identical to the original (compare side by side at 100 %)
- [ ] Installed copies have the same width as the existing files in `public/assets/img/<area>/`
- [ ] Transparent where required (no checkerboard), no text or symbols added
