# Visual identity and style guide

Every visual asset in SPMB (paintings, patterns, dividers, special buttons, animation) is made by
**Codex** from the briefs in [`prompts/`](prompts/). Claude writes the briefs and installs the results
(D-073, replacing ChatGPT in D-027/D-028). Codex starts with [CODEX.md](CODEX.md), which points here.

Read this whole page before making anything. Then read the brief.

## 1. Identity (D-073)

The product feels **santri, Islamic, Nahdlatul Ulama, Indonesian and Bhinneka Tunggal Ika**: warm,
calm, respectful and welcoming to every family. Think of a well-kept madrasah or pesantren school
in the morning, where children from many backgrounds learn together.

| Theme | What it looks like in our art |
| --- | --- |
| Santri | Students in school uniform with peci (boys) and kerudung/jilbab (girls); on pesantren scenes boys in sarung and baju koko, girls in long skirts and kerudung; studying together on a veranda (serambi), shoes neatly lined up outside, a wooden low table (dampar), a closed book on a wooden book stand (rehal) |
| Islamic | Modest clothing, gentle manners (salim to a teacher or parent, greeting with a smile), a small musholla or a simple dome far in the background of an outdoor scene, arches and geometric screens in architecture |
| Nahdlatul Ulama | Green as the home colour of the art, batik and sarung in daily life, the tradition of learning with teachers (ustadz/ustadzah, kiai) — shown through people and places, **never** through the NU emblem |
| Indonesian | Tropical light, tiled roofs, frangipani and mango trees, banana leaves, rattan and bamboo, kawung and other textile motifs, warung-style benches, rice fields or hills far away |
| Bhinneka | Families and students from many regions and skin tones (see the cast); different kerudung styles; different batik and woven cloth patterns; a grandparent, parents and children of different ages and builds, a child with glasses — all together, nobody singled out |

**White label.** Every asset must suit any school using SPMB. School logos, names, mottos and the LP
Ma'arif NU logo are shown by the app from settings and the client pack (`clients/<school>/`); they are
**never** painted or drawn into generic art. The logos in `clients/smp-nu-al-maruf-kudus/originals/`
are for reference only (colour mood), not for copying.

## 2. Respect for religious elements (strict)

Many families will see, print and save these images. Treat religious content with care:

- **No Arabic script or calligraphy** in any asset: no Qur'an verses, no *lafaz Allah* or the name of the
  Prophet ﷺ, no *basmalah*, no *kalimat tauhid*, not even as a faint texture. Books, boards and screens show
  only abstract lines and blocks.
- **The mushaf is never decoration.** If a scene shows studying the Qur'an, the book is closed or seen from
  the side with no readable pages, it rests on a rehal or is held at chest height — never on the floor,
  never under other objects, never near feet or shoes.
- **No figures in prayer** (standing in salat, ruku', sujud) and no hands raised in du'a as ornament.
  Prayer is worship, not a pose for a web page.
- **No mosque or dome silhouettes in patterns, dividers, buttons, loaders or anything printed.** Patterns
  are purely geometric or botanical. A mosque may appear only as a small, respectful background building
  in a painted outdoor scene, and only when the brief allows it.
- **Nothing sacred on things that are thrown away or stepped on**: registration cards, PDFs, printed
  pages and floor-level scenes use only geometric and botanical motifs.
- **No organisation symbols**: no NU emblem (globe, rope, nine stars), no arrangement of nine stars, no
  party colours or flags, no national flag or Garuda.
- **No stereotypes**: no "traditional costume parade" where each child wears a different regional outfit;
  diversity shows through faces, skin, hair and everyday clothing. No caricatured features. Adults look
  dignified; kiai and ustadz are calm and kind, not comic.
- **Modesty**: girls and women in kerudung and loose clothing covering arms and legs; boys and men in
  shirts with sleeves and long trousers or sarung. No physical contact between unrelated adult men and
  women (a mother may hold her son's hand; a father his daughter's).
- **No disability or assistive devices (D-089)**: no wheelchairs, hearing aids, walking canes, crutches,
  white canes, prosthetics, sign-language poses or ramps shown as a subject. Diversity shows through regions,
  skin tones, ages, builds, kerudung styles and everyday clothing (glasses are fine).
- Tone: calm and welcoming. No stress, tears, punishment, exams pictured as scary, or anything broken.

If a brief seems to need something on this list, choose the calmer option and mention it in the PR.

## 3. Colour

### Brand colour comes from settings

The app sets `--brand` from `School.primaryColor` and derives `--color-primary-50 … 950` from it
(`resources/css/app.css`). Default brand is blue `#2563EB`; the first client uses green `#005C2F`.
Dark mode is class based: `.dark` on `<html>`, page background `#111827` (Tailwind `gray-900`).

- **SVG assets never hard-code a brand colour.** Patterns and dividers are single-colour masks
  (shapes filled `#000`); CSS paints them with `background-color: var(--color-primary-600)` (or another
  token) through `mask-image`. Buttons and loaders use `currentColor` and CSS custom properties.
- **Paintings carry one swappable accent** so they suit any brand colour (D-027): a few small elements
  in medium blue `#2563EB`. Every new painting also gets a `-hijau` copy where only that accent becomes
  deep leaf green `#1F7A45` (setting *Warna aksen ilustrasi* = hijau, used by green schools).

### Painting palette: warm neutrals and the NU green family

| Role | Colours |
| --- | --- |
| Base neutrals | cream `#F7F1E3`, warm sand `#E9DCC3`, slate grey `#64748B`, soft charcoal `#334155` for lines |
| Green family (home colour of the art) | sage `#9CB79A`, soft olive `#7D9471`, leaf `#4F8A5B`, deep pesantren green `#2F5D46` — on plants, sarung, kerudung, batik, painted walls |
| Warm accents | muted terracotta `#C98B6B` (roof tiles, pots), soft gold `#D9B45A` (sunlight, brass, one batik line) |
| Swappable accent (only one) | medium blue `#2563EB` on 1–3 small elements (a phone screen, a folder, a calendar block) |
| Uniform | white shirts, navy (biru dongker) `#1E2A4A` trousers and skirts, black peci |

Greens stay muted (no neon green). No large areas of saturated colour, no rainbow palettes. The gold is a
warm highlight, not a second accent.

## 4. Paintings (raster)

### Look: hand-painted storybook

- Medium: soft **gouache and watercolour**, visible gentle brush texture, slightly uneven painted edges,
  light paper grain inside the painted areas.
- People are **stylised**: simple rounded faces, small dot or almond eyes, a short curved line for the
  mouth, rosy cheeks, tiny or no nose stroke, no pores, wrinkles or hair strands; slightly chunky,
  friendly proportions; simplified hands.
- Soft, even morning light; gentle painted shadows; no hard highlights.
- **Never**: photorealism, semi-realistic portraits, 3D/CGI/clay rendering, glossy plastic shading, anime
  or manga, hard-edged vector clip-art, lens blur or depth-of-field effects.

### Cast (keep the same people across images)

Names are references for consistency only; never write them in an image. Skin tones are warm Indonesian
tones and differ from person to person.

| Name | Who | Look |
| --- | --- | --- |
| Aisyah | applicant girl, 12, Javanese | white kerudung, white school shirt, navy skirt, small sage-green backpack |
| Raka | applicant boy, 12, Sundanese | short black hair, black peci, white shirt, navy trousers |
| Nurul | applicant girl, 12, from Papua (Fakfak), darker brown skin | cream kerudung over curly hair at the hairline, white shirt, navy skirt, round glasses |
| Fajar | applicant boy, 12, Chinese-Indonesian Muslim family, lighter skin | straight black hair, black peci, white shirt, navy trousers |
| Daeng Arif | applicant boy, 12, Bugis | wavy hair, black peci, sarung in deep green check on pesantren scenes, white baju koko |
| Ibu Sari | mother | white kerudung, soft sand-coloured long tunic |
| Pak Hadi | father | short hair, black peci, batik-like shirt with an abstract sage and sand pattern |
| Mak Ijah | grandmother | dark green long kerudung, kain batik in brown and cream, walks upright with a gentle smile |
| Pak Rahmat | father, Minang | light grey peci, olive batik-like shirt, calm smile |
| Bu Nur | school staff (ustadzah) | white kerudung, navy cardigan over a cream blouse, lanyard with a blank card |
| Pak Budi | school staff (ustadz) | black peci, light batik-like shirt with an abstract pattern, glasses |
| Kiai Mansur | elder teacher | white peci, white baju koko, dark green sarung, short grey beard, kind face; shown with dignity, usually in the background |

Not every image shows everyone. Mix the cast so that, across a page, families from different regions
appear naturally.

### Rules for every painting

- **No text at all**: no letters, numbers, Arabic script, logos, watermarks, signatures, signboards with
  writing. Screens, papers, cards and boards show only abstract blocks and lines.
- **No school marks**: no badge on uniforms, no recognisable building, no flags.
- Respect the rules in section 2.
- Leave the empty area described in the brief really empty (the app puts text next to the image, never on it).
- **Transparent** means real alpha with the painting as an isolated vignette with soft painted edges —
  no paper texture or colour wash outside the subject, no drawn checkerboard. **White** means `#FFFFFF`.
- **Reads on light and dark pages (D-083).** The app shows every image as delivered: no white plate, card or
  tinted frame behind it in dark mode. So every new painting (briefs 036 onwards) is transparent and works on
  `#FFFFFF` and on `#111827` alike: a soft painted outline in soft charcoal around every shape, outer edges
  mid-toned (neither near-white nor near-black), no glow, halo or light rim. The prompt adds the block in §10.

### Variants

**From brief 036 on (D-083): `light` and `hijau` only.** New paintings read on both page colours (see "Rules
for every painting"), so they get **no `-dark` and no `-dark-hijau` copy**; the brief's `variants:` line says
`light, hijau`. The `-hijau` copy stays, because green schools need the green accent.

Briefs 001–035 keep the variants they list. For those, unless the brief says otherwise:

| Variant | File suffix | How |
| --- | --- | --- |
| Light (default accent) | `<name>` | the painting |
| Dark | `<name>-dark` | same painting on real transparency; outlines and light areas lifted slightly; a faint light rim around the subject so it reads on `#111827`. Never invert colours |
| Green accent | `<name>-hijau` | edit of the light image: only the blue accent elements become leaf green `#1F7A45` (one tiny highlight may be soft yellow `#F2C94C`) |
| Green accent, dark | `<name>-dark-hijau` | the dark variant with the same green edit |

The app picks the variant itself (`illustration()` in `app/Helpers/ui_helper.php`): it looks for
`<name>-<accent>.webp` and `<name>-dark(-<accent>).webp` next to `<name>.webp`; when no `-dark` file exists it
shows the light (or `-hijau`) file on dark pages too, which is what D-083 relies on.

### Painted icons and step pictures (areas `ikon` and `langkah`)

Small paintings used like icons (D-082): home information cards, contact cards, the art buttons (049) and the
"Cara mendaftar" steps. The app floats them with a small three.js tilt (`resources/js/ikon3d.js`).

- One subject (an object, or an object with one small companion the brief names); **no people, hands or
  faces**; no text; no brand or app logos.
- Gentle three-quarter view from slightly above, light from the upper left, the same across a set.
- **No ground, table or cast shadow** (the icon floats); at most a very faint contact shadow in step pictures.
- Simple, bold shapes: icons must read at 48 px, step pictures at 128 px.
- No circle, tile or shape behind the object (D-070: icons without backgrounds).

### Sizes and budgets

Generate at the generator's native shape. The **installed** file is trimmed and scaled:

1. **Trim** to the painted subject: the bounding box of pixels with alpha > 8 (transparent images) or that
   are not near-white (white images), plus a 2 % margin. The empty area of a hero is removed here — the page
   layout already places the text beside the picture.
2. **Use one crop box for all variants** of a painting (light, dark, hijau, dark-hijau), taken as the union
   of their boxes, so the variants swap without a jump.
3. **Scale down** so the longest side fits the limit below (Lanczos); never scale up.
4. Save as WebP, quality 80–85, alpha kept.

| Usage | Generate | Installed file (`public/assets/img/…`) | Longest side | Budget per file |
| --- | --- | --- | --- | --- |
| Public hero | landscape 1536×1024 | `hero/<name>.webp` | ≤ 1100 px | ≤ 220 KB |
| Section illustration (beside a page heading) | square 1024×1024 | `section/<name>.webp` | ≤ 800 px | ≤ 140 KB |
| Guide cover | landscape 1536×1024 | `guide/<name>.webp` | ≤ 1000 px | ≤ 180 KB |
| Empty / success / error state | square 1024×1024 | `state/<name>.webp` | ≤ 480 px | ≤ 70 KB |
| Painted icon | square 1024×1024 | `ikon/<name>.webp` | ≤ 512 px | ≤ 60 KB |
| Step picture ("Cara mendaftar") | square 1024×1024 | `langkah/<name>.webp` | ≤ 640 px | ≤ 90 KB |
| Share image (link previews) | landscape 1536×1024 | `share/<name>.webp` and `.jpg` | exactly 1200×630, centre crop, no trim | ≤ 180 KB |

## 5. Vector assets (SVG)

Patterns, dividers, button layers and loaders are drawn by hand in SVG code (no tracing of raster
images, no embedded bitmaps).

- Inspiration: Islamic geometry (eight-point star *khatam*, interlaced octagons, arches) and Indonesian
  textile and craft motifs: **kawung**, **tumpal** (the triangle border of batik and songket), **ceplok**
  grids, **mega mendung** clouds, bamboo **anyaman** weaving, banana and frangipani leaves.
- Avoid **parang** and other motifs that were reserved for royal courts, the NU emblem, nine-star
  arrangements, mosque/dome silhouettes, and anything in section 2.
- Quiet: low contrast, open spacing, rounded joins. A pattern is texture, not a picture.
- Technical: `viewBox` required; no `<text>`, `<script>`, `<foreignObject>`, `<image>`, external `href`,
  fonts, filters that blur large areas, or inline event handlers; whole-pixel or one-decimal coordinates;
  minified by hand or with `svgo` (default preset, keep `viewBox`).
- Colour: masks use only `#000` fills on transparency (CSS colours them). Inline SVGs (loaders) use
  `currentColor`.
- Size: pattern tile ≤ 4 KB, divider ≤ 3 KB, button layer ≤ 6 KB, loader ≤ 4 KB.
- Light and dark: one SVG serves both; the brief's CSS sets a different colour token or opacity under `.dark`.

## 6. Visual rules for everything (D-070)

These apply to every asset and to every demo or CSS file Codex delivers:

- **No eyebrows**: no small uppercase or letter-spaced label above a heading.
- **No short decorative lines**: no accent bar or short rule above or below a heading. Dividers in this
  set span the full width of a section and are shapes, not thin lines.
- **No cards with a coloured top or side border**: cards use a neutral full border and/or a soft shadow.
- **Icons without backgrounds**: Lucide icons in `currentColor` sit directly next to text; never put a
  tinted circle or square behind an icon, and never paint icon tiles.
- **Motion**: welcome, but subtle (≤ 400 ms for interactions, slow loops ≥ 1.2 s), no flashing, no layout
  shift, and **everything stops under `prefers-reduced-motion: reduce`**.
- **Mobile and desktop**: check at 360 px and 1280 px; touch targets at least 44×44 px.

## 7. Base style block for painting prompts

Every painting prompt starts with this block, unchanged.

```
Hand-painted storybook illustration in soft gouache and watercolour, visible gentle brush texture,
slightly uneven painted edges, light paper grain inside the painted areas. Stylised friendly characters
with simple rounded faces, small dot eyes, rosy cheeks and simplified hands, like a modern Indonesian
children's educational book. Not photorealistic, not semi-realistic, not 3D, not CGI, no glossy shading,
no anime. Warm muted palette: cream, warm sand, slate grey, muted sage and leaf greens, a deep pesantren
green, soft terracotta and a touch of soft gold, with ONE small accent in medium blue #2563EB. Soft even
morning light. Modest Indonesian Muslim school life: girls in kerudung, boys in black peci. Absolutely no
text, letters, numbers, Arabic script, calligraphy, logos, emblems, flags, signatures or watermarks anywhere.
```

## 8. Dark variant block

Only for briefs 001–035 that list a `dark` variant. New briefs use §10 instead (D-083).

```
Make the dark-mode version of exactly this illustration: same painting, composition, characters and
colours, on a real transparent background (remove any white or paper background). Lift the outlines and
light areas slightly and add a faint light rim around the subject so it reads well on a very dark navy
page (#111827). Do not invert the colours. Still no text, letters or Arabic script anywhere.
```

## 9. Green accent block

```
Edit this exact illustration. Keep everything identical — composition, characters, faces, poses, brush
texture, colours and the background (keep real transparency if it is transparent). Change ONLY the small
medium-blue (#2563EB) accent elements into a deep leaf green (#1F7A45) with the same painted gouache
texture and shading; at most one tiny highlight may become soft yellow (#F2C94C). Do not recolour the navy
uniforms, plants, skin, kerudung or sarung. Do not add anything. No text, letters, logos or symbols.
```

## 10. Light-and-dark block (briefs 036 onwards)

Every new painting prompt has this block right after the base style block (§7), unchanged. It replaces the
separate dark variant (D-083).

```
Real transparent background (true alpha): no white, paper or colour background, no glow, no halo, no
drawn checkerboard. The painting must read on a white page and on a very dark navy page (#111827) alike:
give every shape a soft painted outline in soft charcoal, keep the outer edges mid-toned (neither
near-white nor near-black) and do not add a light rim.
```
