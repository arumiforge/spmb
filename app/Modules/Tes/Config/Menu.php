<?php

// Sidebar entries of the Tes module (docs/phase-3-plan.md). Owned by the coordinator.
return [
    ['label' => 'Kehadiran Tes', 'url' => 'admin/tes/kehadiran', 'icon' => 'check', 'permission' => 'tes.kehadiran', 'group' => 'Tes', 'order' => 10],
    ['label' => 'Peserta Tes', 'url' => 'admin/tes/peserta', 'icon' => 'users', 'permission' => 'tes.kehadiran', 'group' => 'Tes', 'order' => 20],
    ['label' => 'Penjadwalan', 'url' => 'admin/tes/penjadwalan', 'icon' => 'clipboard', 'permission' => 'tes.atur', 'group' => 'Tes', 'order' => 30],
    ['label' => 'Sesi Tes', 'url' => 'admin/tes/sesi', 'icon' => 'clock', 'permission' => 'tes.atur', 'group' => 'Tes', 'order' => 40],
    ['label' => 'Kalender Tes', 'url' => 'admin/tes/kalender', 'icon' => 'calendar', 'permission' => 'tes.atur', 'group' => 'Tes', 'order' => 50],
    ['label' => 'Ruang Tes', 'url' => 'admin/tes/ruang', 'icon' => 'home', 'permission' => 'tes.atur', 'group' => 'Tes', 'order' => 60],
    ['label' => 'Riwayat Jadwal', 'url' => 'admin/tes/riwayat-jadwal', 'icon' => 'inbox', 'permission' => 'tes.kehadiran', 'group' => 'Tes', 'order' => 70],
    ['label' => 'Tes', 'url' => 'admin/pengaturan/tes', 'icon' => 'cog', 'permission' => 'tes.atur', 'group' => 'Pengaturan', 'order' => 28],
];
