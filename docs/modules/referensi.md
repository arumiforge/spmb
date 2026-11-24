# Module: Referensi

Previous schools (SD, MI, SDLB, SLB) that applicants choose on the registration
form (FR-REG-06, D-012), plus the school part of the import requirement (FR-RPT-04).
Table `ref_sekolah` (foundation migration), model `Modules\Referensi\Models\SchoolModel`.

Since D-111 the module also holds the national list of administrative regions (provinces, regencies and cities,
districts, villages) used for the applicant's address: table `ref_wilayah`, model
`Modules\Referensi\Models\RegionModel`. See "Region list (D-111)" below.

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
| `GET /admin/referensi/alamat` | `referensi.kelola` | Alamat pendaftar: state of the region list, applicants with/without region codes; `?pratinjau=1` previews the matching of typed addresses |
| `POST /admin/referensi/alamat` | `referensi.kelola` | Saves the matching (AddressBackfill); `mirip=0` = exact names only |
| `GET /wilayah/daftar?induk=` | public | JSON children of a region (no `induk`: provinces) for the address picker |
| `GET /wilayah/cari?q=` | public | JSON search of districts and villages with the full path |

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

## Region list (D-111)

The full list of Indonesian administrative regions ships as default data, like the SD/MI list:
38 provinces, 514 regencies and cities, 7,285 districts and 83,762 villages (source version `2026-02-13`).

- **Source**: [cahyadsn/wilayah](https://github.com/cahyadsn/wilayah), file `db/wilayah.sql`, codes and names per
  Kepmendagri No 300.2.2-2138 Tahun 2025. MIT licence (`THIRD_PARTY_NOTICES.md`, `Database/Data/wilayah/LICENSE.txt`).
  Public data, no personal data. The source has a few more districts and villages than the decree's summary
  (later corrections); the manifest records what is shipped.
- **Files**: `app/Modules/Referensi/Database/Data/wilayah/wilayah.json.gz` (one gzipped JSON array of
  `[code, name]` pairs sorted by code, 640 KB) and `manifest.json` (version, source, licence, SHA-256, counts).
- **Converter**: `node scripts/wilayah.mjs <path/to/wilayah.sql> [--version=YYYY-MM-DD]`. The SQL file is treated
  as untrusted input: it is never executed; only `('code','name')` tuples are read with a strict pattern, and every
  row is checked (code shape `11` / `11.01` / `11.01.01` / `11.01.01.2001`, parent present, letters, digits and a few
  marks only, at most 100 characters, no duplicates, every data line read, minimum counts). The version defaults to
  the "last edit" date in the SQL header.
- **Table** `ref_wilayah`: `code` varchar(13) primary key, `parent_code` varchar(8), `level` (1 provinsi,
  2 kabupaten/kota, 3 kecamatan, 4 desa/kelurahan), `name` varchar(100) in the official spelling (the source is
  already in title case: "Kabupaten Kudus", "Kota Semarang", "Getaspejaten"). Indexes `(parent_code, name)` for the
  selects and `(level, name)` for name searches.
- **Display rule** (`RegionModel::displayName()`): "Kabupaten X" becomes **"Kab. X"**; cities ("Kota Semarang"),
  provinces, districts and villages keep their official name. This matches the regency names of the SD/MI list
  (`Kab. Kudus`), so applicants and schools group alike. Full path: "Ploso, Jati, Kab. Kudus, Jawa Tengah".
  Region names are proper nouns and are never translated. A village code `…1xxx` is a kelurahan, `…2xxx` a desa
  (`RegionModel::villageKind()`).
- **Loading**: `spmb:install` (Referensi `InstallSeeder`) and `php spark referensi:import --bawaan` (every deploy)
  load it when the version in `manifest.json` differs from the setting `Referensi.regionsVersion`; otherwise only the
  manifest is read. A load is one transaction: new codes are added, renamed ones updated and codes no longer in the
  list removed (applicants keep their codes and names); audited as `regions_imported`. `--paksa` reloads,
  `--dry-run` only counts. About 5 seconds for the whole list.
- **Updating**: run the converter on a newer `wilayah.sql`, check the counts it prints, commit both files, deploy.
  Keep `ReferensiRegionsTest` green.

### Lookups (public JSON, no account)

- `GET /wilayah/daftar?induk=<code>` → `[{"code", "name", "label"}]`: children of a province, regency or district
  (no `induk`: the provinces), sorted by name without the "Kabupaten"/"Kota" word (a regency before the city of the
  same name). A malformed or village code → 400. `Cache-Control: public, max-age=86400` and an `ETag` built from the
  list version (`If-None-Match` → 304). Throttled per IP: `Referensi.regionListPerMinute` (120) → 429 `{"message"}`.
- `GET /wilayah/cari?q=<text>` → up to 20 districts and villages:
  `[{"code", "level", "name", "path", "kind", "codes": {province, regency, district, village}, "names": {…}}]`.
  At least one word of three letters; "desa", "kel.", "kec." and similar words are ignored; every word must start a
  word of the region's name or of its parents' names, at least one in its own name ("ploso jati"). Ranking: the name
  typed in full, words matching whole words of the path, the school's own regency and province
  (`School.regionCode`), districts before villages. Words written together are tried when nothing matches
  ("getas pejaten" → "Getaspejaten"). Throttled: `Referensi.regionSearchPerMinute` (60); cached for an hour.

### Address picker (`Views/partials/region_picker.php`, `resources/js/wilayah.js`)

Used by Pendaftaran's `applicant_fields` partial in the address section of the public form, the offline add/edit form
and the applicant's correction page, when `RegionInput::available()` (module on and the list loaded).

- Four selects, province → regency/city → district → village, posted as `province_code`, `regency_code`,
  `district_code`, `village_code`; each loads its options when the one above changes. Above them a
  "Cari desa atau kelurahan" combobox (ARIA 1.2: arrow keys, Enter, Escape) fills all four at once.
- Progressive enhancement: the selects and the search are rendered `disabled` inside a `hidden` block and the old
  text fields (`province`, `regency`, `district`, `village`) show. The script switches the selects on and the text
  fields off ("pilih" mode). "Tempat saya tidak ada di daftar, ketik sendiri" switches to the text fields ("ketik"
  mode; chosen names are copied into empty fields) and back. A disabled control is not posted, so the server gets
  codes or text, never both. A text round trip opens in "ketik" mode. A saved address typed by hand (text without
  codes) is matched with exact names first: the matching levels are preselected, and the picker opens in "pilih" mode
  when every typed level matched, otherwise in "ketik" mode with the text.
- Options are rendered on the server for the chosen codes (after a validation error, for a saved applicant, or the
  school's own province and regency on a fresh form when `School.regionCode` is set), so the picker is complete
  before the script loads anything.
- A level is required when its field or a field below it is required (Pendaftaran field modes). Levels hidden by the
  settings but needed as parents of a shown level still show as selects; their codes are saved, their text is not.
- The wizard summary reads the selects; the search box is skipped (`data-ringkas-lewati`).

### Reading the post (`Libraries/RegionInput.php`)

`(new RegionInput())->resolve($post, $cleanedData, $fields)` → `[$data, $errors]`, called by Pendaftaran's
`HandlesApplicantForm::validateApplicant()` before the field rules run.

- Codes posted: the deepest code must exist at its level and each posted higher code must be its ancestor; otherwise
  an error on that level's text field (`regionUnknown`, `regionMismatch`). All four `*_code` keys are set (deeper ones
  `null`) and the text fields on the form get the display names; a level not chosen becomes empty, so an old village
  never stays behind a new district.
- Only text posted (no JavaScript, or "ketik" mode): the text is kept as typed; `RegionMatcher` with **exact** names
  only adds the codes of the levels it is sure about and replaces those levels' text with the official names.

### Matching typed addresses (`Libraries/RegionMatcher.php`, `Libraries/AddressBackfill.php`)

`RegionMatcher::match(['province' => …, 'regency' => …, 'district' => …, 'village' => …], $similar)`:

1. Each level is cleaned: case, spaces, text after a comma or bracket, and the usual leading words ("Prov.",
   "Provinsi", "Kab.", "Kabupaten", "Kota", "Kodya", "Kec.", "Kecamatan", "Ds.", "Desa", "Kel.", "Kelurahan"). The
   text with the leading word is also compared (official names such as the district "Kota Kudus" or the villages
   "Kampung Baru"). Province short names are known ("Jateng", "DIY", "DKI", "NTB", …). For regencies "Kota X" or
   "Kab. X" chooses the kind; a bare name that fits both means the regency.
2. From the province down, each level is searched only under the regions still possible above it (a level without
   text stays open). Per level: the same name, then the same letters without spaces and marks
   ("Getas Pejaten" = "Getaspejaten"), then, when `$similar` and only inside at most three known parents, a name one
   letter off (two for names longer than five letters) with a single best candidate ("Plosso" → "Ploso").
3. A level whose text matches nothing stops the search there. The result is the deepest level that ended with
   exactly one region, with all its parents (`level` 0–4, `codes`, `names` = display names, `unmatched` = levels with
   text that could not be matched).

`AddressBackfill` runs it for applicants without a village code that have some address text (not deleted ones),
fills the codes and official names of the matched levels through `ApplicantModel` (one audited `updated` per
applicant, plus one `addresses_matched` summary) and keeps the text of unmatched levels. Report: checked, full
(every typed level matched), partial, none, changed, and up to 300 rows. Safe to repeat.

```bash
php spark referensi:cocokkan-alamat --dry-run      # show what would change
php spark referensi:cocokkan-alamat                # save (names one or two letters off allowed)
php spark referensi:cocokkan-alamat --tanpa-mirip  # exact names only
```

The same is available to Admin Sekolah in Sekolah Asal → Alamat pendaftar (preview first, then "Simpan hasil
pencocokan"). It is **not** run automatically after the import: it rewrites applicant data, so a person looks at the
preview first (one-time step in `docs/deployment.md`).

### Interface for other modules (Statistik)

`applicants.province_code`, `regency_code`, `district_code`, `village_code` (nullable; indexed except the province)
hold the codes; the text columns `province`, `regency`, `district`, `village` hold the display names of the chosen
regions, or the text typed when no region matched. Join `ref_wilayah` on the code for names and parents.

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
| `regionsVersion` | empty | Version of the bundled region list already loaded (written by the importer, D-111) |
| `regionListPerMinute` | 120 | `/wilayah/daftar` requests per IP per minute |
| `regionSearchPerMinute` | 60 | `/wilayah/cari` requests per IP per minute |

`School.regionCode` (Identitas Sekolah, optional): the school's own regency/city code, e.g. `33.19`. A fresh address
on the forms starts with its province and regency, and region searches list it first.
