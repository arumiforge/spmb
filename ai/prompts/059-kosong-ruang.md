---
id: 059
name: kosong-ruang
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states
status: requested
---

# 059 — kosong-ruang

## Purpose

A painting for one specific empty state: a blank floor plan on drafting paper with a folding carpenter's ruler and a pencil.

**Condition:** No test room has been added yet, so no session can be planned.

## Where it is used

Dashboard → Tes → Ruang (`app/Modules/Tes/Views/ruang/index.php`), in `partials/empty_state` (`'image' => 'state/kosong-ruang'`). Shown about 160–240 px wide, centred above a short heading, one sentence and sometimes a button (all HTML). Installed trimmed to the subject, longest side ≤ 480 px, budget ≤ 70 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- A blank floor plan on drafting paper with a folding carpenter's ruler and a pencil.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A sheet of cream drafting paper with a faint square grid and the outline of one empty room (just walls and a door gap).
- A wooden folding ruler lying across one corner, partly unfolded; its joints soft gold.
- A pencil with a medium-blue body.

## Colour and light

Cream paper, slate grid, warm wood ruler. Blue accent: the pencil.

## Avoid

- Sad, stressed or broken things; red; warning signs
- Anything another empty state already shows (see the list in `ai/README.md`)
- Numbers or measurements on the ruler or plan
- Keys or doors (028)
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

Square image. Small simple vignette, centred, generous empty margin, calm and friendly. A sheet of cream drafting paper with a faint square grid and the simple outline of one empty room with a door gap, a warm-wood folding carpenter's ruler partly unfolded across one corner with soft gold joints and no numbers, and a pencil with a medium-blue body.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-ruang.webp` | `state/kosong-ruang.webp` |
| hijau | `ai/generated/kosong-ruang-hijau.webp` | `state/kosong-ruang-hijau.webp` |

## Alt text (Indonesian, used in the view)

Denah ruangan yang masih kosong dengan penggaris lipat.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 059` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Clearly different from every other empty state (004, 005, 009, 010, 028–034 and 052–071); the scene matches its condition
- [ ] Reads at 160 px
