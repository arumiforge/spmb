# Pendaftaran (registration)

Waves, registration numbers, the online and offline form, duplicate warnings,
the public status check and the applicant's own page. Requirements FR-REG-01…05,
FR-REG-07; decisions D-008, D-031, D-032, D-034, D-037.

## Routes

| URL | Who | What |
| --- | --- | --- |
| `GET/POST /daftar` | public | Online form; closed page when `onlineOpen` is off, no wave covers today or the wave quota is full |
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
| `Libraries\ApplicantForm` | Field modes (core / configurable / hidden), cleaning, validation rules and messages |

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

## Rules worth knowing

- Online registration closes per the wave's `quota_mode` (D-047); staff can always register offline.
- The public form never shows another applicant's data: a possible duplicate is only flagged (`possible_duplicate`) and every success page carries the same neutral hint (D-032).
- The private-link token (32 random bytes, base64url) is shown once; only its SHA-256 is stored. Staff can issue a new one.
- Applicants see NIK and KK numbers masked on their own page; staff lists mask NIK, the detail page shows it (`pendaftar.view`).
- The previous school uses the Referensi picker (`Modules\Referensi\Views\partials\school_picker`) when that module is on and the view exists; otherwise free text.
- `Database/Seeds/DemoSeeder`: three waves (first open today) and 30 applicants, idempotent.

## Guides

`Guide/calon-murid.md`, `Guide/panitia-pendaftaran.md`, `Guide/admin-sekolah.md`.
