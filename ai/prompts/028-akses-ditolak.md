---
id: 028
name: akses-ditolak
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, dark, hijau, dark-hijau
batch: paintings-states
status: requested
---

# 028 — akses-ditolak

## Purpose

A page the user may not open (403): calm, not scolding — "this room is for someone else".

## Where it is used

Error page for 403 (`app/Views/errors/html/error_403.php` or the shared error layout) and pages where a role lacks permission. Installed trimmed to the subject, longest side ≤ 480 px; shown about 160–240 px wide, centred above a short heading, one sentence and a button (all HTML).

## Composition

- A closed carved wooden door with a geometric panel, seen straight on.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A small brass key hanging on a hook beside the door, a potted plant, a doormat with a woven pattern; the door has a plain round handle.

## Colour and light

Warm wood, cream wall, green family plant; blue accent on the key tag.

## Avoid

- Locks, chains, red signs, warning symbols
- Arabic or any carving that looks like writing
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

Square image. Transparent background (real alpha), the painting as an isolated vignette with soft painted edges. A closed wooden door with a simple carved geometric panel (no letters or calligraphy) in a cream wall, a plain round handle, a small brass key on a hook beside it with a tiny medium-blue tag, a potted plant and a woven doormat. Small simple vignette, centred, generous empty margin, calm and friendly.
```

Variants: dark (style guide §8), green accent (§9), dark green accent (§9 applied to the dark image).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/akses-ditolak.webp` | `state/akses-ditolak.webp` |
| dark | `ai/generated/akses-ditolak-dark.webp` | `state/akses-ditolak-dark.webp` |
| hijau | `ai/generated/akses-ditolak-hijau.webp` | `state/akses-ditolak-hijau.webp` |
| dark-hijau | `ai/generated/akses-ditolak-dark-hijau.webp` | `state/akses-ditolak-dark-hijau.webp` |

## Alt text (Indonesian, used in the view)

Pintu ruangan yang tertutup.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 028` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, walls, clothing)
- [ ] Real transparency; no checkerboard, paper texture or colour wash outside the vignette
- [ ] The carving is plainly geometric (no shapes that look like letters)
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Dark variant reads well on `#111827` without a bright halo; colours not inverted
