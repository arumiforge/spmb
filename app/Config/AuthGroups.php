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
    public string $defaultGroup = 'petugas-seragam';

    /**
     * --------------------------------------------------------------------
     * Groups (roles)
     * --------------------------------------------------------------------
     * Titles and descriptions are shown to school staff, so they are in
     * plain Indonesian. See docs/requirements.md section 1.
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
        'kepsek' => [
            'title'       => 'Kepala Sekolah',
            'description' => 'Melihat ringkasan dan laporan pendaftaran.',
        ],
        'panitia' => [
            'title'       => 'Panitia Pendaftaran',
            'description' => 'Menambah pendaftar offline, memeriksa berkas, dan mencetak kartu.',
        ],
        'petugas-tes' => [
            'title'       => 'Petugas Tes',
            'description' => 'Mencatat kehadiran tes pemetaan.',
        ],
        'petugas-daftar-ulang' => [
            'title'       => 'Petugas Daftar Ulang',
            'description' => 'Memproses daftar ulang calon murid.',
        ],
        'petugas-seragam' => [
            'title'       => 'Petugas Seragam',
            'description' => 'Mencatat status pengambilan seragam.',
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
        'pengguna.kelola-admin' => 'Mengelola akun Admin Sekolah dan Super Admin',

        'audit.view' => 'Melihat riwayat perubahan data',

        'pendaftar.view'       => 'Melihat data pendaftar',
        'pendaftar.create'     => 'Menambah pendaftar (offline)',
        'pendaftar.edit'       => 'Mengubah data pendaftar',
        'pendaftar.delete'     => 'Menghapus data pendaftar',
        'pendaftar.verifikasi' => 'Memeriksa dan memverifikasi berkas',
        'pendaftar.export'     => 'Mengunduh data pendaftar (Excel/PDF)',
        'pendaftar.import'     => 'Mengunggah data pendaftar dari Excel',

        'kartu.cetak' => 'Mencetak kartu pendaftaran',

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
            'pendaftar.*', 'kartu.*', 'tes.*', 'daftar-ulang.*', 'seragam.*',
            'laporan.*', 'situs.*', 'analitik.*', 'chat.*',
        ],
        'admin' => [
            'dasbor.view', 'pengaturan.*',
            'pengguna.view', 'pengguna.create', 'pengguna.edit', 'pengguna.delete',
            'audit.view',
            'pendaftar.*', 'kartu.*', 'tes.*', 'daftar-ulang.*', 'seragam.*',
            'laporan.*', 'situs.*', 'analitik.*', 'chat.*',
        ],
        'kepsek' => [
            'dasbor.view', 'pendaftar.view', 'laporan.*', 'analitik.view',
        ],
        'panitia' => [
            'dasbor.view', 'pendaftar.view', 'pendaftar.create', 'pendaftar.edit',
            'pendaftar.verifikasi', 'kartu.cetak', 'chat.balas',
        ],
        'petugas-tes' => [
            'dasbor.view', 'pendaftar.view', 'kartu.cetak', 'tes.kehadiran',
        ],
        'petugas-daftar-ulang' => [
            'dasbor.view', 'pendaftar.view', 'daftar-ulang.proses',
        ],
        'petugas-seragam' => [
            'dasbor.view', 'pendaftar.view', 'seragam.proses',
        ],
    ];
}
