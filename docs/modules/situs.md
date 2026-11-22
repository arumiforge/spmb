# Situs (public information pages)

Public pages for parents and students (FR-WEB-01 Persyaratan and Jadwal Gelombang,
FR-WEB-02 Indonesian slugs), the Tanya Jawab page (D-072) and the registration actions on
the home page. Requires the Pendaftaran module. Persyaratan and Jadwal read waves and
document types; Tanya Jawab has its own table and admin screen.

## Routes

| URL | Content |
| --- | --- |
| `/persyaratan` | Active document types in `sort_order` (required / optional, "boleh menyusul", formats, size limit) while the Berkas module is on, then the registration steps (upload and card steps only when Berkas / Kartu are on). Empty state when Berkas is off or no type is active. Uses `DocumentTypeModel::active()` when available, else `is_active = 1` ordered by `sort_order`, `name`. |
| `/jadwal` | Active waves of `setting('Pendaftaran.academicYear')` with state *Sedang dibuka* / *Akan dibuka* / *Sudah ditutup*, dates, quota and description, plus a banner. Empty state without waves. |
| `/tanya-jawab` | Published questions grouped by topic (topic chips when there is more than one topic, `<details>` accordion per question, anchors `#<topic-slug>` and `#pertanyaan-<id>`), then a contact block from `School.*` settings (WhatsApp, phone, email, address) and links to Daftar (while online registration is open), Persyaratan, Jadwal and Cek Status. Empty state with no published question. The public menu link "Tanya Jawab" only appears while a question is published. |
| `/` (`App\Controllers\Home`, `app/Views/home.php`) | "Daftar Sekarang" while online registration is open, "Cek Status" whenever Pendaftaran is on, "Lihat Persyaratan" when Situs is on, the current/next wave line and three short steps. With Pendaftaran off it only points to the guide. |

## Admin: Website → Tanya jawab

Permission `situs.kelola` (Admin Sekolah; Super Admin implicitly). Every change goes through
the audited `SiteQuestionModel` (audit subject `site_questions`, labels in `Config/Audit.php`).

| URL | Action |
| --- | --- |
| `GET /admin/tanya-jawab` | List grouped by topic, badges *Tampil* / *Disembunyikan* / *Perlu dilengkapi*, empty state |
| `GET/POST /admin/tanya-jawab/tambah` | Add (`?topik=` prefills the topic) |
| `GET/POST /admin/tanya-jawab/{id}/ubah` | Edit |
| `POST /admin/tanya-jawab/{id}/pindah` | `arah=naik\|turun`, inside the topic only |
| `POST /admin/tanya-jawab/topik/pindah` | `topik`, `arah`: moves the whole topic |
| `POST /admin/tanya-jawab/{id}/tampilkan` | `tampil=1\|0` show or hide |
| `POST /admin/tanya-jawab/{id}/hapus` | Soft delete |

## Table `site_questions`

`topic` (80), `question` (200), `answer` (Markdown, max 5,000 characters), `sort_order`,
`is_published`, timestamps, soft delete. One `sort_order` runs through the list;
`normalise()` keeps a topic's questions together (topics are matched ignoring case and spaces)
and renumbers in steps of 10.

Answers are rendered by `Libraries/AnswerRenderer` with CommonMark (`html_input: strip`,
no unsafe links, like the guide pages). Links that start with `/` are rewritten with
`site_url()`. Placeholders: `{nama_sekolah}`, `{tahun_ajaran}`.

Text only the school can write is marked `[ISI SEKOLAH: ...]`
(`SiteQuestionModel::PLACEHOLDER`). Such a question cannot be published until the marker is
replaced; the admin list counts and badges them.

## Starter questions (client pack)

`php spark spmb:branding <pack>` also loads `tanya-jawab.json` from the pack folder or its
parent (e.g. `clients/smp-nu-al-maruf-kudus/tanya-jawab.json` next to `branding/`), only while
the installation has no questions (deleted ones count, so the school's edits are never
overwritten). Format: `{"questions": [{"topic", "question", "answer", "published"?}]}`.
Questions with `[ISI SEKOLAH` are stored hidden.

## WaveSchedule

`Modules\Situs\Services\WaveSchedule` (dates inclusive, today from `Time::today()` in the
app timezone):

- `forYear(?string $year = null, ?string $today = null)` — active waves with `state`.
- `current()`, `next()` — the open / next upcoming wave.
- `onlineOpen()` — `Pendaftaran.onlineOpen` and a wave covers today.
- `banner()` — `open`, `offline` (wave running, online form switched off: come to the
  school), `upcoming` or `closed`, with the Indonesian text.

## Tests

`tests/feature/SitusTest.php`, `tests/feature/HomeTest.php`, `tests/feature/SitusTanyaJawabTest.php`.
