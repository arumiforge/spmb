<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Modules\Situs\Models\SiteQuestionModel;
use Throwable;

/**
 * "Persiapan sistem" on the dashboard (D-099): what is still missing before the
 * system runs fully, in the order a school sets it up. Each step names the page
 * that fixes it; a user only sees steps whose page they may open. Steps of a
 * switched-off module are left out.
 *
 * A step: key, group, title, hint, url, permission, done, required
 * (required = needed for registration to run; otherwise recommended).
 */
class SetupChecklist
{
    /**
     * @return list<array{key: string, group: string, title: string, hint: string, url: string, permission: string, done: bool, required: bool}>
     */
    public function steps(?User $user = null): array
    {
        $steps = [...$this->school(), ...$this->registration(), ...$this->tests(), ...$this->website(), ...$this->accounts(), ...$this->system()];

        if ($user === null) {
            return $steps;
        }

        return array_values(array_filter($steps, static fn (array $step): bool => $user->can($step['permission'])));
    }

    /**
     * Counts for the progress bar.
     *
     * @param list<array{done: bool, required: bool}> $steps
     *
     * @return array{done: int, total: int, requiredLeft: int, percent: int}
     */
    public static function progress(array $steps): array
    {
        $done = count(array_filter($steps, static fn (array $s): bool => $s['done']));

        return [
            'done'         => $done,
            'total'        => count($steps),
            'requiredLeft' => count(array_filter($steps, static fn (array $s): bool => $s['required'] && ! $s['done'])),
            'percent'      => $steps === [] ? 100 : (int) floor($done / count($steps) * 100),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function school(): array
    {
        $url  = 'admin/pengaturan/sekolah';
        $perm = 'pengaturan.sekolah';
        $name = trim((string) setting('School.name'));

        return [
            $this->step('school-name', 'Identitas sekolah', 'Isi nama sekolah', 'Nama lengkap dan nama singkat tampil di semua halaman, kartu, dan surat.', $url, $perm, $name !== '' && $name !== 'Nama Sekolah'),
            $this->step('school-logo', 'Identitas sekolah', 'Unggah logo sekolah', 'Logo tampil di halaman depan, dasbor, dan kartu pendaftaran.', $url, $perm, BrandingAssets::file('logo') !== ''),
            $this->step('school-address', 'Identitas sekolah', 'Isi alamat sekolah', 'Alamat tampil di halaman depan bersama peta lokasi.', $url, $perm, trim((string) setting('School.address')) !== ''),
            $this->step('school-contact', 'Identitas sekolah', 'Isi nomor telepon atau WhatsApp', 'Orang tua bisa langsung menghubungi panitia.', $url, $perm, trim((string) setting('School.phone')) !== '' || trim((string) setting('School.whatsapp')) !== ''),
            $this->step('school-email', 'Identitas sekolah', 'Isi email sekolah', 'Tampil di bagian kontak dan bawah halaman.', $url, $perm, trim((string) setting('School.email')) !== '', false),
            $this->step('school-map', 'Identitas sekolah', 'Tempel peta lokasi dari Google Maps', 'Tanpa peta tempelan, letak sekolah dicari dari alamat dan bisa kurang tepat.', $url, $perm, trim((string) setting('School.mapEmbed')) !== '', false),
            $this->step('school-social', 'Identitas sekolah', 'Isi tautan media sosial', 'Instagram, Facebook, YouTube, atau TikTok sekolah tampil di halaman depan.', $url, $perm, $this->anySocial(), false),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function registration(): array
    {
        if (! $this->enabled('Pendaftaran')) {
            return [];
        }

        $perm  = 'pengaturan.pendaftaran';
        $year  = (string) setting('Pendaftaran.academicYear');
        $today = date('Y-m-d');
        $waves = $this->count('waves', ['academic_year' => $year, 'deleted_at' => null]);
        $open  = $this->count('waves', ['academic_year' => $year, 'deleted_at' => null, 'is_active' => 1, 'ends_on >=' => $today]);

        $steps = [
            $this->step('reg-year', 'Pendaftaran', 'Periksa tahun ajaran', 'Tahun ajaran sekarang: ' . ($year !== '' ? $year : '(kosong)') . '. Nomor pendaftaran dimulai lagi setiap tahun ajaran.', 'admin/pengaturan/pendaftaran', $perm, $year !== ''),
            $this->step('reg-waves', 'Pendaftaran', 'Buat gelombang pendaftaran', 'Atur tanggal buka dan tutup serta kuota setiap gelombang.', 'admin/pengaturan/gelombang', $perm, $waves > 0),
            $this->step('reg-open-wave', 'Pendaftaran', 'Pastikan ada gelombang yang aktif', 'Minimal satu gelombang aktif yang belum lewat tanggal tutupnya.', 'admin/pengaturan/gelombang', $perm, $open > 0),
            $this->step('reg-online', 'Pendaftaran', 'Buka pendaftaran online', 'Saat ditutup, orang tua hanya bisa mendaftar langsung di sekolah.', 'admin/pengaturan/pendaftaran', $perm, (bool) setting('Pendaftaran.onlineOpen'), false),
        ];

        if ($this->enabled('Berkas')) {
            $steps[] = $this->step('reg-documents', 'Pendaftaran', 'Atur daftar berkas', 'Pilih berkas yang wajib diunggah dan yang boleh menyusul.', 'admin/pengaturan/berkas', $perm, $this->count('document_types', ['is_active' => 1, 'deleted_at' => null]) > 0);
        }

        return $steps;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tests(): array
    {
        if (! $this->enabled('Tes')) {
            return [];
        }

        $year = (string) setting('Pendaftaran.academicYear');

        return [
            $this->step('test-rooms', 'Tes penempatan', 'Tambah ruang tes', 'Isi nama dan kapasitas setiap ruang.', 'admin/tes/ruang', 'tes.atur', $this->count('test_rooms', ['is_active' => 1, 'deleted_at' => null]) > 0),
            $this->step('test-sessions', 'Tes penempatan', 'Buat sesi tes', 'Sesi tes untuk tahun ajaran ini, bisa dibuat sekaligus dengan pembuat sesi.', 'admin/tes/sesi', 'tes.atur', $this->count('test_sessions', ['academic_year' => $year, 'is_active' => 1, 'deleted_at' => null]) > 0),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function website(): array
    {
        if (! $this->enabled('Situs')) {
            return [];
        }

        $placeholders = 0;

        try {
            $placeholders = model(SiteQuestionModel::class, false)->like('answer', SiteQuestionModel::PLACEHOLDER)->countAllResults();
        } catch (Throwable) {
            // Table missing (module not migrated yet): nothing to complete.
        }

        return [
            $this->step('site-answers', 'Website', 'Lengkapi jawaban tanya jawab', $placeholders > 0 ? $placeholders . ' jawaban masih berisi [ISI SEKOLAH …] dan belum tampil.' : 'Semua jawaban sudah diisi.', 'admin/tanya-jawab', 'situs.kelola', $placeholders === 0, false),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function accounts(): array
    {
        return [
            $this->step('acc-admin', 'Akun petugas', 'Buat akun Admin Sekolah', 'Admin Sekolah mengatur sekolah dan menambah akun panitia.', 'admin/pengguna/tambah', 'pengguna.kelola-admin', $this->usersIn('admin') > 0),
            $this->step('acc-panitia', 'Akun petugas', 'Buat akun panitia', 'Panitia memeriksa berkas, mencetak kartu, dan mencatat kehadiran.', 'admin/pengguna/tambah', 'pengguna.create', $this->usersIn('panitia') > 0),
            $this->step('acc-2fa', 'Akun petugas', 'Nyalakan verifikasi dua langkah di akun Anda', 'Akun tetap aman walau kata sandi diketahui orang lain.', 'admin/profil/verifikasi-dua-langkah', 'dasbor.view', auth()->loggedIn() && service('twoFactor')->isEnabled((int) auth()->id()), false),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function system(): array
    {
        $max     = ServerCheck::maxUploadBytes();
        $largest = 0;

        try {
            $largest = (int) (db_connect()->table('document_types')->selectMax('max_size_kb', 'kb')->where('is_active', 1)->where('deleted_at', null)->get()->getRow()->kb ?? 0) * 1024;
        } catch (Throwable) {
            // No document types table yet.
        }

        return [
            $this->step('sys-upload', 'Server', 'Periksa batas unggah server', 'Batas unggah PHP harus sama atau lebih besar dari batas berkas terbesar.', 'admin/pengaturan/kondisi-server', 'sistem.server', $max === 0 || $max >= $largest),
        ];
    }

    /**
     * @return array{key: string, group: string, title: string, hint: string, url: string, permission: string, done: bool, required: bool}
     */
    private function step(string $key, string $group, string $title, string $hint, string $url, string $permission, bool $done, bool $required = true): array
    {
        return compact('key', 'group', 'title', 'hint', 'url', 'permission', 'done', 'required');
    }

    private function enabled(string $module): bool
    {
        return service('modules')->isEnabled($module);
    }

    /**
     * @param array<string, mixed> $where
     */
    private function count(string $table, array $where): int
    {
        try {
            $db = db_connect();

            return $db->tableExists($table) ? $db->table($table)->where($where)->countAllResults() : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    private function usersIn(string $group): int
    {
        $tables = config('Auth')->tables;

        return model(UserModel::class, false)
            ->whereIn('id', static fn ($sub) => $sub->select('user_id')->from($tables['groups_users'])->where('group', $group))
            ->countAllResults();
    }

    private function anySocial(): bool
    {
        foreach (['socialInstagram', 'socialFacebook', 'socialYoutube', 'socialTiktok'] as $key) {
            if (trim((string) setting('School.' . $key)) !== '') {
                return true;
            }
        }

        return false;
    }
}
