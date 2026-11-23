---
id: 063
name: kosong-gelombang
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states
status: requested
---

# 063 — kosong-gelombang

## Purpose

A painting for one specific empty state: a small wooden boat resting on a calm beach, waiting for the first wave.

**Condition:** No registration wave (gelombang) exists yet; the first one must be added before registration can open.

## Where it is used

Dashboard → Pengaturan → Gelombang (`app/Modules/Pendaftaran/Views/admin/gelombang/index.php`), in `partials/empty_state` (`'image' => 'state/kosong-gelombang'`). Shown about 160–240 px wide, centred above a short heading, one sentence and sometimes a button (all HTML). Installed trimmed to the subject, longest side ≤ 480 px, budget ≤ 70 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- A small wooden boat resting on a calm beach, waiting for the first wave.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A small traditional wooden boat (jukung style, no outriggers needed) pulled up on pale sand.
- Very calm water with only a faint line, no waves yet; a coconut palm leaning at one side.
- The boat's small sail rolled up; a medium-blue stripe on the hull.

## Colour and light

Pale sand, soft sage water, warm wood. Blue accent: the stripe on the hull.

## Avoid

- Sad, stressed or broken things; red; warning signs
- Anything another empty state already shows (see the list in `ai/README.md`)
- Big waves, storms or anything dramatic
- Flags on the boat
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

Square image. Small simple vignette, centred, generous empty margin, calm and friendly. A small traditional Indonesian wooden boat with a rolled-up sail resting on pale sand at the edge of very calm sage-tinted water with no waves, a coconut palm leaning at one side; the hull has one medium-blue stripe. Peaceful, waiting for the first wave.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-gelombang.webp` | `state/kosong-gelombang.webp` |
| hijau | `ai/generated/kosong-gelombang-hijau.webp` | `state/kosong-gelombang-hijau.webp` |

## Alt text (Indonesian, used in the view)

Perahu kecil menunggu di pantai yang tenang.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 063` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Clearly different from every other empty state (004, 005, 009, 010, 028–034 and 052–071); the scene matches its condition
- [ ] Reads at 160 px
