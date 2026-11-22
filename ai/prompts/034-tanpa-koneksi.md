---
id: 034
name: tanpa-koneksi
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-states
status: requested
---

# 034 — tanpa-koneksi

## Purpose

The internet connection dropped (offline or slow network): gentle and a little playful.

## Where it is used

Offline notice and failed-request messages (e.g. upload failed because of the connection). Installed trimmed to the subject, longest side ≤ 480 px; shown about 160–240 px wide, centred above a short heading, one sentence and a button (all HTML).

## Composition

- A kite (layang-layang) resting on a tree branch, its string hanging loose.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A simple diamond-shaped kite with a short tail caught gently on a mango branch; a phone lying on a woven mat below with abstract faded bars on its screen.

## Colour and light

Green family leaves, cream sky; blue accent on the kite.

## Avoid

- Broken phones, warning signs, Wi-Fi symbols with a cross
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. A simple diamond-shaped medium-blue kite with a short tail rests gently on a mango tree branch, its string hanging loose; below, a phone lies on a woven mat, its screen showing a few faded abstract bars. Light, gently playful mood. Small simple vignette, centred, generous empty margin, calm and friendly.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/tanpa-koneksi.webp` | `state/tanpa-koneksi.webp` |
| dark | `ai/generated/tanpa-koneksi-dark.webp` | `state/tanpa-koneksi-dark.webp` |
| hijau | `ai/generated/tanpa-koneksi-hijau.webp` | `state/tanpa-koneksi-hijau.webp` |
| dark-hijau | `ai/generated/tanpa-koneksi-dark-hijau.webp` | `state/tanpa-koneksi-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Layang-layang tersangkut di dahan pohon.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 034` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
