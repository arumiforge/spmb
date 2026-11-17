<?php

/**
 * Guide audiences: URL slug => title, short description, Shield group (null = public).
 * Guide files are named after the slug (Guide/<slug>.md); "semua-petugas.md"
 * is added to every staff guide.
 */
return [
    'calon-murid'          => ['title' => 'Calon Murid dan Orang Tua', 'description' => 'Cara mendaftar, mengunggah berkas, dan mencetak kartu.', 'group' => null, 'icon' => 'school'],
    'admin-sekolah'        => ['title' => 'Admin Sekolah', 'description' => 'Mengatur identitas sekolah, pengguna, dan pendaftaran.', 'group' => 'admin', 'icon' => 'cog'],
    'kepala-sekolah'       => ['title' => 'Kepala Sekolah', 'description' => 'Melihat ringkasan dan laporan pendaftaran.', 'group' => 'kepsek', 'icon' => 'chart'],
    'panitia-pendaftaran'  => ['title' => 'Panitia Pendaftaran', 'description' => 'Mendaftarkan calon murid dan memeriksa berkas.', 'group' => 'panitia', 'icon' => 'clipboard'],
    'petugas-tes'          => ['title' => 'Petugas Tes', 'description' => 'Mencatat kehadiran tes pemetaan.', 'group' => 'petugas-tes', 'icon' => 'calendar'],
    'petugas-daftar-ulang' => ['title' => 'Petugas Daftar Ulang', 'description' => 'Memproses daftar ulang calon murid.', 'group' => 'petugas-daftar-ulang', 'icon' => 'check'],
    'petugas-seragam'      => ['title' => 'Petugas Seragam', 'description' => 'Mencatat pengambilan seragam.', 'group' => 'petugas-seragam', 'icon' => 'users'],
    'super-admin'          => ['title' => 'Super Admin', 'description' => 'Pengaturan teknis dan keamanan sistem.', 'group' => 'superadmin', 'icon' => 'shield'],
];
