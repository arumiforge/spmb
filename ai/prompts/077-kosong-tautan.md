---
id: 077
name: kosong-tautan
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states-2
status: requested
---

# 077 — kosong-tautan

## Purpose

Empty state of Website → Tautan bawah website when every footer link is removed (D-105).

## Where it is used

`app/Modules/Situs/Views/admin/tautan.php` in `partials/empty_state` (`'image' => 'state/kosong-tautan'`), about 160–240 px wide above a heading, one sentence and buttons (HTML). Installed trimmed, longest side ≤ 480 px, ≤ 70 KB.

## Composition

- A short wooden signpost with three blank arrow-shaped boards pointing in different directions, at its foot a small coil of rope.
- Small vignette centred with about 15 % empty margin.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- Teak signpost, cream blank arrow boards, sage grass tuft at the foot; one board edged in medium blue.

## Colour and light

Teak, cream, sage. Blue accent: the edge of one arrow board.

## Avoid

- Chains or broken links, red, any writing on the boards
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

A small vignette: a short wooden signpost with three blank cream arrow-shaped boards pointing left, right and up, a small tuft of sage grass and a neat coil of rope at its foot. The boards are completely blank. Faint contact shadow only, centred on real transparency with generous empty margins. The edge of one board in medium blue is the only blue. Friendly, "choose where the paths lead".
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-tautan.webp` | `state/kosong-tautan.webp` |
| hijau | `ai/generated/kosong-tautan-hijau.webp` | `state/kosong-tautan-hijau.webp` |

## Alt text (Indonesian, used in the view)

Tiang penunjuk arah kayu dengan papan panah yang masih kosong.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 077` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] Reads at the smallest size named under "Where it is used"
