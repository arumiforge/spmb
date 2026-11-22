---
id: 033
name: kosong-berkas
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-states
status: requested
---

# 033 — kosong-berkas

## Purpose

No documents uploaded yet: an inviting empty folder.

## Where it is used

Applicant document lists (`/cek-status/ubah` uploads, `/admin/pendaftar/{id}` documents) when empty. Installed trimmed to the subject, longest side ≤ 480 px; shown about 160–240 px wide, centred above a short heading, one sentence and a button (all HTML).

## Composition

- An open, empty folder lying on a table.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A paper clip, a small potted succulent beside it.

## Colour and light

Cream and sand; blue accent on the folder.

## Avoid

- Papers inside the folder
- Trash bins
- Everything in the style guide: "Respect for religious elements", "Never" and "Rules for every painting"

## Prompt

```
Hand-painted storybook illustration in soft gouache and watercolour, visible gentle brush texture,
slightly uneven painted edges, light paper grain inside the painted areas. Stylised friendly characters
with simple rounded faces, small dot eyes, rosy cheeks and simplified hands, like a modern Indonesian
children's educational book. Not photorealistic, not semi-realistic, not 3D, not CGI, no glossy shading,
no anime. Warm muted palette: cream, warm sand, slate grey, muted sage and leaf greens, a deep pesantren
green, soft terracotta and a touch of soft gold, with ONE small accent in medium blue #2563EB. Soft even
morning light. Modest Indonesian Muslim school life: girls in kerudung, boys in black peci. Absolutely no
text, letters, numbers, Arabic script, calligraphy, logos, emblems, flags, signatures or watermarks anywhere.

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. An open, empty medium-blue paper folder lies on a light wooden surface with one paper clip beside it and a small potted succulent. Small simple vignette, centred, generous empty margin, calm and friendly.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-berkas.webp` | `state/kosong-berkas.webp` |
| dark | `ai/generated/kosong-berkas-dark.webp` | `state/kosong-berkas-dark.webp` |
| hijau | `ai/generated/kosong-berkas-hijau.webp` | `state/kosong-berkas-hijau.webp` |
| dark-hijau | `ai/generated/kosong-berkas-dark-hijau.webp` | `state/kosong-berkas-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Map berkas yang masih kosong.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 033` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
