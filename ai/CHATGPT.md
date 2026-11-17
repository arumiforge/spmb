# Instructions for ChatGPT: generate the SPMB illustrations

You are helping Arumi Studios create the illustrations for SPMB, a school admission web app for
Indonesian junior high schools. Everything you need is in this repository's `ai/` folder. Follow
these steps exactly.

## 1. Read first

1. `ai/style-guide.md`: the painted storybook style, palette, the recurring cast of characters,
   the rules for every image and the dark-variant rule. This style is mandatory for every image.
2. `ai/README.md`: the queue table. Work only on rows with status `requested`, in ID order.
3. For each image, its file `ai/prompts/<ID>-<name>.md`: purpose, composition, characters,
   setting, colours, things to avoid, the ready prompt and, for some, a dark variant prompt.

## 2. For each image, in ID order

1. Tell the user in one short line (in Indonesian) which image you are making: ID, file name and where
   it is used.
2. Generate the image from the **Prompt** section of its file, using the shape it names
   (square 1024×1024, landscape 1536×1024, portrait 1024×1536) and its background
   (`transparent` = real transparent PNG with alpha; `white` = plain white). Respect the
   Composition and Avoid sections too; they are part of the brief.
3. Check the result yourself against this list before showing it as done:
   - painted gouache/watercolour storybook look; people stylised, not realistic, not 3D;
   - no text, letters, numbers, logos or signatures anywhere (screens, papers, signs, cards, patterns);
   - the empty area for UI text is really empty (heroes and guide covers);
   - only one small blue accent (#2563EB), otherwise the muted palette;
   - the transparent background is real (no drawn checkerboard, no paper texture outside the subject);
   - characters match the cast description (hijab, uniform colours, clothing).
   If any check fails, regenerate once with a short correction and say what you fixed.
4. If the file has a **Dark variant prompt**, generate the dark version right after the light one
   is accepted, from the same image.
5. Tell the user the exact file names to save: `<name>.png` and, if made, `<name>-dark.png`
   (names are in each file's front matter, field `file`).
6. Ask the user: "Lanjut ke gambar berikutnya?" and wait. If the user asks for changes, revise the
   same image until they accept it, keeping the style.

Keep characters consistent between images: the same Aisyah, Raka, Ibu Sari, Pak Hadi, Bu Nur and
Pak Budi every time they appear.

## 3. Talking to the user

- Write to the user in friendly Indonesian, short messages.
- Never invent new images or change the scene beyond the brief; if something in a brief is unclear,
  choose the calmer, simpler option and mention it.
- You cannot change the repository. The user downloads each image and gives it to Claude
  (the developer agent), which resizes it and places it in the app.

## 4. When all images are done

Give the user a summary table: ID, file name(s), and any notes (e.g. "regenerated once: removed text on sign").
