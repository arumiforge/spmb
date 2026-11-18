<?php

/**
 * Labels of shared status codes (stored in English in the database, shown in
 * Indonesian). Used by Pendaftaran, Berkas, Kartu and the public pages; see
 * status_badge_for() in ui_helper. Friendly Indonesian, see the spmb-copy skill.
 */
return [
    'applicant' => [
        'registered' => 'Terdaftar',
        'verified'   => 'Sudah diperiksa',
        'cancelled'  => 'Dibatalkan',
    ],
    'documents' => [
        'incomplete' => 'Belum lengkap',
        'pending'    => 'Menunggu diperiksa',
        'to_follow'  => 'Ada yang menyusul',
        'complete'   => 'Lengkap',
    ],
    'document' => [
        'pending'   => 'Menunggu diperiksa',
        'valid'     => 'Sesuai',
        'invalid'   => 'Perlu diganti',
        'to_follow' => 'Menyusul',
    ],
    'method' => [
        'online'  => 'Online',
        'offline' => 'Di sekolah',
    ],
    'gender' => [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ],
];
