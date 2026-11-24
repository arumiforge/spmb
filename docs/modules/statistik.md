# Statistik (applicant statistics)

Staff-only statistics about applicants for school planning (D-111): where applicants live and which
schools they come from, the parents' occupation and income, guardian, gender, religion, age, SD vs MI
and public vs private origin, online vs at school, registrations over time and document completeness,
plus cross-tabs of income per wave and per district. Only counts are shown, never names or other
personal data. Shown on the Fitur page as "Statistik Pendaftar"; requires Pendaftaran (switching
Pendaftaran off switches Statistik off too). Test-score analysis comes later, after the interview on
score entry.

## Routes

| URL | Permission | Content |
| --- | --- | --- |
| `GET /admin/statistik` | `laporan.view` | Filters, summary tiles, five groups of folding cards (below) |
| `GET /admin/statistik/unduh/{asal\|keluarga\|murid\|pendaftaran\|perbandingan}` | `laporan.export` | CSV of one group with the same filters and view switches; unknown group → 404 |

Sidebar: group **Laporan**, "Statistik", icon `chart-column`. No dashboard panel (the dashboard is
busy enough). Admin Sekolah and Super Admin have `laporan.*`; Panitia has neither permission.

## Filters and view switches (query string)

All links and forms are plain GET; the page works without JavaScript (charts aside).

| Key | Values | Default |
| --- | --- | --- |
| `tahun` | an academic year that has applicants or waves | `setting('Pendaftaran.academicYear')` |
| `gelombang` | a wave id of that year, or `tanpa` (no wave) | all waves |
| `status` | `aktif` (all but cancelled), `semua`, `registered`, `verified`, `cancelled` | `aktif` |
| `kab`, `kec` | location drill-down keys (below); `kec` only counts with `kab` | top level |
| `sekolah` | `kecamatan`, `kabupaten`, `nama` (origin schools grouped by the school's district, regency, or per school) | `kecamatan` |
| `waktu` | `hari`, `minggu` (weeks start on Monday) | per week when registrations span more than 60 days, else per day |
| `penghasilan` | `ayah`, `ibu` (whose income the cross-tabs use) | `ayah` |

Unknown values fall back to the default. Soft-deleted applicants never count. Default values are left
out of generated links (`StatistikFilter::query()`), and the filter form keeps the view switches.

## Cards

| Group (`id`) | Cards |
| --- | --- |
| Asal calon murid (`asal`) | Tempat tinggal (drill-down), Sekolah asal (switch), SD atau MI, Negeri atau swasta |
| Orang tua dan wali (`keluarga`) | Pekerjaan ayah, Pekerjaan ibu, Penghasilan ayah, Penghasilan ibu, Ada wali |
| Calon murid (`murid`) | Jenis kelamin, Agama, Usia saat mendaftar |
| Pendaftaran (`pendaftaran`) | Pendaftar dari waktu ke waktu (per day/week), Per gelombang, Cara mendaftar, Kelengkapan berkas |
| Perbandingan (`perbandingan`) | Penghasilan per gelombang, Penghasilan per kecamatan (8 districts with most applicants + "Kecamatan lain") |

Forms (dataviz skill): ranked horizontal bars in one brand hue for categories (top 15 + a grey
"Lainnya (n kelompok)" bar; "Belum diisi" is grey too, full list in the table); ordered categories
(income in the order of `Pendaftaran.incomeOptions`, age buckets "Di bawah 11 tahun" … "15 tahun atau
lebih") keep their order and are never folded; columns for registrations over time; small splits as a
table with a meter bar per row (no chart script); cross-tabs as 100% stacked horizontal bars with a
light → dark brand ramp (income is ordinal) and grey "Belum diisi", and a count table with row
percentages. Every chart has `role="img"` with a summary `aria-label` and its table under
"Lihat tabel" (the location table is open by default because it holds the drill-down links).

Layout: summary page rule of D-102 — cards two per row from `xl`, three from `2xl`; wide cards
(location, origin schools, timeline, cross-tabs) span the row. Group links at the top jump to each
group; "Buka semua / Tutup semua" (D-100).

## Location grouping

Grouped by the applicant's text columns `regency` → `district` → `village`, trimmed and
case-insensitive (`LOWER(TRIM(...))`). When `applicants` has the code columns of the national region
list (`regency_code`, `district_code`, `village_code`; D-111, added by the region list work) a row
with a code is grouped by its code, and rows without one keep the text grouping. The field list is
read once per request (`getFieldNames`), so the query works with and without those columns.

Group keys are opaque and used as `kab`/`kec` values: the code (e.g. `33.19`) or `t:` + the
normalised names down to that level (`t:kudus`, `t:kudus|jati`), so district keys never mix two
regencies. A key that no longer matches any applicant in the current filter is ignored (the page shows
the level above). Rows with an empty place name are "Belum diisi" and cannot be opened. Inside the
card a breadcrumb ("Semua kabupaten/kota › Kudus › Jati") leads back; bars are clickable
(`spmb.links` in `charts.js`) and the table rows are links.

Origin schools join `ref_sekolah` on `previous_school_id` and group by the school's own
`district`/`regency` (not the applicant's address). Schools typed by hand (`previous_school_name`)
form one group "Sekolah lain (belum ada di daftar)" in the place modes and keep their typed name
(normalised) in the per-school mode. Level is `ref_sekolah.level` (SD, MI, …), ownership
`ref_sekolah.ownership` (negeri/swasta; missing → "Status belum tercatat").

## Privacy

- Staff only; no names, numbers or other personal data anywhere on the page or in the CSV.
- Any count of 1 or 2 is written "< 3" in tables, tiles, chart tooltips and CSV files, and gets no
  percentage (`Libraries/Angka`). Charts may still draw small bars (D-111) but never print the number:
  each chart passes `spmb.mask: 3` and `charts.js` formats tooltips and data labels.
- Known limit: a hidden cell can sometimes be worked out from the row total minus the other cells.
  Totals are kept because staff need them; the page is staff-only.

## Performance

Every number is one `GROUP BY` query over `applicants` (about 25 small queries per page); PHP only
sorts the grouped rows, fills empty days or weeks of the timeline and folds age buckets. Migration
`AddYearStatusIndexToApplicants` adds the index `applicants_year_status (academic_year, status,
deleted_at)`, which every query filters on.

## CSV

`/admin/statistik/unduh/{group}` returns `statistik-{group}-{year}.csv` (UTF-8 with BOM, comma
separated): a header (title, filters, date, privacy note), then each card's table (label, optional
"Letak" for schools, Jumlah, Persen and a "Semua" row; cross-tabs: one column per income range and
Jumlah). Counts are masked like on the page. The button sits next to each group heading for users with
`laporan.export`.

## Files

`Libraries/StatistikFilter` (query string), `Libraries/StatistikQuery` (SQL aggregates),
`Libraries/StatistikReport` (cards for the page and the CSV), `Libraries/Angka` (masking and
numbers), `Controllers/Statistik`, `Views/index.php`, `Views/_kartu.php`, `Language/id/Statistik.php`,
`Guide/admin-sekolah.md`. Empty state image `state/kosong-statistik` (Codex brief 079), falling back to
`state/kosong-data`.

`resources/js/charts.js` gained optional page options under the key `spmb`: `mask`, `links`,
`sequential`, `gap`, `percentAxis` (documented at the top of the file).

## Tests

`tests/feature/StatistikTest.php`: location drill-down (text and code columns), origin school
grouping, income order, cross-tabs, ages and timeline, filters, masking, access (Panitia denied, Admin
allowed), CSV download, module off (and Pendaftaran off) → 404 and no menu entry.
