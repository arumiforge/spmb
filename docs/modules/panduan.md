# Module: Panduan

Public Indonesian guide pages per role. Core module.

| Route | Purpose |
| --- | --- |
| `GET /panduan` | Role cards (public, staff; the signed-in user's roles are highlighted) |
| `GET /panduan/{peran}` | Guide for one role, with table of contents |

Roles and slugs: `Config/Roles.php` (`calon-murid`, `admin-sekolah`, `panitia`, `super-admin`; D-063).
Old slugs (`panitia-pendaftaran`, `petugas-tes`, `petugas-daftar-ulang`, `petugas-seragam` → `panitia`,
`kepala-sekolah` → `admin-sekolah`) redirect with 301.
`Modules\Panduan\Libraries\GuideBuilder` concatenates `Guide/<slug>.md` and then `Guide/<slug>/*.md`
(file name order) from every enabled module (Panduan first, then by module name); staff guides also get `Guide/semua-petugas.md`, the Super Admin
guide also gets `admin-sekolah.md`. Markdown is rendered with league/commonmark (raw HTML stripped);
placeholders `{nama_sekolah}`, `{url_masuk}`, `{url_situs}` are replaced. `## ` headings form the table
of contents; blockquotes render as tip boxes. Writing rules: `.claude/skills/spmb-copy`.
