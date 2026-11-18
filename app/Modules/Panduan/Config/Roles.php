<?php

/**
 * Guide audiences: URL slug => title, short description, Shield group (null = public).
 * Three roles (D-063): calon murid (Siswa), Admin Sekolah, Panitia; plus Super Admin.
 * Guide files are named after the slug (Guide/<slug>.md); "semua-petugas.md"
 * is added to every staff guide.
 */
return [
    'calon-murid'   => ['title' => 'Calon Murid dan Orang Tua', 'description' => 'Cara mendaftar, mengunggah berkas, dan mencetak kartu.', 'group' => null, 'icon' => 'school'],
    'admin-sekolah' => ['title' => 'Admin Sekolah', 'description' => 'Mengatur identitas sekolah, pengguna, dan pendaftaran.', 'group' => 'admin', 'icon' => 'cog'],
    'panitia'       => ['title' => 'Panitia', 'description' => 'Mendaftarkan calon murid, memeriksa berkas, mencetak kartu, mencatat kehadiran tes, daftar ulang, dan seragam.', 'group' => 'panitia', 'icon' => 'clipboard'],
    'super-admin'   => ['title' => 'Super Admin', 'description' => 'Pengaturan teknis dan keamanan sistem.', 'group' => 'superadmin', 'icon' => 'shield'],
];
