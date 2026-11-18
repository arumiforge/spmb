<?php

/** UI strings of the Kartu module. English keys, friendly Indonesian values (spmb-copy). */
return [
    'title' => 'Kartu Pendaftaran',

    // Card facts (PDF, check page, staff lookup).
    'number'           => 'Nomor Pendaftaran',
    'name'             => 'Nama',
    'wave'             => 'Gelombang',
    'school'           => 'Asal Sekolah',
    'method'           => 'Cara Daftar',
    'testStatus'       => 'Jadwal Tes',
    'testNotScheduled' => 'Belum dijadwalkan',
    'academicYear'     => 'Tahun Ajaran',

    // PDF.
    'pdfTitle'        => 'Kartu Pendaftaran {0}',
    'pdfAcademicYear' => 'Tahun Ajaran {0}',
    'pdfScan'         => 'Pindai untuk memeriksa kartu',
    'pdfPhoto'        => 'Tempel pas foto 3 x 4',
    'pdfPrinted'      => 'Dicetak {0}',
    'pdfCut'          => 'gunting di sini',
    'fileName'        => 'kartu-pendaftaran-{0}.pdf',

    // Public check page /kartu/<token>.
    'checkTitle'    => 'Pemeriksaan Kartu',
    'validTitle'    => 'Kartu sah',
    'validText'     => 'Kartu ini terdaftar di sistem pendaftaran {0}.',
    'invalidTitle'  => 'Kartu tidak berlaku',
    'invalidText'   => 'Kartu ini sudah diganti dengan kartu baru atau tidak dikenal. Mintalah calon murid menunjukkan kartu terbaru, atau hubungi panitia.',
    'cancelledText' => 'Pendaftaran calon murid ini sudah dibatalkan. Hubungi panitia jika ada pertanyaan.',
    'openApplicant' => 'Buka Data Pendaftar',
    'checkPrivacy'  => 'Halaman ini hanya menampilkan data seperlunya untuk memeriksa kartu.',
    'tooManyChecks' => 'Terlalu banyak pemeriksaan dalam waktu singkat. Tunggu sebentar lalu coba lagi.',
    'tooManyTitle'  => 'Coba lagi sebentar',

    // Applicant download /cek-status/kartu.
    'download'           => 'Unduh Kartu',
    'downloadHelp'       => 'Cetak kartu di kertas A4, lalu gunting di garis putus-putus. Bawa kartu saat tes dan daftar ulang.',
    'notYetTitle'        => 'Kartu belum bisa diunduh',
    'notYetText'         => 'Kartu bisa diunduh setelah berkas diperiksa panitia. Silakan cek lagi nanti.',
    'cancelledTitle'     => 'Pendaftaran dibatalkan',
    'cancelledApplicant' => 'Pendaftaran Anda sudah dibatalkan, jadi kartu tidak bisa diunduh. Hubungi panitia jika ada pertanyaan.',
    'backToStatus'       => 'Kembali ke Cek Status',
    'needCheck'          => 'Silakan cek status pendaftaran dulu untuk mengunduh kartu.',

    // Staff: panel, print, renew.
    'panelTitle'     => 'Kartu Pendaftaran',
    'print'          => 'Cetak Kartu',
    'renew'          => 'Buat Kartu Baru',
    'renewConfirm'   => 'Buat kartu baru untuk {0}? Kartu lama tidak berlaku lagi dan kode QR-nya akan ditolak.',
    'renewed'        => 'Kartu baru sudah dibuat. Kartu lama tidak berlaku lagi. Cetak kartu baru untuk calon murid.',
    'renewHelp'      => 'Buat kartu baru jika kartu hilang atau dipakai orang lain.',
    'cardActive'     => 'Kartu aktif',
    'cardNone'       => 'Kartu belum pernah dicetak atau diunduh.',
    'cardIssuedAt'   => 'Dibuat {0}',
    'cardLastSeen'   => 'Terakhir dipindai {0}',
    'cardNeverSeen'  => 'Belum pernah dipindai',
    'applicantRule'  => 'Calon murid bisa mengunduh kartu ini sendiri.',
    'applicantWaits' => 'Calon murid belum bisa mengunduh kartu sendiri (menunggu pemeriksaan panitia).',
    'cancelledStaff' => 'Pendaftaran ini dibatalkan, jadi kartu tidak bisa dicetak.',
    'notFound'       => 'Data pendaftar tidak ditemukan.',

    // Staff lookup /admin/kartu.
    'lookupTitle'         => 'Periksa Kartu',
    'lookupIntro'         => 'Pindai kode QR di kartu dengan kamera HP, atau ketik nomor pendaftaran untuk mencari calon murid.',
    'lookupLabel'         => 'Kode kartu atau nomor pendaftaran',
    'lookupHelp'          => 'Tempel alamat dari kode QR, atau ketik nomor seperti PMB-2027-0001.',
    'lookupButton'        => 'Periksa',
    'lookupNotFound'      => 'Kartu atau nomor pendaftaran "{0}" tidak ditemukan. Periksa lagi ketikan Anda.',
    'lookupNotFoundTitle' => 'Tidak ditemukan',
    'lookupRevoked'       => 'Kartu ini sudah tidak berlaku karena sudah dibuat kartu baru.',
    'lookupNoCard'        => 'Calon murid ini belum punya kartu.',
    'lookupResult'        => 'Hasil Pemeriksaan',
    'lookupEmptyTitle'    => 'Belum ada yang diperiksa',
    'lookupEmptyText'     => 'Hasil pemeriksaan akan tampil di sini.',
    'applicantStatus'     => 'Status Pendaftaran',
    'documentStatus'      => 'Status Berkas',

    // Settings /admin/pengaturan/kartu.
    'settingsTitle'        => 'Pengaturan Kartu',
    'settingsIntro'        => 'Atur kapan calon murid bisa mengunduh kartu dan tulisan yang tercetak di kartu.',
    'availableWhen'        => 'Kartu bisa diunduh calon murid',
    'availableWhenHelp'    => 'Panitia tetap bisa mencetak kartu kapan saja.',
    'availableImmediately' => 'Langsung setelah mendaftar',
    'availableVerified'    => 'Setelah data diperiksa panitia',
    'headerText'           => 'Judul kartu',
    'headerTextHelp'       => 'Tercetak di bawah nama sekolah.',
    'footerNote'           => 'Catatan di bagian bawah kartu',
    'footerNoteHelp'       => 'Contoh: Bawa kartu ini saat tes dan daftar ulang.',
    'save'                 => 'Simpan Pengaturan',
    'saved'                => 'Pengaturan kartu berhasil disimpan.',
    'sectionAvailability'  => 'Kapan kartu bisa diunduh',
    'sectionText'          => 'Tulisan di kartu',
    'preview'              => 'Logo dan nama sekolah diambil dari Identitas Sekolah.',
];
