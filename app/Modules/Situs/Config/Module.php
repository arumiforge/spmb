<?php

/** Shown on the Fitur page (Pengaturan → Fitur). */
return [
    'label'       => 'Halaman Informasi',
    'description' => 'Halaman persyaratan dan jadwal gelombang untuk calon murid dan orang tua.',
    'icon'        => 'book',
    // Modules that must be on for this one to work (ModuleRegistry::isEnabled).
    'requires' => ['Pendaftaran'],
];
