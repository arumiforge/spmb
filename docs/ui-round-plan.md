# UI round plan (November 2026)

The user's UI and UX requests of 2026-11-23, recorded as D-080…D-088 in `decisions.md`. Run with the
protocol in `coordination.md`: the coordinator session's branch **`nty/vigilant-wozniak-lvnhku`** is the
integration branch; each worker branches from it (`feat/ui-<package>`), touches only the files of its
package and opens one PR into it. The coordinator merges and finally opens one PR into `main`; the user
merges `main`.

## Foundation (coordinator, already on the integration branch)

- Sign-in with email or username (`App\Controllers\Auth\LoginController`, D-080) + tests.
- Public layout: small top bar (social links, staff sign-in), right-hand drawer menu, mobile bottom bar
  (`partials/bottom_nav`, `public_nav()`, `public_bottom_nav()`), footer with contact and "Developed by : Mr. F".
- Admin layout: mobile bottom bar with the raised "Cari" (quick-search `<dialog>`), footer credit.
- Settings: `School.motto`, `mapEmbed`, `social*`, `heroSlide1Path…6Path` (`BrandingAssets::HERO_SLIDES`),
  `map_embed_url()`, `school_social_links()`, `whatsapp_url()`, `social_icon()` (Simple Icons), `art_icon()`.
- Home page: slideshow (`[data-slide-hero]`), `tombol-seni` art buttons, step and info cards with art slots,
  location and contact with the map.
- JS: `resources/js/ui.js` (dialogs, folds `[data-lipat]`, `[data-lipat-bagian]`, table of contents
  `[data-daftar-isi]`, slideshow, three.js loader), `resources/js/tanggal.js` (date picker for every
  `input[type=date]`), `resources/js/ikon3d.js` (three.js, separate bundle).
- CSS: `resources/css/komponen.css` (bottom bar, art buttons, slideshow, folds, contents, date picker, map,
  `.form-lebar` for full-width admin forms).
- Empty states take `'image' => 'state/<unique>'` with `'fallback'`; every condition already names its own
  painting (see list below), shown as soon as Codex delivers it.
- Brand images cached for a year with ETag (no logo blink on reload); `width`/`height` on logos.
- Dependencies added: `three` (MIT), `simple-icons` (CC0, dev), `svgo` (MIT, dev); `THIRD_PARTY_NOTICES.md` updated.

## Work packages

| Package | Branch | Owns (may edit) | Must not edit |
| --- | --- | --- | --- |
| **A — Codex briefs** | `feat/ui-codex` | `ai/` (new briefs, `README.md` queue, `style-guide.md`, `CODEX.md`, `tools/check_assets.py`) | anything outside `ai/` |
| **B — Minified assets** | `feat/ui-minify` | `scripts/` (new script), `package.json` scripts only, `resources/img/` (new: SVG originals), `public/assets/img/**/*.svg` (minified output), `docker/` build stage if it runs the build, `.gitignore`, `docs/deployment.md` build notes | views, CSS, JS behaviour, `ai/` |
| **C — Dashboard pass** | `feat/ui-admin` | `app/Views/admin/`, `app/Modules/*/Views/` **admin** views (not `public/`, not Situs public pages), `app/Modules/*/Language/` keys for those views, `tests/feature/*Admin*Test.php`, new `tests/feature/AdminLayoutTest.php` | layouts, partials, `resources/`, `ai/`, shared docs |
| **D — Public pages pass** | `feat/ui-publik` | `app/Modules/Situs/Views/{persyaratan,jadwal,tanya_jawab}.php`, `app/Modules/Pendaftaran/Views/public/`, `app/Modules/Kartu/Views/check.php`, `app/Modules/Panduan/Views/`, `app/Views/errors/friendly.php`, their language keys, `tests/feature/{HomeTest,SitusTest,SitusTanyaJawabTest,PengaturanTest,BrandingTest,PanduanTest}.php` | layouts, partials, `resources/`, `ai/`, admin views |

Shared files (layouts, partials, helpers, `resources/css`, `resources/js`, configs, migrations, `docs/*` except
the files listed above) belong to the coordinator. A worker that needs a change there writes it under
"Needs from coordinator" in its PR description or messages the coordinator session.

### A — Codex briefs (D-082, D-083)

Write one brief per asset in `ai/prompts/` (next free number is 036), add queue rows in `ai/README.md`, and
group them into batches. Rules: `ai/style-guide.md` (identity, respect rules); new art is delivered on real
transparency that reads on light **and** dark pages, **no `-dark` variant** (D-083), `-hijau` variant kept.
Update `style-guide.md` (dark variant rule, new area `ikon` with longest side ≤ 512 px and ≤ 60 KB, area
`langkah` ≤ 640 px / ≤ 90 KB) and `tools/check_assets.py` (`AREAS`) to match. File names below are fixed:
the views already load them.

| Batch | Files (`public/assets/img/…`) | Used by |
| --- | --- | --- |
| `ikon-beranda` | `ikon/ikon-persyaratan`, `ikon/ikon-jadwal`, `ikon/ikon-cek-status`, `ikon/ikon-tanya-jawab`, `ikon/ikon-panduan`, `ikon/ikon-daftar`, `ikon/ikon-alamat`, `ikon/ikon-telepon`, `ikon/ikon-pesan`, `ikon/ikon-email` | home info cards, contact cards, art buttons (`art_icon()`), 3D icons; painted single objects, no people, no text, no brand marks |
| `langkah` | `langkah/langkah-1` (berkas in a folder), `langkah/langkah-2` (form on a phone), `langkah/langkah-3` (registration card) | "Cara mendaftar" steps |
| `tombol-seni` | SVG + CSS frame for `.tombol-seni` (organic leaf-like shape with a tumpal/kawung edge, states hover/focus/active/disabled, `currentColor`/custom properties, see `resources/css/komponen.css`) and a round art badge for the raised bottom-bar button (`.nav-bawah-tengah-bulat`, painted `ikon/ikon-daftar` sits inside) | home buttons, bottom bar |
| `slide-beranda` | `hero/hero-slide-belajar`, `hero/hero-slide-keluarga` (and 019 `hero/hero-beranda-santri` already queued); landscape paintings whose edges fade softly (the page also masks them) | hero slideshow defaults (`App.altHeroSlides`) |
| `kosong-*` | one painting per empty condition: `state/kosong-jenis-berkas`, `kosong-antrean-berkas`, `kosong-sekolah-asal`, `kosong-sekolah-lain`, `kosong-penjadwalan`, `kosong-riwayat-jadwal`, `kosong-sesi`, `kosong-ruang`, `kosong-peserta`, `kosong-peserta-sesi`, `kosong-sesi-hari-ini`, `kosong-gelombang`, `kosong-pendaftar`, `kosong-tanya-jawab`, `kosong-jadwal-pendaftaran`, `kosong-persyaratan`, `kartu-cari`, `kartu-tidak-ditemukan`, `kosong-pengguna`, `kosong-dasbor` (plus 028–034 already queued) | empty states (grep `'fallback'` in `app/`) |

Each brief says the condition in one plain sentence and gives a scene that differs clearly from the others
(no two empty states may look alike). Alt text in Indonesian in the brief; the coordinator adds it to the views.

### B — Minified assets (D-086)

SVG originals move to `resources/img/<area>/*.svg` (unminified, as Codex delivered them); `npm run build`
gains `build:img` (a `scripts/minify-assets.mjs` with svgo, `multipass`, keep `viewBox`, no ID cleanup that
breaks masks) that writes minified copies to `public/assets/img/<area>/`. WebP files stay where they are
(already compressed; masters in `ai/generated/`). Check that every page still shows patterns, dividers,
loaders and the registration button (screenshots 360/1280), that the Docker image build runs `build:img`, and
document the flow in `docs/deployment.md` → build notes. Update `.claude/skills/spmb-ai-image` step 4 text
through "Needs from coordinator" (the skill is shared).

### C — Dashboard pass (D-081, full width)

Go through every dashboard page at 360, 768, 1280 and 1920 px (light and dark) with the demo data and fix
what the full-width layout and the bottom bar expose: wasted empty space (use grids, two-column card layouts
from `xl`, tables at full width), content hidden behind the bottom bar, tables that overflow, filters that
wrap badly, date fields (the new picker) in filter rows, long help texts that should fold (`data-lipat`).
Add `tests/feature/AdminLayoutTest.php`: bottom bar items per role (Admin Sekolah, Panitia), the "Cari"
button only with `pendaftar.view`, quick search goes to `admin/pendaftar?q=…`, footer credit present.

### D — Public pages pass (D-081, D-085, D-088)

Persyaratan, jadwal, tanya jawab, cek status, daftar (form), selesai, tutup, kartu, panduan, error pages at
360 and 1280 px (light/dark): long texts fold with `data-lipat` where they are long, topic chips on
`/tanya-jawab` become a `data-daftar-isi` contents with the active topic highlighted, no plate behind images
(D-083), date fields use the picker (birth date opens on a year ~12 years back), nothing hidden behind the
bottom bar. Tests: home page shows slogan, map iframe only for a valid embed URL, social links only when set,
uploaded slides replace the defaults; Identitas Sekolah saves motto, map (from the full `<iframe>` code; rejects
other hosts), social URLs and slide uploads; `/aset/<slug>` answers 304 to a matching `If-None-Match`.

## Done when

All packages merged, full suite green, `composer lint` clean, `npm run build` OK, screenshots checked, docs
updated (`CHANGELOG.md`, `architecture.md`, `spmb-ui` skill, `progress.md`), one PR into `main`.
