---
id: 032
name: kosong-jadwal
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-states
status: requested
---

# 032 — kosong-jadwal

## Purpose

No test schedule or session yet: waiting calmly.

## Where it is used

Test session lists and applicant schedule view when nothing is scheduled (`/admin/tes/sesi`, `/cek-status` test section). Installed trimmed to the subject, longest side ≤ 480 px; shown about 160–240 px wide, centred above a short heading, one sentence and a button (all HTML).

## Composition

- An empty wall calendar with a pushpin.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A blank calendar sheet of empty squares pinned to a cork board, a sprig of jasmine tucked behind it, a pencil on a small ledge.

## Colour and light

Cream and sand; blue accent on the pushpin.

## Avoid

- Numbers or letters on the calendar
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. A blank calendar sheet made only of empty squares pinned to a small cork board with one medium-blue pushpin, a sprig of jasmine tucked behind it and a pencil on a little ledge. Small simple vignette, centred, generous empty margin, calm and friendly.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-jadwal.webp` | `state/kosong-jadwal.webp` |
| dark | `ai/generated/kosong-jadwal-dark.webp` | `state/kosong-jadwal-dark.webp` |
| hijau | `ai/generated/kosong-jadwal-hijau.webp` | `state/kosong-jadwal-hijau.webp` |
| dark-hijau | `ai/generated/kosong-jadwal-dark-hijau.webp` | `state/kosong-jadwal-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Kalender yang masih kosong.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 032` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
