# Pendaftaran (registration)

Waves, registration numbers, the online and offline form, duplicate warnings,
the public status check and the applicant's own page. Requirements FR-REG-01…05,
FR-REG-07; decisions D-008, D-031, D-032, D-034, D-037.

## Routes

| URL | Who | What |
| --- | --- | --- |
| `GET/POST /daftar` | public | Online form as a step-by-step wizard (one POST, see below); consent checkbox `agree` required; closed page when `onlineOpen` is off, no wave covers today or the wave quota is full |
| `GET /daftar/selesai` | public (once) | Number and private link, shown once (flash data); a reload goes to the status check |
| `GET/POST /cek-status` | public | Number + date of birth, throttled per IP (`statusCheckAttempts` per minute), one generic error |
| `GET /cek-status/data` | applicant grant | Own page: status, data (NIK/KK masked), `module_panels('applicant_public', ['applicant' => $row])` |
| `GET/POST /cek-status/ubah` | applicant grant | Correct contact/address fields (`ApplicantForm::SELF_EDITABLE`); identity fields only by staff |
| `GET/POST /cek-status/keluar` | applicant grant | Ends the grant |
| `GET /tautan/<token>` | public | Private link → `service('applicantAccess')->grant()` |
| `GET /admin/pendaftar` | `pendaftar.view` | List; search (name, number, NISN, NIK) and filters `gelombang`, `cara`, `status`, `berkas`, `ganda` in the query string; NIK masked |
| `GET/POST /admin/pendaftar/tambah` | `pendaftar.create` | Offline form; staff confirm when duplicates are found |
| `GET /admin/pendaftar/<id>` | `pendaftar.view` | Detail + `module_panels('applicant_admin', ['applicant' => $row])` |
| `GET/POST /admin/pendaftar/<id>/ubah` | `pendaftar.edit` | Edit all fields, wave and staff notes |
| `POST /admin/pendaftar/<id>/status` | `pendaftar.edit` | registered / verified / cancelled (+ note) |
| `POST /admin/pendaftar/<id>/data-ganda` | `pendaftar.edit` | Duplicate reviewed (note required) |
| `POST /admin/pendaftar/<id>/tautan` | `pendaftar.edit` | New private link (old one stops working), shown once to staff with a WhatsApp button |
| `POST /admin/pendaftar/<id>/hapus` | `pendaftar.delete` | Soft delete; the number is never reused |
| `/admin/pengaturan/gelombang[/tambah, /<id>/ubah, /<id>, /<id>/hapus]` | `pengaturan.pendaftaran` | Waves of the academic year; no overlapping dates; a wave with applicants cannot be deleted |
| `GET/POST /admin/pengaturan/pendaftaran` | `pengaturan.pendaftaran` | Academic year, online switch, number format (live example), number scope, field modes |

Other modules add `admin/pendaftar/<id>/berkas/…` and `admin/pendaftar/<id>/kartu…` in their own route files.

## Services and models (interfaces for other modules)

| Piece | Notes |
| --- | --- |
| `Services\RegistrationNumber::next(string $academicYear): string` | Locks `registration_counters` (`SELECT … FOR UPDATE`, row inserted if missing); skips numbers that exist (also deleted). `format()`, `isValidFormat()`, `scope()` are static helpers |
| `Services\ApplicantService::register(array $data, string $method, ?int $staffId): array` | `['applicant', 'token', 'duplicates']`; number + insert in one transaction; wave from `WaveModel::current()` (offline: chosen `wave_id`, else today's, else none); throws `Exceptions\RegistrationClosed` (`switched_off`, `no_wave`, `full`) online |
| `ApplicantService::update()`, `changeStatus()`, `reviewDuplicate()`, `renewPrivateLink()`, `onlineClosedReason()` | All writes through `ApplicantModel` (audited) |
| `Services\DuplicateChecker::find(array $data, ?int $exceptId = null)` | NIK, NISN, `name_key` + `birth_date`, same academic year, not deleted; rows carry `reasons` (`nik`, `nisn`, `name`). `nameKey()` normalises names |
| `Services\ApplicantStats::summary()` | Dashboard counts (cancelled and deleted excluded) |
| `Models\ApplicantModel::findByNumberAndBirthDate()`, `findByAccessToken()`, `maskNik()` | |
| `Models\WaveModel::current(?DateTimeInterface)`, `forYear()`, `overlapping()`, `isFull()`, `applicantCount()`, `years()` | |
| `Libraries\ApplicantForm` | Field modes (core / configurable / hidden), cleaning, validation rules and messages; `SECTIONS` + `sections()` / `sectionOf()` (form sections = wizard steps), `GUARDIAN`, `asksGuardian()`, `fieldsToCheck()` (guardian answer) |

Events (`CodeIgniter\Events\Events`): `applicant_registered(array $applicant)`,
`applicant_status_changed(array $applicant, string $oldStatus)`.

Audit events: `applicant_registered`, `applicant_status_changed`, `duplicate_reviewed`,
`status_checked` (number + birth date or private link), `private_link_created`,
`settings_updated` (subject `Pendaftaran`), plus `created/updated/deleted` from the models.

## Settings (`setting('Pendaftaran.*')`, defaults in `app/Config/Pendaftaran.php`)

`academicYear`, `numberFormat` (`{YEAR}`, `{YY}`, `{0…0}`, optional `{ACAK}`/`{ACAK:1…5}` random
characters without look-alikes; one sequence token, at most one random token, max 30 characters, D-049),
`numberScope` (`year` default, restarts each academic year | `never` → counter scope `all`), `onlineOpen`,
`requiredFields`, `hiddenFields`, `staffOnlyFields` (not on the public form, filled by staff), `occupationOptions`,
`incomeOptions` (dropdown lists, Dapodik categories by default, D-048), `statusCheckAttempts`, `applicantSessionMinutes`.

Waves carry `quota`, `quota_mode` (`none` information only | `close` online closes at the quota | `online_limit`
online closes at `online_quota`; D-047) and `online_quota`.

Always required: full name, gender, birth place and date, address, parent WhatsApp
number, previous school (list entry or free text) and at least one of father,
mother or guardian name (one of the three always stays on the form).

## Guardian question ("Ada wali?")

The parents section is grouped (Ayah, Ibu, Wali, Kontak orang tua atau wali). Before the guardian fields
the form asks "Apakah calon murid tinggal bersama atau diasuh oleh wali?" with two large radio cards,
**Tidak ada wali** (default) and **Ada wali** (`has_guardian` = 0 / 1).

- With JavaScript the guardian fields show (and are enabled) only after "Ada wali"; without JavaScript all fields show
  with a note to fill them only for "Ada wali".
- Server side (`ApplicantForm::clean()` + `fieldsToCheck()`, used by the public form and the staff forms): "Tidak ada
  wali" clears the guardian fields (null) and skips their validation, even when the school marked them required.
  "Ada wali" checks the guardian fields the school marked required (`requiredFields`) **and always the guardian's
  name**. When no answer is posted but a guardian field is filled in, the answer counts as "Ada wali".
- Column `applicants.has_guardian` (TINYINT, nullable: null = not asked because no guardian field is on the form).
  Migration `AddHasGuardianToApplicants` backfills existing rows: 1 when any guardian field holds a value, else 0.
- The applicant's own page and the staff detail page show "Wali: Ada wali / Tidak ada wali"; with "Tidak ada wali"
  the (empty) guardian rows are left out. Audited like every applicant field (label "Ada wali").

## Step-by-step form (wizard)

`/daftar` is one form with one POST; `resources/js/wizard.js` (`initWizards`, `initGuardianChoice`) shows one step at a
time. Steps follow `ApplicantForm::SECTIONS` that hold visible fields — today 1 Data calon murid, 2 Alamat, 3 Orang tua
atau wali, 4 Sekolah asal — plus 5 **Periksa dan kirim**: a read-only summary of every answer with an "Ubah" button per
step, the consent checkbox and the submit button.

- Stepper: `app/Views/partials/stepper.php` (reusable, one array `['stepper' => ['label', 'steps', 'current']]`).
  Desktop: numbered circles joined by a line, labels under them; done = filled brand colour with a check, current = ring,
  upcoming = muted, red dot = the step has an error. Phones: "Langkah 2 dari 5 · Sekolah asal", a slim progress bar and
  small numbered dots. The state of each step is written out for screen readers.
- "Lanjut" checks the current step with the browser's constraint validation (`required`, `type`) and shows Indonesian
  messages under the fields; the server stays the source of truth. Focus moves to the step heading, the URL carries
  `#langkah-N` (browser Back/Forward move between steps), entered data stays. A step further on opens only when every
  step before it is complete. Enter in a field moves to the next step instead of sending.
- After a server-side error the first step with an error opens (`data-wizard-awal`), steps with errors get the red dot
  and an alert "Ada isian yang perlu diperbaiki di langkah ini.".
- Phones: Back/Next float in a sticky bar above the bottom bar and its raised button (D-081); from `md` the bar is part of
  the page. Motion (step fade, line colour) is off under `prefers-reduced-motion`.
- Without JavaScript every section shows on one page with its heading; nothing is lost.
- CSS: the "Steps" block at the end of `resources/css/komponen.css` (`.langkah-bulat`, `.stepper*`, `.wizard*`,
  `.pilihan-kartu`, `.garis-waktu*`, shared with the home page timeline).
- The applicant's correction page (`/cek-status/ubah`) only has a few address/contact fields and stays one page; the
  staff form (`/admin/pendaftar/tambah`, `/ubah`) stays one long page with the same section headers and the guardian
  question.

## Rules worth knowing

- Online registration closes per the wave's `quota_mode` (D-047); staff can always register offline.
- The public form never shows another applicant's data: a possible duplicate is only flagged (`possible_duplicate`) and every success page carries the same neutral hint (D-032).
- The private-link token (32 random bytes, base64url) is shown once; only its SHA-256 is stored. Staff can issue a new one.
- Applicants see NIK and KK numbers masked on their own page; staff lists mask NIK, the detail page shows it (`pendaftar.view`).
- The previous school uses the Referensi picker (`Modules\Referensi\Views\partials\school_picker`) when that module is on and the view exists; otherwise free text.
- `Database/Seeds/DemoSeeder`: three waves (first open today), 30 applicants and one complete example applicant
  **"Contoh Lengkap - Aisyah Putri Ramadhani"** (`DemoSeeder::EXAMPLE_NAME`): every field filled in (optional ones,
  guardian, occupations, incomes, staff note), a school from the SD/MI list (a Kudus school first), status verified and,
  when Berkas is on, every active document type uploaded as a generated sample (a portrait for the photo) and marked
  valid. `spmb:install --demo` prints its registration number, date of birth and private link. Idempotent: the 30 only
  for a year without applicants, the example only when no applicant has that name.

## Languages (D-109)

The registration form, success page, status check and applicant page also answer at `/en/…` and `/ar/…`
([../i18n.md](../i18n.md)): labels, help, steps, summary, consent and validation messages come from
`Language/{en,ar}/Pendaftaran.php` (public keys only), framework validation messages from
`codeigniter4/translations`. Forms post to the prefixed address and redirect back to it; the private link
shown after registering keeps the language. Data the applicant types is stored as typed (school lists such
as occupations and incomes stay as the school wrote them). Waves have optional `name_en/_ar` and
`description_en/_ar` (migration `AddTranslationsToWaves`, Gelombang form → "Terjemahan (tidak wajib)"),
read with `localized($wave, 'name')`. The staff pages stay Indonesian.

## Guides

`Guide/calon-murid.md`, `Guide/panitia.md`, `Guide/admin-sekolah.md`.
