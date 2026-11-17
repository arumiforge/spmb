<?php

/** Labels for the audit log viewer (Riwayat), merged by AuditPresenter. */
return [
    'events' => [
        'card_issued'  => 'Kartu dibuat',
        'card_revoked' => 'Kartu lama dinonaktifkan',
        'card_printed' => 'Kartu dicetak petugas',
    ],
    'eventColors' => [
        'card_issued'  => 'green',
        'card_revoked' => 'yellow',
    ],
    'subjects' => [
        'card_tokens' => 'Kartu',
    ],
    'settingGroups' => [
        'Kartu' => 'Pengaturan Kartu',
    ],
    'fields' => [
        'token'         => 'Kode kartu',
        'revoked_at'    => 'Waktu dinonaktifkan',
        'availableWhen' => 'Kartu bisa dicetak',
        'headerText'    => 'Judul kartu',
        'footerNote'    => 'Catatan di kartu',
    ],
];
