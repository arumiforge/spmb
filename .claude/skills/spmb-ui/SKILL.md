---
name: spmb-ui
description: Build views for SPMB with Tailwind v4 + Flowbite — full-width responsive admin layout, public site layout, breadcrumbs, tables, forms, empty states, white-label theming. Use for any page, component or layout work.
---

# UI conventions

## Layouts

- `app/Views/layouts/admin.php` — **full width** (no max-width container on the
  content area), collapsible sidebar (drawer on mobile), top navbar with user
  menu, breadcrumb bar under the navbar, content slot, toast area.
- `app/Views/layouts/public.php` — public site for parents/students: small top bar (social links from
  `school_social_links()`, staff sign-in), sticky header (menu inline from `md`, drawer from the right
  below `md`), footer with contact and "Developed by : Mr. F", large tap targets, mobile first.
- **Mobile bottom bar on every layout (D-081)**: `partials/bottom_nav` with five items, the middle one raised
  (public: Beranda · Persyaratan · **Daftar** · Jadwal · Cek Status from `public_bottom_nav()`; dashboard:
  Dasbor · Data Pendaftar · **Cari** · Periksa Berkas · Menu, filtered by permission). Layouts add
  `pb-24 md:pb-0` to `<body>`; never put fixed content at the bottom of the screen on phones.
- `app/Views/layouts/auth.php` — login, 2FA, password reset.
- Shared partials in `app/Views/partials/`: `breadcrumbs`, `flash`, `empty_state`,
  `form_field`, `status_badge`, `pagination`, `school_brand`, `head`. Each takes **one array**
  (`view('partials/form_field', ['field' => [...]])`, `['badge' => ...]`, `['empty' => ...]`)
  because CI shares view data between renders; never rely on loose variables in a partial.
- Admin pages pass `title`, optional `subtitle`, `breadcrumbs`; buttons next to the title go in
  section `actions`. Destructive forms use `data-confirm="..."`; copy buttons `data-copy="..."`.
- Illustrations: `illustration('state/kosong-data', lang('App.altNoData'), 'h-32 w-auto')` prints the painted
  WebP (and an older `-dark` variant if one exists). **No plate behind images** (no `bg-white` box, D-083).
  Empty states pass `'image' => 'state/<unique>'` plus `'fallback'` to `partials/empty_state`: every
  different empty condition has its own painting (D-082). New images are requested through `spmb-ai-image`.
- Painted icons: `art_icon('ikon/ikon-jadwal', 'calendar-days', 'h-20 w-20')` shows the Codex icon inside a
  three.js holder (`[data-ikon-3d]`, floats and tilts towards the pointer within `[data-ikon-3d-area]`) or the
  Lucide fallback until the painting exists. A page that uses them sets `data-ikon-3d-src` (see `home.php`).
  Buttons with a unique function on the home page use `.tombol-seni` with an art icon, not a plain box.
- Long text: wrap in `<div class="lipat" data-lipat data-lipat-tinggi="12rem"><div class="lipat-isi">…</div></div>`
  ("Lihat selengkapnya"); `data-lipat-bagian` on rendered Markdown folds each `h2` section. A contents list
  with `data-daftar-isi` highlights the section in view (D-088).
- Dates: every `input[type=date]` becomes the Indonesian date picker (`resources/js/tanggal.js`); the name
  carries the ISO value. Birth dates (`name` contains `birth`/`lahir` or `data-tanggal="lahir"`) open years back.
  Opt out with `data-tanggal="asli"`.
- Icons: `icon('users', 'h-5 w-5')` from `ui_helper` — Lucide icons (ISC, D-070). Pass a Lucide name
  (`circle-check`) or an alias (`check`). Only the icons listed in `resources/icons.json` exist: add the name
  there, run `npm run icons` (writes `app/Helpers/lucide_icons.php`, never edit it by hand) and commit both.
  `icon_exists()` tells whether a name is known. Dates: `tanggal_indo()`.
- Motion: `motion-rise` (soft entrance; stagger with `style="--rise-delay: 80ms"`), `motion-lift` (hover lift on
  cards and tiles), `motion-icon` (icon inside a `motion-lift`). All are off under `prefers-reduced-motion` and
  change only opacity/transform, so nothing shifts.
- After adding classes in views run `npm run build` (Tailwind scans `app/Views`, `app/Modules`).

## Visual rules (D-070, strict)

- **No eyebrows**: no small uppercase or letter-spaced label above a heading
  (`uppercase tracking-*` over an `h1`/`h2`). Put that information in the heading,
  the subtitle or a normal sentence-case line under the heading.
- **No decorative short lines**: no accent bar or short rule above or below a heading or
  section (`h-1 w-12 bg-primary-*` and similar). Progress bars and meters are data, not decoration.
- **No accent-border cards**: no card with a coloured top, left or right border
  (`border-t-4 border-primary-*`, `border-l-4`, `border-r-*`). Cards use a neutral
  full border and/or a soft shadow. Alerts use a tinted background, not a side stripe.
- **Icons without backgrounds**: Lucide only, through `icon()`, in `currentColor`, directly
  next to the text. No tinted square or circle behind an icon (`rounded-lg bg-primary-50 p-2`
  around an icon is not allowed). Size and colour carry the emphasis.
- **Rich is fine**: heavier pages are accepted. Use illustrations, patterns and animation
  (Codex-made assets, `spmb-ai-image`) freely, as long as every feature works on mobile and
  desktop, there is no layout shift, and `prefers-reduced-motion` turns motion off.
- **Identity (D-073)**: santri, Islamic, Nahdlatul Ulama, Indonesian, Bhinneka Tunggal Ika —
  carried by illustrations, patterns and colour from settings, never by hard-coded school names or logos.

## Charts (ApexCharts)

`resources/js/charts.js` applies the shared responsive defaults; a chart passes only its own options.
Text must never overlap, especially at 360 px:
- use `responsive` breakpoints: under 640 px turn data labels off, put the legend at the
  bottom, shorten category labels (`formatter` with an ellipsis, full text in the tooltip),
  rotate long x labels (`rotate: -45`, `hideOverlappingLabels: true`) or switch to horizontal bars;
- donut/pie: centre label short, legend below, no labels on slices under ~8%;
- give the chart a fixed minimum height so labels have room; check 360, 768 and 1280 px with screenshots.
- options that JSON cannot express go under `"spmb"` in `data-chart` (see the top of `charts.js`): `mask: 3` (tooltips show
  "< 3" for 1–2, D-111), `links` (one address per bar, for drill-down), `sequential` (light → dark brand steps for ordered
  series), `gap` (card-coloured line between stacked segments), `percentAxis`.

## Breadcrumbs (required on every page except the home page)

Controllers pass `$breadcrumbs = [['label' => 'Beranda', 'url' => '/admin'], ['label' => 'Data Pendaftar', 'url' => '/admin/pendaftar'], ['label' => 'Detail']]`.
Rules: first item is always the home of the area (`Beranda`/`Dasbor`); last item
is the current page and not a link; labels are plain Indonesian nouns, max ~3 words;
on mobile collapse middle items into "…". No home icon on the first item. Use Flowbite breadcrumb markup with `aria-label="Breadcrumb"`.

## Responsive

Mobile first. Test at 360, 768, 1280, 1920 px. Tables become stacked cards
under `md` or scroll horizontally inside their own container — never the page.
Admin content uses the full width on large screens (no `max-w-*` on admin pages). Forms with several cards
use `.form-lebar` (one column, two columns from `xl`; the last child, alerts and a single card span both).

## Public pages in three languages (D-109, `docs/i18n.md`)

- Public views run in Indonesian, English (`/en/…`) and Arabic (`/ar/…`, `dir="rtl"`): every text via
  `lang()`, links via `site_url()`/`url_to()` (they keep the prefix), assets via `asset_url()`/`base_url()`.
- Use logical utilities only (`ms-/me-/ps-/pe-/start-/end-/text-start/rounded-s-/border-e`), never
  `left/right/ml/mr/pl/pr/text-left`; for a directional transform add `rtl:` (e.g.
  `group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5`). `icon()` already flips arrows and chevrons.
- School-typed text: `localized($row, 'field')` / `setting_localized('School.key')`, wrapped in `<bdi>`;
  phone numbers in `dir="ltr"`. Check new public pages at `/ar/…` on a 390 px screen.

## White label / theming

- Colours come from settings as CSS variables (`--color-primary-*`) set in the
  layout `<head>`; Tailwind v4 `@theme` maps `primary` to those variables.
  Never hard-code a school colour or name in a view.
- Logo, favicon, school name, footer text from settings.
- Support dark mode via Flowbite's `dark:` classes. Pages start light; dark mode is opt-in through the toggle (D-078).

## Components

Prefer Flowbite components (open-source `flowbite` package). Flowbite Pro
templates in `flowbite/` (private repo, licensed) may be used as a markup
reference: copy the minimum, adapt to our partials, never copy whole pages or assets.
Charts: ApexCharts. Tables: server-side pagination + filters in the query string so
filtered views are shareable and exportable.

## States every screen must handle

Loading, empty (friendly illustration from `ai/` + one clear action), error
(plain message + what to do), success (toast), no-permission (explain, link back).

## Accessibility

Labels on every input, visible focus, contrast AA, status never by colour alone
(badge text + colour), buttons say the action (`Simpan Data`, not `Submit`).

Copy rules: see `spmb-copy`. Missing illustration: see `spmb-ai-image`.
