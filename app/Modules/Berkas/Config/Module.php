<?php

/** Shown on the Fitur page (Pengaturan → Fitur). */
return [
    'label'       => 'Berkas Pendaftar',
    'description' => 'Daftar berkas wajib, unggah berkas, dan pemeriksaan berkas oleh panitia.',
    'icon'        => 'inbox',
    // Modules that must be on for this one to work (ModuleRegistry::isEnabled).
    'requires' => ['Pendaftaran'],
];
