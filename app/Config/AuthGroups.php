<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * Public registration is disabled; accounts are created by an admin who
     * picks the role. This fallback is the least-privileged staff role.
     */
    public string $defaultGroup = 'panitia';

    /**
     * --------------------------------------------------------------------
     * Groups (roles)
     * --------------------------------------------------------------------
     * Titles and descriptions are shown to school staff, so they are in
     * plain Indonesian. See docs/requirements.md section 1.
     *
     * Only three roles (D-063): Admin Sekolah and Panitia for school staff,
     * and Siswa, the no-password applicant access (not a Shield group, D-052).
     * Super Admin is the developer's technical account.
     *
     * @var array<string, array<string, string>>
     */
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Pengelola sistem. Akses penuh, termasuk pengaturan teknis.',
        ],
        'admin' => [
            'title'       => 'Admin Sekolah',
            'description' => 'Mengatur data sekolah, gelombang, jadwal tes, pengguna, dan semua data pendaftar.',
        ],
        'panitia' => [
            'title'       => 'Panitia',
            'description' => 'Mengurus semua pekerjaan panitia: pendaftar offline, pemeriksaan berkas, kartu, kehadiran tes, daftar ulang, seragam, dan pesan dari orang tua.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * Keys are "<module>.<action>". Modules declare their own keys here when
     * they are added. Descriptions are shown to admins in Indonesian.
     */
    public array $permissions = [
        'dasbor.view' => 'Membuka dasbor',

        'sistem.modul'    => 'Menyalakan atau mematikan fitur',
        'sistem.cadangan' => 'Membuat dan memulihkan cadangan data',
        'sistem.log'      => 'Melihat log sistem',
        'sistem.keamanan' => 'Mengatur kebijakan keamanan (verifikasi dua langkah per peran)',

        'pengaturan.sekolah'     => 'Mengubah identitas dan tampilan sekolah',
        'pengaturan.pendaftaran' => 'Mengatur tahun ajaran, gelombang, dan berkas wajib',

        'pengguna.view'         => 'Melihat daftar pengguna',
        'pengguna.create'       => 'Menambah pengguna',
        'pengguna.edit'         => 'Mengubah pengguna',
        'pengguna.delete'       => 'Menghapus pengguna',
        'pengguna.kelola-admin' => 'Mengelola akun Super Admin',

        'audit.view' => 'Melihat riwayat perubahan data',

        'pendaftar.view'       => 'Melihat data pendaftar',
        'pendaftar.create'     => 'Menambah pendaftar (offline)',
        'pendaftar.edit'       => 'Mengubah data pendaftar',
        'pendaftar.delete'     => 'Menghapus data pendaftar',
        'pendaftar.verifikasi' => 'Memeriksa dan memverifikasi berkas',
        'pendaftar.export'     => 'Mengunduh data pendaftar (Excel/PDF)',
        'pendaftar.import'     => 'Mengunggah data pendaftar dari Excel',

        'kartu.cetak' => 'Mencetak dan memeriksa kartu pendaftaran',

        'referensi.kelola' => 'Mengelola daftar sekolah asal dan mengimpor data sekolah',

        'tes.atur'      => 'Mengatur jadwal, ruang, dan kuota tes',
        'tes.kehadiran' => 'Mencatat kehadiran dan menjadwalkan ulang tes',

        'daftar-ulang.proses' => 'Memproses daftar ulang',
        'seragam.proses'      => 'Mengubah status seragam',

        'laporan.view'   => 'Melihat laporan',
        'laporan.export' => 'Mengunduh laporan',

        'situs.kelola'  => 'Mengubah isi halaman website dan pengumuman',
        'analitik.view' => 'Melihat statistik pengunjung website',
        'chat.balas'    => 'Membalas pesan di layanan chat',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     */
    public array $matrix = [
        'superadmin' => [
            'dasbor.*', 'sistem.*', 'pengaturan.*', 'pengguna.*', 'audit.*',
            'pendaftar.*', 'kartu.*', 'referensi.*', 'tes.*', 'daftar-ulang.*', 'seragam.*',
            'laporan.*', 'situs.*', 'analitik.*', 'chat.*',
        ],
        'admin' => [
            'dasbor.view', 'pengaturan.*',
            'pengguna.view', 'pengguna.create', 'pengguna.edit', 'pengguna.delete',
            'audit.view',
            'pendaftar.*', 'kartu.*', 'referensi.*', 'tes.*', 'daftar-ulang.*', 'seragam.*',
            'laporan.*', 'situs.*', 'analitik.*', 'chat.*',
        ],
        'panitia' => [
            'dasbor.view', 'pendaftar.view', 'pendaftar.create', 'pendaftar.edit',
            'pendaftar.verifikasi', 'kartu.cetak', 'chat.balas',
            // D-051, D-063: one committee role for registration, test attendance, re-registration and uniform pickup.
            'tes.kehadiran', 'daftar-ulang.proses', 'seragam.proses',
        ],
    ];
}
