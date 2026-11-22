---
id: 030
name: pendaftaran-ditutup
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-states
status: requested
---

# 030 — pendaftaran-ditutup

## Purpose

Registration is not open (not yet, or already closed): friendly "please come back" mood.

## Where it is used

Public `/daftar` when no wave is open, and the home page notice. Installed trimmed to the subject, longest side ≤ 480 px; shown about 160–240 px wide, centred above a short heading, one sentence and a button (all HTML).

## Composition

- A closed low school gate in a garden, seen from the front.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A small standing calendar made of blank squares beside the gate, frangipani flowers fallen on the path, a potted plant.

## Colour and light

Cream and green family; blue accent on one calendar square.

## Avoid

- Locks, chains, "closed" signs, barriers, red
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. A low, simple school gate painted cream and closed gently, with a garden path, a few frangipani flowers on the ground and a potted plant; beside the gate a small standing calendar made only of blank squares, one of them medium blue. Peaceful, "see you soon" mood. Small simple vignette, centred, generous empty margin, calm and friendly.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/pendaftaran-ditutup.webp` | `state/pendaftaran-ditutup.webp` |
| dark | `ai/generated/pendaftaran-ditutup-dark.webp` | `state/pendaftaran-ditutup-dark.webp` |
| hijau | `ai/generated/pendaftaran-ditutup-hijau.webp` | `state/pendaftaran-ditutup-hijau.webp` |
| dark-hijau | `ai/generated/pendaftaran-ditutup-dark-hijau.webp` | `state/pendaftaran-ditutup-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Gerbang sekolah yang sedang tertutup.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 030` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
