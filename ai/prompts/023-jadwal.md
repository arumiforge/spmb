---
id: 023
name: jadwal
kind: painting
area: section
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-pages
status: generated
---

# 023 — jadwal

## Purpose

Turns dates and waves into something friendly: a teacher shows a family the schedule on a big calendar.

## Where it is used

Public page `/jadwal` (waves and dates), beside the heading. Installed trimmed to the subject, longest side ≤ 800 px. Desktop (≥ 1024 px): beside the page heading, about 320–380 px wide, heading and intro text on the left. Phones (360 px): above the heading, about 200 px wide, centred. It must read clearly at 200 px.

## Composition

- Square vignette. A large wall calendar on the left half (grid of plain blocks, one row highlighted), people on the right half.

## Characters (cast in `ai/style-guide.md`)

- Bu Nur points to one highlighted block on the calendar.
- Pak Hadi with Fajar beside him, both looking and nodding.

## Setting and props

- Calendar: a grid of blank squares, no numbers, no month names; one block in the accent colour, two blocks with a small soft green dot.
- A cream wall, a wooden bench, a potted plant, a window with a mango tree outside.

## Colour and light

Cream wall and calendar, green family plants; blue accent on the highlighted calendar block only.

## Avoid

- Numbers, weekday or month letters on the calendar
- Clocks with numerals
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. On a cream wall hangs a large paper calendar made only of blank squares (no numbers, no letters); one block is filled in medium blue and two blocks have a small soft green dot. A female teacher in a white kerudung, a navy cardigan over a cream blouse and a lanyard with a blank card points kindly at the blue block. Beside her, a father in a black peci and a batik-like shirt and his Chinese-Indonesian son in a black peci, white shirt and navy trousers look at it and nod. A wooden bench, a potted plant, and a window showing a mango tree. Calm and clear, compact composition with generous empty margin.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/jadwal.webp` | `section/jadwal.webp` |
| dark | `ai/generated/jadwal-dark.webp` | `section/jadwal-dark.webp` |
| hijau | `ai/generated/jadwal-hijau.webp` | `section/jadwal-hijau.webp` |
| dark-hijau | `ai/generated/jadwal-dark-hijau.webp` | `section/jadwal-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Guru menunjukkan jadwal pendaftaran kepada ayah dan anak.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 023` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Calendar has no numbers or letters, only blocks
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
