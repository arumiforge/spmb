<?php

return [
    ['label' => 'Data Pendaftar', 'url' => 'admin/pendaftar', 'icon' => 'users', 'permission' => 'pendaftar.view', 'group' => 'Pendaftaran', 'order' => 10],
    ['label' => 'Tambah Pendaftar', 'url' => 'admin/pendaftar/tambah', 'icon' => 'plus', 'permission' => 'pendaftar.create', 'group' => 'Pendaftaran', 'order' => 20],
    ['label' => 'Gelombang', 'url' => 'admin/pengaturan/gelombang', 'icon' => 'calendar', 'permission' => 'pengaturan.pendaftaran', 'group' => 'Pengaturan', 'order' => 20],
    ['label' => 'Pendaftaran', 'url' => 'admin/pengaturan/pendaftaran', 'icon' => 'cog', 'permission' => 'pengaturan.pendaftaran', 'group' => 'Pengaturan', 'order' => 15],
];
