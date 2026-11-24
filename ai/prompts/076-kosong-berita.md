---
id: 076
name: kosong-berita
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states-2
status: requested
---

# 076 — kosong-berita

## Purpose

Empty state of Website → Berita singkat when the school has not added any news item yet (D-103).

## Where it is used

`app/Modules/Situs/Views/admin/berita.php` in `partials/empty_state` (`'image' => 'state/kosong-berita'`). Shown about 160–240 px wide above a heading, one sentence and an "Tambah berita" button (HTML). Installed trimmed, longest side ≤ 480 px, ≤ 70 KB.

## Composition

- A small wooden kentongan-style announcement board (simple frame on two short legs) with three blank cream paper notes pinned on it, one note still rolled up beside it.
- Small vignette centred with about 15 % empty margin; reads at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- Simple teak board, cream blank notes with soft green pins; the rolled note tied with a thin string.
- Medium-blue accent: one pin.

## Colour and light

Teak brown, cream paper, sage pins. Blue accent: one pin.

## Avoid

- Megaphones, loudspeakers, newspapers with headlines
- Any writing or lines that look like letters on the notes
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

A small still-life vignette: a simple teak announcement board on two short legs with three blank cream paper notes pinned with soft green pins, and one more note still rolled up and tied with string, leaning beside the board, ready to be pinned. The notes are completely blank. Faint contact shadow only, centred on real transparency with generous empty margins. One pin in medium blue is the only blue. Calm and inviting, "ready for the first announcement".
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-berita.webp` | `state/kosong-berita.webp` |
| hijau | `ai/generated/kosong-berita-hijau.webp` | `state/kosong-berita-hijau.webp` |

## Alt text (Indonesian, used in the view)

Papan pengumuman kayu dengan kertas catatan yang masih kosong.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 076` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] Reads at the smallest size named under "Where it is used"
