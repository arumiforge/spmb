---
id: 066
name: kosong-jadwal-pendaftaran
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states
status: requested
---

# 066 — kosong-jadwal-pendaftaran

## Purpose

A painting for one specific empty state: a wooden easel with a blank board and a rolled-up poster leaning against it, ready to be put up.

**Condition:** The committee has not announced the registration schedule yet; families should check again later.

## Where it is used

Public `/jadwal` (`app/Modules/Situs/Views/jadwal.php`), in `partials/empty_state` (`'image' => 'state/kosong-jadwal-pendaftaran'`). Shown about 160–240 px wide, centred above a short heading, one sentence and sometimes a button (all HTML). Installed trimmed to the subject, longest side ≤ 480 px, budget ≤ 70 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- A wooden easel with a blank board and a rolled-up poster leaning against it, ready to be put up.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A simple wooden easel holding a blank cream board.
- A rolled-up poster tied with a sage ribbon leaning against the easel's leg; the roll's edge shows a medium-blue stripe.
- A potted plant beside it.

## Colour and light

Warm wood, cream board, sage ribbon. Blue accent: the stripe on the rolled poster.

## Avoid

- Sad, stressed or broken things; red; warning signs
- Anything another empty state already shows (see the list in `ai/README.md`)
- A calendar or a cork board with pins (032)
- Writing on the board
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

Square image. Small simple vignette, centred, generous empty margin, calm and friendly. A simple wooden easel holding a blank cream board, a rolled-up poster tied with a sage ribbon leaning against one leg with a medium-blue stripe on its edge, and a potted plant beside it. Coming soon, calm and hopeful; no writing.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-jadwal-pendaftaran.webp` | `state/kosong-jadwal-pendaftaran.webp` |
| hijau | `ai/generated/kosong-jadwal-pendaftaran-hijau.webp` | `state/kosong-jadwal-pendaftaran-hijau.webp` |

## Alt text (Indonesian, used in the view)

Papan kosong di atas penyangga dengan poster yang belum dipasang.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 066` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Clearly different from every other empty state (004, 005, 009, 010, 028–034 and 052–071); the scene matches its condition
- [ ] Reads at 160 px
