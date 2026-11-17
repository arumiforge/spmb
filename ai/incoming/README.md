# ai/incoming — delivery folder for ChatGPT

ChatGPT puts the generated illustrations here, and only here, through a pull request
(see [../CHATGPT.md](../CHATGPT.md)). Nothing in this folder is used by the app.

- File names come from the `file` field of each brief in `../prompts/` (e.g. `hero-beranda.png`);
  dark variants end in `-dark` (e.g. `masuk-dasbor-dark.png`).
- PNG, or WebP when a PNG is larger than 2 MB. Transparent images keep their transparency.
- Claude reviews the files in the pull request, converts and installs the approved ones into
  `public/assets/img/`, wires them into the views, then deletes them from this folder before the
  pull request is squash-merged, so the large originals never enter the history of `main` (D-028).
