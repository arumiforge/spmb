---
id: 073
name: asisten-pelita
kind: painting
area: ikon
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: asisten-chat
status: requested
---

# 073 — asisten-pelita

## Purpose

The face of the website's automatic assistant ("Tanya Asisten", D-110): a friendly traditional oil lamp (pelita), the light of knowledge, so visitors see at once it is a helper and not a person. Generic for every school.

## Where it is used

Public chat widget on every public page (`app/Modules/Asisten`): the round launcher button (shown 48–56 px), the widget header (40 px), next to each answer (28–32 px) and the welcome panel (120–160 px). Installed trimmed, longest side ≤ 512 px, ≤ 60 KB. Shown as delivered on light and dark pages (D-083). A school can replace it with its own picture in the admin (D-110); the first client uses brief 074.

## Composition

- One small traditional brass-and-clay oil lamp (pelita) with a soft warm flame, three-quarter view from slightly above, centred with about 12 % empty margin.
- A gentle friendly "face" made only by the shape: two small dot eyes and a tiny smile painted on the lamp body (like a children's book object character); no arms, no hands.
- Must read at 28 px: bold simple silhouette, the flame clearly visible.

## Characters (cast in `ai/style-guide.md`)

- None (an object character, painted-icon rules in the style guide §4).

## Setting and props

- Lamp body in soft terracotta clay with a deep pesantren-green band; small brass handle and rim in soft gold.
- Flame: warm soft gold-orange, no glow outside the painted edge.
- One tiny medium-blue accent: a small round bead on the handle.

## Colour and light

Terracotta, pesantren green, soft gold flame. Blue accent: the bead on the handle.

## Avoid

- Robots, screens, chat bubbles with text, microphones
- A human face, hands, a peci or kerudung on the lamp
- Mosque or minaret shapes, crescents, stars, Arabic-like marks
- Glow, sparkles or light rays around the flame
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

A single painted-icon object character: a small traditional Indonesian oil lamp (pelita) of terracotta clay with a deep green band and a soft gold brass rim and handle, a warm soft flame on top, two small dot eyes and a tiny gentle smile painted on the round lamp body, no arms or hands, three-quarter view from slightly above, light from the upper left, no ground, no table and no cast shadow, centred on real transparency with a generous empty margin. One tiny medium-blue bead on the handle is the only blue. Friendly, calm and welcoming, readable as a tiny round avatar.
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/asisten-pelita.webp` | `ikon/asisten-pelita.webp` |
| hijau | `ai/generated/asisten-pelita-hijau.webp` | `ikon/asisten-pelita-hijau.webp` |

## Alt text (Indonesian, used in the view)

Pelita kecil yang tersenyum, tanda asisten otomatis website.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 073` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] Reads at the smallest size named under "Where it is used"
