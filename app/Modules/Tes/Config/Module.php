<?php

/** Shown on the Fitur page (Pengaturan → Fitur). */
return [
    'label'       => 'Tes Penempatan',
    'description' => 'Jadwal, ruang, dan kuota tes; penjadwalan otomatis; kehadiran lewat kode QR; riwayat perubahan jadwal.',
    'icon'        => 'calendar',
    // Modules that must be on for this one to work (ModuleRegistry::isEnabled).
    'requires' => ['Pendaftaran'],
];
