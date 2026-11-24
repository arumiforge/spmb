<?php

/** Sidebar entries of the Situs module. */
return [
    ['label' => 'Tanya jawab', 'url' => 'admin/tanya-jawab', 'icon' => 'message-circle-question-mark', 'permission' => 'situs.kelola', 'group' => 'Website', 'order' => 10],
    ['label' => 'Berita singkat', 'url' => 'admin/berita-singkat', 'icon' => 'megaphone', 'permission' => 'situs.kelola', 'group' => 'Website', 'order' => 20],
    ['label' => 'Popup beranda', 'url' => 'admin/popup', 'icon' => 'app-window', 'permission' => 'situs.kelola', 'group' => 'Website', 'order' => 30],
    ['label' => 'Tautan bawah website', 'url' => 'admin/tautan-footer', 'icon' => 'link', 'permission' => 'situs.kelola', 'group' => 'Website', 'order' => 40],
];
