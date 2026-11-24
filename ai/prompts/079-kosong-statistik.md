---
id: 079
name: kosong-statistik
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states-2
status: requested
---

# 079 — kosong-statistik

## Purpose

Empty state of the Statistik pages (D-111) before there are applicants to count, or when a filter finds nobody.

## Where it is used

Statistik module pages (`app/Modules/Statistik`) in `partials/empty_state` (`'image' => 'state/kosong-statistik'`), about 160–240 px wide. Installed trimmed, longest side ≤ 480 px, ≤ 70 KB.

## Composition

- A woven bamboo tray holding a small stack of blank cream index cards, a pencil and a small round abacus-like string of wooden beads; nothing counted yet.
- Small vignette centred with about 15 % empty margin. Must differ clearly from 071 (pots like a bar chart).

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- Bamboo anyaman tray, blank cards, wooden beads on a string, pencil; one bead medium blue.

## Colour and light

Bamboo, cream, warm wood. Blue accent: one bead.

## Avoid

- Charts, numbers, axes, calculators, screens
- Pots or plants (071 shows those)
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

Real transparent background (true alpha): no white, paper or colour background, no glow, no halo, no
drawn checkerboard. The painting must read on a white page and on a very dark navy page (#111827) alike:
give every shape a soft painted outline in soft charcoal, keep the outer edges mid-toned (neither
near-white nor near-black) and do not add a light rim.

A small still-life vignette: a shallow woven bamboo tray holding a neat stack of blank cream index cards, a pencil and a short string of round wooden counting beads, nothing counted yet. The cards are completely blank. Faint contact shadow only, centred on real transparency with generous empty margins. One bead in medium blue is the only blue. Calm and orderly, "ready to count".
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-statistik.webp` | `state/kosong-statistik.webp` |
| hijau | `ai/generated/kosong-statistik-hijau.webp` | `state/kosong-statistik-hijau.webp` |

## Alt text (Indonesian, used in the view)

Nampan anyaman berisi kartu kosong dan manik-manik kayu untuk menghitung.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 079` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] Reads at the smallest size named under "Where it is used"
