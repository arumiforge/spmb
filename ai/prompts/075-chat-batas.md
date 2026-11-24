---
id: 075
name: chat-batas
kind: painting
area: state
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: asisten-chat
status: requested
---

# 075 — chat-batas

## Purpose

Shown in the chat widget when a visitor has used all their questions for today, or the website's daily total is used up: friendly, not a refusal. The text below it (HTML) invites them to continue on WhatsApp with the committee.

## Where it is used

Inside the chat widget panel (`app/Modules/Asisten`), centred above a short sentence and a "Lanjut lewat WhatsApp" button. Shown about 120–160 px wide. Installed trimmed, longest side ≤ 480 px, ≤ 70 KB.

## Composition

- The pelita character of 073, its flame small and calm, resting beside a simple smartphone lying on its back.
- The phone screen shows only two or three abstract rounded chat-bubble shapes in soft green and cream, no letters or icons.
- Small vignette centred with about 15 % empty margin.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- Simple smartphone with abstract chat bubbles (no brand logo, no WhatsApp mark).
- The pelita from 073.

## Colour and light

Cream phone, soft green bubbles, terracotta lamp. Blue accent: the bead on the lamp handle.

## Avoid

- Clocks, stop signs, red, crossed-out symbols, sad faces
- The WhatsApp logo or any app logo
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

A small calm scene: the friendly terracotta oil-lamp character with a deep green band, soft gold rim, a small calm flame, dot eyes and a gentle smile, resting beside a simple cream smartphone that lies on its back; the phone screen shows only two or three abstract rounded chat-bubble shapes in soft green and cream with no letters, icons or logos. No ground and no cast shadow beyond a faint contact shadow, centred on real transparency with generous empty margins. One tiny medium-blue bead on the lamp handle is the only blue. Gentle and reassuring, "let's continue another way".
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/chat-batas.webp` | `state/chat-batas.webp` |
| hijau | `ai/generated/chat-batas-hijau.webp` | `state/chat-batas-hijau.webp` |

## Alt text (Indonesian, used in the view)

Pelita kecil beristirahat di samping ponsel berisi balon percakapan.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 075` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] Reads at the smallest size named under "Where it is used"
