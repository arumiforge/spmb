# Module: Referensi

Previous schools (SD, MI, SDLB, SLB) that applicants choose on the registration
form (FR-REG-06, D-012), plus the school part of the import requirement (FR-RPT-04).
Table `ref_sekolah` (foundation migration), model `Modules\Referensi\Models\SchoolModel`.

## Routes

| Route | Permission | Purpose |
| --- | --- | --- |
| `GET /admin/referensi/sekolah` | `referensi.kelola` | List; filters `q` (name, NPSN, district, regency), `jenjang`, `kabupaten`, `status` (`aktif`/`nonaktif`); 25 per page |
| `GET/POST /admin/referensi/sekolah/tambah` | `referensi.kelola` | Add one school (source `manual`) |
| `GET/POST /admin/referensi/sekolah/{id}/ubah` | `referensi.kelola` | Edit; shows how many applicants chose it |
| `POST /admin/referensi/sekolah/{id}/nonaktifkan`, `…/aktifkan` | `referensi.kelola` | Hide from / show again in the search (never deleted) |
| `GET/POST /admin/referensi/sekolah/impor` | `referensi.kelola` | CSV/XLSX upload (max 5 MB, 25 000 rows) with a row-level report |
| `GET /admin/referensi/sekolah/impor/contoh` | `referensi.kelola` | Example CSV |
| `GET /admin/referensi/sekolah-lain` | `referensi.kelola` | School names typed by applicants (`previous_school_id` null), grouped with counts |
| `GET/POST /admin/referensi/sekolah-lain/petakan` | `referensi.kelola` | Map one typed name to a list entry: all unmapped applicants with that name get `previous_school_id` |
| `POST /admin/referensi/sekolah-lain/tambah` | `referensi.kelola` | Add the school to the list and map it in one step |
| `GET /sekolah-asal/cari?q=` | public | JSON search for the registration form (below) |

Audit: list edits go through `SchoolModel` (`Auditable`). Imports write in bulk and log one
`schools_imported` event (counts, source, file name). Mapping updates applicants through
`ApplicantModel` (one `updated` entry per applicant) and logs `school_mapped` on the school.

## Interface for other modules

- `GET /sekolah-asal/cari?q=<text>` → `[{"id", "npsn", "name", "level", "district", "regency", "label"}]`.
  Active schools only, at least 2 characters, max 20, configured provinces (`Referensi.provinces`)
  first, then names starting with the text, then names matching more words. Every word must start
  a word of the name, district or regency (or the start of the NPSN); `sdn`/`min`/`mis` are expanded.
  Throttled per IP: `Referensi.searchPerMinute` (30) → HTTP 429 `{"message"}`.
- View `Modules\Referensi\Views\partials\school_picker` with one array
  `picker = ['id' => ?int, 'name' => ?string, 'error' => ?string, 'required' => bool]`.
  Posts `previous_school_id` (hidden) and `previous_school_name` (text). Without JavaScript it is
  a plain text field; with JavaScript an ARIA 1.2 combobox (arrow keys, Enter, Escape) with a last
  option "Sekolah saya tidak ada di daftar". One picker per page. `old()` values win.
- `SchoolModel::fromPicker($id, $name)` → `['previous_school_id' => ?int, 'previous_school_name' => ?string]`:
  keeps the id only if the school is active and the text is still its name or label (an edited
  text becomes free text); an exact name of exactly one active school counts as chosen.
- `SchoolModel::label(int $id): ?string` → `"SD NEGERI 1 JATI KULON — Kec. Jati, Kab. Kudus"`.
- `SchoolModel::search(string $text, int $limit = 20)`, `SchoolModel::normalise(array $row)`.
- `Database/Seeds/DemoSeeder`: 21 sample schools around Kudus (NPSN `99…`, never real), idempotent.

Stored conventions: `province` without "Prov." (`Jawa Tengah`), `regency` as `Kab. Kudus` /
`Kota Semarang` ("Kabupaten X" becomes "Kab. X"), `district` without "Kec.", names with
`SDN`/`SD N` → `SD Negeri`, `MIN` → `MI Negeri`, `MIS` → `MI`.

## File import (admin page and `--file`)

First row = column titles (any order, case-insensitive): **Nama Sekolah** (`nama`, `nama_sekolah`)
and **Jenjang** (`bentuk_pendidikan`; `MIS`/`MIN` → `MI`) are required; optional `NPSN`, `Status`
(Negeri/Swasta, N/S), `Provinsi`, `Kabupaten/Kota`, `Kecamatan`, `Desa/Kelurahan`, `Alamat`.
CSV separator (`,` `;` tab) and encoding are detected. Upsert: by NPSN; without NPSN by
name + district + regency; a row with an NPSN also completes a school typed earlier without one.
Empty cells never wipe data, `is_active` is never changed, nothing is deleted. Rows with
problems are skipped and listed with the spreadsheet row number and a plain reason.

## Bundled national SD/MI list (D-058, default)

SPMB ships the list of every SD and MI in Indonesia (177,422 schools, 38 provinces, version `2026-11-18`)
in `app/Modules/Referensi/Database/Data/sekolah-sd-mi/`: one gzipped JSON file per province
(`jawa-tengah.json.gz`, …, 2.7 MB in total) and `manifest.json` (version, file, province, counts).
Public data only: NPSN, name, level (`SD`/`MI`), status (negeri/swasta), village, district, regency,
province. No personal data, so it is kept in git.

- `php spark spmb:install` loads it (Referensi `InstallSeeder`), about 50 s and < 1 GB memory.
- Every deploy runs `php spark referensi:import --bawaan` (`spmb deploy`, D-068); it does nothing when
  the version in `manifest.json` equals the setting `Referensi.bundledVersion`, so an update that ships a
  newer list loads it once.
- Rows get `source = nasional` ("Daftar nasional"). The usual upsert rules apply (D-039): by NPSN, never
  deleted, `is_active` never changed, so schools an admin hid stay hidden. A newer list does overwrite
  name/address edits of the same NPSN.
- Temporary numbers such as `NP718159` (3 schools in DKI Jakarta) are not NPSNs: these rows are matched by
  name + district + regency. One NPSN appears twice in Papua (two spellings); the second wins.

```bash
php spark referensi:import --bawaan                                  # skipped when already loaded
php spark referensi:import --bawaan --paksa                          # load again
php spark referensi:import --bawaan --provinsi "Jawa Tengah" --dry-run   # one province, count only
```

**Updating the list**: replace the province files (same JSON shape: `npsn`, `nama`, `bentuk_pendidikan`,
`status_sekolah`, `desa`, `kecamatan`, `kabupaten`, `provinsi`), gzip them with `gzip -9 -n`, update
`manifest.json` (new `version`, counts) and keep `ReferensiBundledSchoolsTest` green.

## Other JSON lists (`--file`, D-057)

Any other JSON list loads with:

```bash
php spark referensi:import --file /path/sekolah.json            # or .json.gz, .ndjson
php spark referensi:import --file /path/sekolah.json --dry-run  # count only
```

Note the space after `--file` (CodeIgniter's CLI does not read `--file=…`).

Accepted shapes: an array of objects, `{"data": [ ... ]}`, or one object per line (NDJSON); gzip is
unpacked. Keys are matched like spreadsheet headers (case, spaces, `_` and `.` ignored):

| Column | Accepted keys |
| --- | --- |
| npsn | `npsn` (8 digits; numbers are fine; `NP…` temporary numbers are ignored) |
| name | `nama_sekolah`, `nama`, `sekolah`, `name` |
| village | `desa`, `kelurahan`, `desa_kelurahan`, `kelurahan_desa`, `village` |
| district | `kecamatan`, `kec`, `district` |
| regency | `kabupaten_kota`, `kabupaten`, `kota`, `kab_kota`, `regency` |
| province | `provinsi`, `prov`, `province` |
| level (optional) | `jenjang`, `bentuk_pendidikan`, `level` — SD, MI, SDLB, SLB or "Sekolah Dasar"/"Madrasah Ibtidaiyah"; when missing it is taken from the name (SD…, SDN…, MI…, MIN…, SDLB…, SLB…) |
| ownership (optional) | `status`, `status_sekolah` — negeri/swasta |

Rows are upserted by NPSN (or name + district + regency), never deleted; running it again is quick and changes
nothing. Rows without a name or a recognisable level are skipped and listed (first 1,000). The school search stays at
~50 ms with the national volume.

## Dapodik import: `php spark referensi:import`

```bash
php spark referensi:import                       # Referensi.provinces, Referensi.levels
php spark referensi:import --kabupaten=Kudus --dry-run
php spark referensi:import --provinsi="Jawa Tengah" --delay=1000
php spark referensi:import --file emis-mi.xlsx   # MI from EMIS, or any CSV/XLSX/JSON
```

Walks provinces → regencies → districts → schools, one request at a time with a pause
(`Referensi.importDelayMs`, 500 ms), and upserts each district (source `dapodik`). Safe to run
again. MI is not in Dapodik (madrasah are listed by the Ministry of Religious Affairs, EMIS):
import it from a file. SD requests also keep SDLB rows; SLB only when `SLB` is a configured level.

Endpoints (portal `https://dapo.kemendikdasmen.go.id`, read from the portal's public JavaScript
bundle on 2026-11-17; the older `/rekap/progresSP` endpoints are gone):

| Step | Request | Fields used |
| --- | --- | --- |
| Provinces | `GET /api/progress-pengiriman/provinsi?jenjang=SD` | `kode_provinsi`, `provinsi` ("Prov. Jawa Tengah") |
| Regencies | `GET /api/progress-pengiriman/kabupaten?kode_provinsi=030000&jenjang=SD` | `kode_kabupaten`, `kabupaten` |
| Districts | `GET /api/progress-pengiriman/kecamatan?kode_kabupaten=031900&jenjang=SD` | `kode_kecamatan`, `kecamatan` |
| Schools | `GET /api/progress-pengiriman/kecamatan/school?kode_kecamatan=031901&jenjang=SD` | `npsn`, `nama`, `bentuk_pendidikan`, `status_sekolah`, `kabupaten`, `kecamatan` |

Responses are a JSON list or `{"data": [...]}` / `{"results": [...]}`; all three are accepted.

**Access key — open question.** The portal's own page sends an `Authorization: Bearer` key with
these requests. This session did not call the API with it (not verified live); the importer
sends a key only when the operator sets `referensi.dapodikToken` in `.env`, and otherwise reports
"Dapodik refused the request" if the portal requires one. Whether a key may be used, and which,
is for Arumi Studios to decide (see the PR). Until then, the file import covers the data. The
fallback portal `sekolah.data.kemendikdasmen.go.id` was not checked. Tests use recorded fixtures
in `tests/_support/Referensi/fixtures/` (shapes taken from the bundle's sample data).

## Settings (`app/Config/Referensi.php`, `setting('Referensi.*')`)

| Key | Default | Meaning |
| --- | --- | --- |
| `provinces` | `['Jawa Tengah']` | Imported and suggested first |
| `levels` | `['SD', 'MI']` | Levels applicants come from |
| `dapodikUrl` | `https://dapo.kemendikdasmen.go.id` | `.env` `referensi.dapodikUrl` |
| `dapodikToken` | empty | `.env` `referensi.dapodikToken`; never commit |
| `importDelayMs` | 500 | Pause between Dapodik requests |
| `searchPerMinute` | 30 | Public search limit per IP |
| `bundledVersion` | empty | Version of the bundled list already loaded (written by the importer) |
