<?php

/** Shown on the Fitur page (Pengaturan → Fitur). */
return [
    'label'       => 'Statistik Pendaftar',
    'description' => 'Asal tempat tinggal dan sekolah calon murid, latar belakang orang tua, usia, dan pendaftaran dari waktu ke waktu, untuk perencanaan sekolah. Tanpa nama atau data pribadi.',
    'icon'        => 'chart-column',
    // Modules that must be on for this one to work (ModuleRegistry::isEnabled).
    'requires' => ['Pendaftaran'],
];
