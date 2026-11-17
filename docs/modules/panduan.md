# Module: Panduan

Public Indonesian guide pages per role. Core module.

| Route | Purpose |
| --- | --- |
| `GET /panduan` | Role cards (public, staff; the signed-in user's roles are highlighted) |
| `GET /panduan/{peran}` | Guide for one role, with table of contents |

Roles and slugs: `Config/Roles.php` (`calon-murid`, `admin-sekolah`, `kepala-sekolah`,
`panitia-pendaftaran`, `petugas-tes`, `petugas-daftar-ulang`, `petugas-seragam`, `super-admin`).
`Modules\Panduan\Libraries\GuideBuilder` concatenates `Guide/<slug>.md` from every enabled module
(Panduan first, then by module name); staff guides also get `Guide/semua-petugas.md`, the Super Admin
guide also gets `admin-sekolah.md`. Markdown is rendered with league/commonmark (raw HTML stripped);
placeholders `{nama_sekolah}`, `{url_masuk}`, `{url_situs}` are replaced. `## ` headings form the table
of contents; blockquotes render as tip boxes. Writing rules: `.claude/skills/spmb-copy`.
