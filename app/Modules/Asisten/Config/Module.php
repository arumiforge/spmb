<?php

/** Shown on the Fitur page (Pengaturan → Fitur). */
return [
    'label'       => 'Tanya Asisten',
    'description' => 'Asisten otomatis di website: pilihan cepat, jawaban dari Tanya Jawab, dan (jika dinyalakan) jawaban AI dari isi website sekolah.',
    'icon'        => 'message-circle-question-mark',
    // Q&A, page links and the answer renderer come from the Situs module.
    'requires' => ['Situs'],
];
