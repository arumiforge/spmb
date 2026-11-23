---
id: 052
name: kosong-jenis-berkas
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: kosong-states
status: requested
---

# 052 — kosong-jenis-berkas

## Purpose

A painting for one specific empty state: an empty wooden document sorter (a small rack with four slanted slots) waiting to be filled.

**Condition:** The school has not defined any document types yet (for example Kartu Keluarga or Akta Kelahiran), so applicants cannot upload anything.

## Where it is used

Dashboard → Pengaturan → Berkas (`app/Modules/Berkas/Views/settings.php`), the list of document types, in `partials/empty_state` (`'image' => 'state/kosong-jenis-berkas'`). Shown about 160–240 px wide, centred above a short heading, one sentence and sometimes a button (all HTML). Installed trimmed to the subject, longest side ≤ 480 px, budget ≤ 70 KB. Shown as delivered on light and dark pages (no plate behind it, D-083), so it gets no `-dark` copy.

## Composition

- An empty wooden document sorter (a small rack with four slanted slots) waiting to be filled.
- Small, simple vignette centred with about 15 % empty margin; must read at 160 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- A small warm-wood desk sorter with four slanted slots, all empty.
- Beside it a neat stack of blank cream divider cards with tabs; the top card's tab is medium blue.
- A sprig of jasmine in a tiny glass.

## Colour and light

Warm wood, cream cards, sage jasmine leaves. Blue accent: the top divider tab.

## Avoid

- Sad, stressed or broken things; red; warning signs
- Anything another empty state already shows (see the list in `ai/README.md`)
- An open folder with papers (004, 033)
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

Square image. Small simple vignette, centred, generous empty margin, calm and friendly. An empty small warm-wood desk document sorter with four slanted slots, and beside it a neat stack of blank cream divider cards with tabs, the top tab medium blue; a sprig of jasmine in a tiny glass. Ready to be set up.
```

Variant: green accent (style guide §9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/kosong-jenis-berkas.webp` | `state/kosong-jenis-berkas.webp` |
| hijau | `ai/generated/kosong-jenis-berkas-hijau.webp` | `state/kosong-jenis-berkas-hijau.webp` |

## Alt text (Indonesian, used in the view)

Rak berkas yang masih kosong.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 052` passes
- [ ] No text, letters, numbers, Arabic-like marks, emblems or flags anywhere (zoom in on screens, papers, cards and boards)
- [ ] Real transparency; no checkerboard, paper texture, colour wash, glow or halo outside the subject
- [ ] Reads on `#FFFFFF` and on `#111827` (look at both in the demo or a screenshot); edges neither glow nor disappear
- [ ] Blue accent only on the elements named in "Colour and light"; the `-hijau` copy changes only those
- [ ] Clearly different from every other empty state (004, 005, 009, 010, 028–034 and 052–071); the scene matches its condition
- [ ] Reads at 160 px
