# Module: Berkas

Applicant documents (FR-DOC-01…04, D-036): which documents are required, upload
by applicants or staff, safe storage, the committee's check, and the overall
document status of each applicant. Requires the Pendaftaran module.

## Routes

| Route | Permission | Purpose |
| --- | --- | --- |
| `GET/POST /admin/pengaturan/berkas` | `pengaturan.pendaftaran` | Document types list (order, edit, delete) and image settings `Berkas.imageMaxEdge`, `imageQuality`, `deadline` (audited `settings_updated`) |
| `GET/POST /admin/pengaturan/berkas/tambah`, `…/{id}/ubah` | `pengaturan.pendaftaran` | Add / edit a document type (name, code, description, required, may follow, max size, formats JPG/PNG/PDF, shown or hidden) and its example file (upload field `template`, `remove_template`, `template_label`, `template_enabled`; form is `multipart/form-data`) |
| `GET /admin/pengaturan/berkas/{id}/contoh` | `pengaturan.pendaftaran` | The type's stored example file, also while it is switched off |
| `POST /admin/pengaturan/berkas/{id}/pindah` (`arah=naik\|turun`) | `pengaturan.pendaftaran` | Move a type up or down |
| `POST /admin/pengaturan/berkas/{id}/hapus` | `pengaturan.pendaftaran` | Soft delete; refused when any applicant ever uploaded that type (hide it instead) |
| `GET /admin/berkas` | `pendaftar.verifikasi` | Queue: current documents, newest applicants first; filters `q` (name/number), `status` (`menunggu` default, `perlu-diganti`, `sesuai`, `menyusul`, `semua`), `jenis`, `gelombang`; 20 per page |
| `GET /admin/berkas/{docId}/periksa` | `pendaftar.verifikasi` | Review screen: preview next to the applicant's name, number, birth data and parents, with *Sesuai* / *Perlu Diganti* |
| `POST /admin/berkas/{docId}/periksa` | `pendaftar.verifikasi` | `status=valid\|invalid`, `note` (required for invalid), `kembali=antrean` (opens the next waiting document) or `pendaftar` (back to `/admin/pendaftar/{id}#berkas-{typeId}`) |
| `GET /admin/berkas/{docId}/lihat` | `pendaftar.view` | The stored file |
| `POST /admin/pendaftar/{id}/berkas/{typeId}` | `pendaftar.edit` | Upload on the applicant's behalf (field `berkas`), or `aksi=menyusul` |
| `POST /cek-status/berkas/{typeId}` | applicant grant | Applicant upload (field `berkas`) or `aksi=menyusul`; no grant → redirect to `/cek-status` with a friendly message; back to `/cek-status/data#berkas-{typeId}` |
| `GET /cek-status/berkas/{docId}` | applicant grant | The applicant's own current file; anything else → 404 |
| `GET /berkas/contoh/{code}` | none (public) | Example file of a type (`Contoh::download`); 404 unless the type is active (not deleted), `template_enabled = 1` and the file exists |

Files are sent with the stored `Content-Type`, `Content-Disposition: inline;
filename="<code>-<registration number>.<ext>"`, `X-Content-Type-Options: nosniff`
and `Cache-Control: private, no-store`.

## Example files ("Unduh contoh", `Services\TemplateStorage`)

A document type can carry an example or blank form, e.g. the letter the primary
school fills in for "Surat Keterangan Aktif". Columns on `document_types`
(migration `AddTemplateToDocumentTypes`):

| Column | Meaning |
| --- | --- |
| `template_path` | Stored file name `<32 hex>.<pdf\|doc\|docx\|jpg\|png>` in `writable/uploads/berkas-contoh/` (null = none) |
| `template_name` | The school's file name, offered on download (label only; path parts and control characters removed) |
| `template_enabled` | 1 = shown to applicants ("Tampilkan contoh untuk pendaftar"), default 0 |
| `template_label` | Link text (max 60); empty = "Unduh contoh" |

- Accepted: PDF, DOC, DOCX, JPG/JPEG, PNG, at most 5 MB (`TemplateStorage::MAX_KB`). The
  extension and the real MIME type (`finfo`) must match; a `.docx` must also contain
  `word/document.xml`. Files are kept as uploaded (no re-encoding).
- A new file replaces the old one (the old file is removed after the row is saved);
  "Hapus file contoh ini" removes it and switches the example off. Switching it on
  without a file is refused with a message under the file field. Deleting a type
  removes its file.
- Changes go through `DocumentTypeModel` and are audited as `updated` on
  `document_types` (field labels in `Config/Audit.php`).
- Download: `Content-Type` from the stored extension, `Content-Disposition: attachment;
  filename="…"; filename*=UTF-8''…` with the school's file name, `X-Content-Type-Options:
  nosniff`, `Cache-Control: no-cache`.
- Links: partial `Views/partials/template_link.php` (`['link' => ['type' => row, 'class' => ?]]`,
  Lucide `download` icon, prints nothing unless `DocumentTypeModel::templateShown()`). Shown
  in each row of the applicant panel (`partials/document_row`, so also on the staff panel)
  and on `/persyaratan`. The settings list shows a badge "Contoh ditampilkan" /
  "Contoh disembunyikan" for types with a file. The registration form itself lists no
  documents; applicants see them on `/cek-status/data` after registering.
- Client packs: `spmb:branding <pack>` installs `berkas-contoh/<code>.<ext>` found next to
  `branding.json` or in its parent (the client folder, e.g.
  `clients/smp-nu-al-maruf-kudus/berkas-contoh/sk-aktif.doc`) as the example of the type with
  that code and switches it on — only when the type has no example yet. The same file again
  changes nothing (a school that switched it off keeps it off); a different file the school
  uploaded itself is kept. Unknown codes and other formats are skipped with a note. Generic
  installations ship no example files.

## Storage and checks (`Services\DocumentStorage`)

- Path `writable/uploads/berkas/<yyyy>/<32 hex>.<jpg|pdf>`; the client file name
  is only kept as a label (`original_name`, path parts and control characters removed).
- Extension (from the type's list) **and** real MIME type (`finfo`) must match the type.
  `.heic` gets a dedicated message (iPhone "Most Compatible" setting).
- Images (JPEG/PNG): accepted up to `Berkas.imageUploadLimitKb` (10 MB) so phone
  camera photos work, refused above 50 megapixels; turned upright from the EXIF
  orientation, scaled so the longest side is at most `imageMaxEdge`, drawn on white
  (transparent PNG) and saved as JPEG at `imageQuality` with plain GD — no EXIF or
  other metadata survives. The result must fit the type's `max_size_kb`.
- PDFs: copied unchanged; must fit `max_size_kb`.
- PHP upload errors become friendly messages (`UploadInput`); `UPLOAD_ERR_INI_SIZE`
  reports the server's own limit.

## Document actions (`Services\DocumentService`)

| Action | Rules | Audit event |
| --- | --- | --- |
| `upload()` | New row `pending`; the previous current row of that type is soft-deleted (its file is kept). Applicants cannot replace a `valid` document or change anything when their registration is `cancelled`; staff can. | `document_uploaded` (+ `document_replaced` for the old row) |
| `markToFollow()` | Only types with `may_follow`, and only when nothing usable is there (none or `invalid`) | `document_to_follow` |
| `verify()` | `valid` or `invalid` (+ note, required); stores `verified_by/at` | `document_verified` |

The model writes of these actions run with callbacks off and log one semantic
event each (old/new values), so Riwayat shows "Berkas diunggah" instead of a
generic "created" next to it.

## Document status (`Services\DocumentStatus`)

`DocumentStatus::refresh(int $applicantId): string` recomputes
`applicants.document_status` (through `ApplicantModel`, audited only when it
changes) after every action, from the active types:

1. `incomplete` — a required type has no document, or it is `invalid`, or it is
   `to_follow` while the type no longer allows that;
2. `pending` — otherwise, any current document (also optional ones) waits for a check;
3. `to_follow` — otherwise, a required type is "menyusul";
4. `complete` — every required document is valid.

`DocumentStatus::compute()` is the pure function behind it.

## Interfaces for other modules

- `DocumentTypeModel::active(): list<array>` — active types in display order (for `/persyaratan`).
  Helpers: `extensionsOf()`, `mimesOf()`, `acceptOf()`, `formatLabel()`, `hasTemplate()`,
  `templateShown()`, `templateLabel()`.
- Partial `Views/partials/template_link.php` — the "Unduh contoh" link of a type row.
- `ApplicantDocumentModel::currentFor(int $applicantId)` / `currentOf($applicantId, $typeId)`.
- `DocumentStatus::refresh()`.
- Panels `Views/panels/applicant_public.php` and `Views/panels/applicant_admin.php`
  receive `['applicant' => row]` and render nothing without an applicant or without
  active types. Section id `berkas`, rows `berkas-<typeId>`. Per-row upload errors
  come from the flash `berkas_error` (`[typeId => message]`).

## Seeds

- `InstallSeeder`: Kartu Keluarga (`kk`), Akta Kelahiran (`akta`), Pas Foto (`foto`,
  images only, 1 MB; recent face photo, plain background) required; Surat Keterangan
  Aktif dari SD/MI (`sk-aktif`, 2 MB, stamped and signed by the SD/MI principal) and
  Rapor / Surat Keterangan Lulus (`rapor-skl`, 5 MB) required but may follow.
  Idempotent by `code` (also deleted ones). Defaults until the school answers.
- Migration `AddSkAktifDocumentType` adds `sk-aktif` to installations seeded before it
  existed, with the same rule (no row with that code, deleted ones included; an empty
  table is left to the seeder), just before `rapor-skl` (later types move down one
  place) or at the end when `rapor-skl` is gone. `down()` keeps the row.
- `DemoSeeder`: for up to 12 applicants without documents, generated sample images
  with mixed statuses. Does nothing when there are no applicants yet.

## Deployment note

Phone photos are larger than PHP's default 2 MB upload limit. Set
`upload_max_filesize = 10M`, `post_max_size = 12M` (php-fpm) and
`client_max_body_size 12m;` (nginx), matching `Berkas.imageUploadLimitKb`.

## Guides

`Guide/calon-murid.md` (upload, menyusul, statuses, problems),
`Guide/panitia.md` (checking, uploading for applicants),
`Guide/admin-sekolah.md` (document list, example files, image settings).
