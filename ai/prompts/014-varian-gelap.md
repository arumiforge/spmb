---
id: 014
name: varian-gelap
kind: edit
batch: variants-001-012
file: <name>-dark.webp for 001, 002, 006, 011, 012 (and <name>-dark-hijau.webp after 013); master in ai/generated/, installed copy in ai/generated/install/img/<area>/
target: public/assets/img/<area>/<name>-dark.webp
usage: Dark mode of the public home hero, registration form, success page and guide covers. Today these show a white painted card on the dark page.
shape: same as each original
size: same as each original
background: transparent
variants: dark
status: requested
---

# 014 — varian-gelap (dark versions of 001, 002, 006, 011, 012)

## Purpose

These five illustrations have no dark version, so in dark mode they appear as a bright white block on the
dark navy page (`#111827`). Make the dark version of each, following "Dark variants" in the style guide.

## What to do

For each of 001 hero-beranda, 002 daftar-online, 006 sukses-pendaftaran, 011 panduan-petugas and
012 panduan-calon-murid (originals in `ai/generated/`):

- Edit the original with the dark variant prompt below (image editing, not a new generation).
- Save the master as `ai/generated/<original-name>-dark.webp` (quality 92) and the installed copy in
  `ai/generated/install/img/<area>/<original-name>-dark.webp` at the original's target size. After 013 is
  accepted, repeat on the green version: `<original-name>-dark-hijau.webp`.

## Avoid

- Inverted colours, a dark painted background, or a drawn checkerboard (the background must be real
  transparency).
- Everything listed under "Never" and "Rules for every image" in the style guide.

## Dark variant prompt (image edit on the original)

```
Now make the dark-mode version of exactly this illustration: same painting, composition,
characters and colours, on a real transparent background (remove any white or paper background). Lift the
outlines and light areas slightly and add a faint light rim around the subject so it reads well on a very
dark navy page (#111827). Do not invert the colours. Still no text anywhere.
```

## Alt text (Indonesian, used in the view)

Same as each original.

## Acceptance checks

- [ ] Composition, faces and brush texture identical to the original (compare side by side at 100 %)
- [ ] Installed copies have the same width as the existing files in `public/assets/img/<area>/`
- [ ] Transparent where required (no checkerboard), no text or symbols added
