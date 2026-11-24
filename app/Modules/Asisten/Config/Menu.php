<?php

/** Sidebar entries of the Asisten module (D-110). */
return [
    ['label' => 'Tanya Asisten', 'url' => 'admin/asisten', 'icon' => 'message-circle-question-mark', 'permission' => 'asisten.atur', 'group' => 'Website', 'order' => 50],
    ['label' => 'Pertanyaan pengunjung', 'url' => 'admin/asisten/pertanyaan', 'icon' => 'inbox', 'permission' => 'asisten.atur', 'group' => 'Website', 'order' => 55],
    ['label' => 'Koneksi AI', 'url' => 'admin/asisten/ai', 'icon' => 'sparkles', 'permission' => 'asisten.ai', 'group' => 'Pengaturan', 'order' => 96],
];
