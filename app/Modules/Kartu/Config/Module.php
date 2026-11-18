<?php

/** Shown on the Fitur page (Pengaturan → Fitur). */
return [
    'label'       => 'Kartu Pendaftaran',
    'description' => 'Kartu pendaftaran PDF dengan kode QR dan halaman pemeriksaan kartu.',
    'icon'        => 'key',
    // Modules that must be on for this one to work (ModuleRegistry::isEnabled).
    'requires' => ['Pendaftaran'],
];
