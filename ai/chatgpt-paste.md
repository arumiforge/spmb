# ChatGPT copy-paste prompts (images 001–012)

Ready-to-paste messages for ChatGPT image generation. They contain the same scenes as
`prompts/001`–`012`, shortened so they can be pasted one by one into a single chat.

## How to use

1. Open a **new ChatGPT chat** and paste **Message 0** (the style rules) once.
2. Paste one image message at a time. Wait for the image, check it (see the checklist), then download it.
3. Save each image in `ai/output/` under the file name shown in its heading, e.g. `ai/output/hero-beranda.png`.
4. For images marked **+ dark**, send the dark follow-up message right after the light image is accepted
   and save that result as `<name>-dark.png`.
5. If the style starts drifting after many images, open a new chat, paste Message 0 again and continue.
6. Tell Claude when the files are in `ai/output/`; it resizes them to WebP and wires them into the pages.

Checklist before saving: no letters, numbers or logos anywhere (also not on screens, signs, papers);
the background is really transparent where asked (not a drawn grey checkerboard); only one blue accent colour;
faces and hands look natural. If something is wrong, reply e.g. *"Same image, but remove the text on the sign"*.

Sizes: ChatGPT offers square, landscape and portrait. Use the shape named in each message; Claude crops and
resizes to the final size, so exact pixels do not matter.

---

## Message 0 — style rules (paste once per chat)

```
I will ask you for a series of illustrations for a school admission website. Use exactly the same style for every image in this chat:

- Flat vector illustration, modern and friendly, soft rounded shapes, subtle gradients, consistent line weight. No photorealism, no 3D render.
- Limited neutral palette: slate grey and soft white, with ONE accent colour, medium blue #2563EB, used sparingly (it will be recoloured for other schools).
- Characters: Indonesian junior-high students aged 12–13 and their parents, diverse skin tones, some girls wearing a white hijab. Uniform: white shirt with dark navy trousers or skirt. No school badge or logo.
- Absolutely no text, letters, numbers, logos, watermarks or brand names anywhere, including on screens, papers, signs and cards. Screens and documents show only abstract shapes.
- Calm, welcoming mood. Generous empty space.
- Background: as stated in each request. "Transparent" means a real transparent PNG background, not a drawn checkerboard.

Do not generate anything yet. Reply only "Siap" and wait for my first request.
```

---

## 001 — `hero-beranda.png` (public home page)

```
Image 001, landscape (wide), plain white background.
A parent and their child (a 12-year-old girl in a white hijab) standing happily in front of an abstract, friendly junior high school building with trees. The child holds a smartphone showing an abstract form (shapes only). Two other students walk in the background. Keep the LEFT 45% of the image mostly empty and light, because a headline will be placed there. Same style rules as above, no text.
```

## 002 — `daftar-online.png` (online registration page)

```
Image 002, portrait (tall), transparent background.
A parent sitting at a table with a laptop, a 12-year-old boy beside them pointing at the screen. Around them float abstract form cards, a document icon with a check mark and an upload arrow. Vertical composition with the characters in the lower two thirds. Same style rules, no text.
```

## 003 — `masuk-dasbor.png` (staff sign-in page) + dark

```
Image 003, portrait (tall), transparent background.
A friendly adult school staff member (woman in a hijab, smart casual clothes) holding a tablet that shows abstract charts. A shield with a check mark and a small key float nearby to suggest security. Calm and professional, vertical composition. Same style rules, no text.
```

Dark follow-up → save as `masuk-dasbor-dark.png`:

```
Now make a dark-mode version of exactly the same illustration: same composition and characters, transparent background, outlines and light areas lightened so it reads well on a very dark navy background (#111827). Keep the single blue accent. No text.
```

## 004 — `kosong-data.png` (empty lists) + dark

```
Image 004, square, transparent background.
An open, empty folder with a few soft paper sheets floating out of it and a small plant beside it. Minimal and calm, centred, lots of empty space around. No people. Same style rules, no text.
```

Dark follow-up → save as `kosong-data-dark.png`: paste the dark follow-up message from 003.

## 005 — `kosong-pencarian.png` (no search results) + dark

```
Image 005, square, transparent background.
A magnifying glass looking at an empty sheet of paper, with a small abstract curl-shaped cloud nearby (not a question mark or any character). Minimal and centred. No people. Same style rules, no text.
```

Dark follow-up → save as `kosong-pencarian-dark.png`: paste the dark follow-up message from 003.

## 006 — `sukses-pendaftaran.png` (registration success)

```
Image 006, square, transparent background.
A student (girl in a white hijab) and a parent celebrating gently. The student holds up an abstract card with a QR-like square pattern (not a readable code). Small confetti shapes around them, centred. Same style rules, no text.
```

## 007 — `tidak-ditemukan.png` (404 page) + dark

```
Image 007, square, transparent background.
A 12-year-old boy holding a paper map, looking around curiously at a fork in a simple path with two blank signposts (no writing on them). Friendly and light-hearted, centred. Same style rules, no text.
```

Dark follow-up → save as `tidak-ditemukan-dark.png`: paste the dark follow-up message from 003.

## 008 — `gangguan-sistem.png` (500 error page) + dark

```
Image 008, square, transparent background.
A friendly adult school staff member (woman in a white hijab and a simple navy cardigan) calmly tightening a bolt on a large, simple rounded gear next to a laptop on a desk, with a small toolbox on the floor. Reassuring, calm mood: nothing broken, no fire or smoke. Centred with empty space around. Same style rules, no text.
```

Dark follow-up → save as `gangguan-sistem-dark.png`: paste the dark follow-up message from 003.

## 009 — `kosong-riwayat.png` (empty change history) + dark

```
Image 009, square, transparent background.
An open, clean notebook with blank lined pages lying next to a simple wall clock (no numbers on the face) and a pencil, with a few soft decorative dots around. Calm and tidy, no people, centred with generous empty space. Same style rules, no text.
```

Dark follow-up → save as `kosong-riwayat-dark.png`: paste the dark follow-up message from 003.

## 010 — `fitur-tambahan.png` (features page, nothing installed yet) + dark

```
Image 010, square, transparent background.
Four large rounded building blocks arranged in a 2x2 grid. Three are slate grey; a friendly hand is placing the fourth block, which has a soft blue accent, into the last slot. No symbols on the blocks, no other people. Centred. Same style rules, no text.
```

Dark follow-up → save as `fitur-tambahan-dark.png`: paste the dark follow-up message from 003.

## 011 — `panduan-petugas.png` (staff guide cover)

```
Image 011, landscape (wide), plain white background.
Two adult school staff members sitting at a shared desk with a laptop and an open booklet: a man in a batik shirt and a woman in a white hijab and navy blazer. The woman points at the booklet while the man nods. Keep the LEFT third of the image empty, because the page title will be placed there. Batik pattern must be abstract (no letters). Same style rules, no text.
```

## 012 — `panduan-calon-murid.png` (applicant guide cover)

```
Image 012, landscape (wide), plain white background.
A mother in a white hijab and her 12-year-old daughter (white hijab, school uniform) sitting together on a sofa looking at a smartphone (blank screen, no interface details). The father stands behind them, smiling. Warm but minimal home setting. Keep the LEFT third of the image empty for the page title. Same style rules, no text.
```
