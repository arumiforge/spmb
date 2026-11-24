# Module: Panduan

Indonesian guide pages per role. Core module. Staff guides live inside the dashboard (D-097).

| Route | Purpose |
| --- | --- |
| `GET /panduan` | Public guides (without a Shield group). With one public guide (today) it redirects to that guide. |
| `GET /panduan/{peran}` | Public guide for one audience (`calon-murid`), with table of contents and in-page search. A staff slug redirects (301) to `/admin/panduan/{peran}`. |
| `GET /admin/panduan` | Staff guides the signed-in user may read; with one, it redirects to it. Super Admin sees all staff guides plus a link to the public guide. |
| `GET /admin/panduan/{peran}` | Staff guide in the admin layout. Own role only (Super Admin: every staff guide); another role's guide is 404. |

Roles and slugs: `Config/Roles.php` (`calon-murid`, `admin-sekolah`, `panitia`, `super-admin`; D-063); `group`
`null` means public. Old slugs (`panitia-pendaftaran`, `petugas-tes`, `petugas-daftar-ulang`, `petugas-seragam` →
`panitia`, `kepala-sekolah` → `admin-sekolah`) redirect with 301 to the dashboard address.
`Modules\Panduan\Libraries\GuideBuilder` concatenates `Guide/<slug>.md` and then `Guide/<slug>/*.md`
(file name order) from every enabled module (Panduan first, then by module name); staff guides also get `Guide/semua-petugas.md`, the Super Admin
guide also gets `admin-sekolah.md`. Markdown is rendered with league/commonmark (raw HTML stripped);
placeholders `{nama_sekolah}`, `{url_masuk}`, `{url_situs}` are replaced. `## ` headings form the table
of contents; blockquotes render as tip boxes. Both page types share `Views/_isi.php` (contents, other guides,
search box, body). The search box (`partials/cari_halaman`, `initPageSearch` in `resources/js/ui.js`) filters the
`##` sections while typing and marks the words found. Writing rules: `.claude/skills/spmb-copy`.
