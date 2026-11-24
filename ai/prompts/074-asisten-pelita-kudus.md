---
id: 074
name: asisten-pelita-kudus
kind: painting
area: ikon
generate: square 1024x1024
background: transparent
variants: light, hijau
batch: asisten-chat
status: requested
---

# 074 — asisten-pelita-kudus

## Purpose

The first client's own version of the assistant (SMP NU Al Ma'ruf Kudus): the same pelita from brief 073 standing on a small carved wooden base in the style of Kudus gebyok woodcarving, so the assistant feels local to Kudus. Client-specific: it ships in the client pack, never as generic art.

## Where it is used

Same places as 073, for this school only. Installed by the coordinator into `clients/smp-nu-al-maruf-kudus/branding/asisten.webp` (and `asisten-hijau.webp`), loaded by `spmb:branding` as the school's assistant picture. Longest side ≤ 512 px, ≤ 60 KB.

## Composition

- Exactly the pelita character of 073 (same shape, face and colours), standing on a low carved teak-wood base.
- The base shows Kudus gebyok-style relief carving: curling leaves and flower tendrils only, warm teak brown with soft gold highlights.
- Centred with about 12 % empty margin; reads at 32 px.

## Characters (cast in `ai/style-guide.md`)

- None.

## Setting and props

- Low rectangular teak base with leaf-and-tendril relief carving (Kudus gebyok style), no figures, no animals, no writing.
- Medium-blue accent: the bead on the lamp handle, as in 073.

## Colour and light

Teak brown and soft gold carving, the lamp in terracotta and pesantren green. Blue accent: the bead.

## Avoid

- The Menara Kudus tower, mosque or minaret shapes, domes, crescents, stars
- The school logo, its trophy, letters or the NU emblem
- A full gebyok wall or doorway (only the small carved base)
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

The same small terracotta oil-lamp character with a deep green band, soft gold rim and handle, warm soft flame, two dot eyes and a tiny smile, now standing on a low carved teak-wood base decorated with Kudus gebyok-style relief carving of curling leaves and flower tendrils in warm teak brown with soft gold highlights, three-quarter view from slightly above, light from the upper left, no ground and no cast shadow, centred on real transparency with a generous empty margin. One tiny medium-blue bead on the handle is the only blue. Warm, local and welcoming, readable as a tiny round avatar.
```

Variant: green accent (§9) only. No dark variant (D-083).

## Files

| Variant | Master | Installed (`ai/generated/install/img/…`) |
| --- | --- | --- |
| light | `ai/generated/asisten-pelita-kudus.webp` | `ikon/asisten-pelita-kudus.webp` |
| hijau | `ai/generated/asisten-pelita-kudus-hijau.webp` | `ikon/asisten-pelita-kudus-hijau.webp` |

Client-specific: the coordinator copies the installed files to `clients/smp-nu-al-maruf-kudus/branding/asisten.webp` and `asisten-hijau.webp`, not to `public/assets/img/`.

## Alt text (Indonesian, used in the view)

Pelita kecil yang tersenyum di atas alas ukiran kayu khas Kudus, tanda asisten otomatis website.

## Acceptance checks

- [ ] `python3 ai/tools/check_assets.py 074` passes
- [ ] Empty area really empty; no text, letters, Arabic-like marks or emblems anywhere
- [ ] Reads on `#FFFFFF` and on `#111827`; no glow, halo or light rim
- [ ] Reads at the smallest size named under "Where it is used"
