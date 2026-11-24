---
id: 078
name: kosong-pengunjung
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states-2
status: requested
---

# 078 — kosong-pengunjung

## Purpose

Empty state of Website → Pengunjung before anyone has visited the website (D-106).

## Where it is used

`app/Modules/Analitik/Views/` (visitor statistics page) in `partials/empty_state` (`'image' => 'state/kosong-pengunjung'`), about 160–240 px wide. Installed trimmed, longest side ≤ 480 px, ≤ 70 KB.

## Composition

- An open wooden school gate (two small doors) with a clean doormat in front and a terracotta pot with a young plant beside it; nobody there yet.
- Small vignette centred with about 15 % empty margin.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- Small teak double gate standing open, woven pandan doormat, terracotta pot with sprout; a small medium-blue ribbon tied on one gate handle.

## Colour and light

Teak, pandan straw, terracotta, leaf green. Blue accent: the ribbon.

## Avoid

- Footprints, people, counters or numbers, a recognisable school building
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

A small welcoming vignette: a little teak double gate standing open, a woven pandan doormat in front of it and a terracotta pot with a young leaf-green plant beside it, nobody there yet. No building behind the gate. Faint contact shadow only, centred on real transparency with generous empty margins. A small ribbon tied on one gate handle in medium blue is the only blue. Hopeful, "waiting for the first guests".
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-pengunjung.webp` | `state/kosong-pengunjung.webp` |
| hijau | `ai/generated/kosong-pengunjung-hijau.webp` | `state/kosong-pengunjung-hijau.webp` |

## Alt text (Indonesian, used in the view)

Gerbang kayu kecil yang terbuka menunggu tamu pertama.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 078` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] Reads at the smallest size named under "Where it is used"
