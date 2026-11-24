<?php

/** Labels for the audit log viewer (Riwayat), merged by AuditPresenter. API keys are never logged. */
return [
    'subjects' => [
        'asisten_providers' => 'Koneksi AI',
    ],
    'settingGroups' => [
        'Asisten'             => 'Tanya Asisten',
        'AsistenQuickChoices' => 'Pilihan cepat Tanya Asisten',
    ],
    'boolFields' => ['enabled', 'aiEnabled', 'is_active'],
    'fields'     => [
        'enabled'           => 'Tanya Asisten menyala',
        'greeting'          => 'Sapaan pembuka',
        'avatar'            => 'Gambar asisten',
        'retentionDays'     => 'Lama simpan pertanyaan (hari)',
        'aiEnabled'         => 'Jawaban AI menyala',
        'dailyVisitorLimit' => 'Batas jawaban AI per pengunjung per hari',
        'dailySiteLimit'    => 'Batas jawaban AI seluruh website per hari',
        'perMinuteLimit'    => 'Batas pertanyaan per menit',
        'maxLength'         => 'Panjang pertanyaan paling banyak',
        'extraInstructions' => 'Instruksi tambahan untuk AI',
        'choices'           => 'Daftar pilihan cepat',
        'base_url'          => 'Alamat dasar (base URL)',
        'model'             => 'Model',
        'api_key'           => 'API key',
        'timeout_seconds'   => 'Batas waktu (detik)',
        'max_tokens'        => 'Token jawaban paling banyak',
        'temperature'       => 'Temperature',
    ],
];
