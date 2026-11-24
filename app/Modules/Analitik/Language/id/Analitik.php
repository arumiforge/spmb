<?php

/** Visitor statistics (module Analitik). Friendly Indonesian, see the spmb-copy skill. */
return [
    'title'        => 'Pengunjung website',
    'intro'        => 'Berapa orang membuka website sekolah dan halaman mana yang paling sering dibuka.',
    'menu'         => 'Pengunjung',
    'visitors'     => 'Pengunjung',
    'visitorsUnit' => 'pengunjung',
    'views'        => 'Halaman dibuka',
    'viewsCount'   => '{0} halaman dibuka',
    'today'        => 'Hari ini',
    'yesterday'    => 'Kemarin',
    'week'         => '7 hari terakhir',
    'month'        => '30 hari terakhir',
    'total'        => 'Sejak awal',
    'tilesLabel'   => 'Ringkasan pengunjung',

    'chartTitle'   => 'Pengunjung per hari',
    'rangeLabel'   => 'Pilih rentang waktu',
    'rangeDays'    => '{0} hari',
    'chartSummary' => 'Grafik {0} hari terakhir: {1} pengunjung dan {2} halaman dibuka. Hari paling ramai {3} dengan {4} pengunjung.',
    'chartEmpty'   => 'Grafik {0} hari terakhir: belum ada pengunjung.',
    'tableToggle'  => 'Lihat angka per hari',
    'colDate'      => 'Tanggal',

    'pagesTitle' => 'Halaman paling sering dibuka',
    'pagesIntro' => '30 hari terakhir.',
    'pagesEmpty' => 'Belum ada halaman yang dibuka dalam 30 hari terakhir.',
    'colPage'    => 'Halaman',
    'colViews'   => 'Dibuka',
    'pageOther'  => 'Halaman lain',

    'emptyTitle' => 'Belum ada pengunjung',
    'emptyText'  => 'Angka akan muncul setelah ada orang yang membuka website sekolah. Kunjungan petugas yang sedang masuk tidak dihitung.',

    'howTitle' => 'Cara menghitung',
    'howItems' => [
        'Setiap halaman website yang dibuka dihitung satu kali sebagai "halaman dibuka".',
        'Satu orang dihitung satu kali sebagai pengunjung setiap hari, walaupun membuka banyak halaman. Jika datang lagi besok, ia dihitung lagi.',
        'Penghitungan dilakukan tanpa menyimpan data pribadi. Alamat internet dan jenis perangkat hanya diubah menjadi kode acak yang berganti setiap hari, lalu kode itu dihapus.',
        'Mesin pencari, program otomatis, dan petugas yang sedang masuk ke dasbor tidak dihitung. Halaman dasbor dan halaman masuk juga tidak dihitung.',
    ],

    'settingsTitle'     => 'Pengaturan statistik',
    'showInFooter'      => 'Tampilkan jumlah pengunjung di bagian bawah website',
    'showInFooterHelp'  => 'Pengunjung hari ini, bulan ini, dan sejak awal tampil kecil di bagian paling bawah setiap halaman website.',
    'retentionDays'     => 'Simpan data per halaman selama (hari)',
    'retentionDaysHelp' => 'Jumlah pengunjung per hari selalu disimpan. Angka per halaman yang lebih lama dari ini dihapus otomatis. Bawaan: 400 hari.',
    'save'              => 'Simpan pengaturan',
    'settingsSaved'     => 'Pengaturan statistik pengunjung berhasil disimpan.',
    'settingsAudit'     => 'Pengaturan statistik pengunjung diubah',

    // Dashboard panel
    'dashTitle' => 'Pengunjung website',
    'dashOpen'  => 'Lihat statistik',
    'dashSpark' => 'Pengunjung 14 hari terakhir: {0} orang, paling ramai {1} orang dalam sehari.',

    // Public footer
    'footerToday' => 'Pengunjung hari ini',
    'footerMonth' => 'bulan ini',
    'footerTotal' => 'total',

    // Friendly names of public pages (stored path => name). "*" stands for a hidden token or number.
    'pages' => [
        ''                    => 'Beranda',
        'daftar'              => 'Daftar',
        'daftar/selesai'      => 'Pendaftaran selesai',
        'cek-status'          => 'Cek status',
        'cek-status/data'     => 'Cek status: data pendaftar',
        'cek-status/ubah'     => 'Cek status: ubah data',
        'cek-status/berkas'   => 'Cek status: berkas',
        'cek-status/berkas/*' => 'Cek status: lihat berkas',
        'persyaratan'         => 'Persyaratan',
        'jadwal'              => 'Jadwal',
        'tanya-jawab'         => 'Tanya jawab',
        'panduan'             => 'Panduan',
        'panduan/calon-murid' => 'Panduan calon murid',
        'kartu/*'             => 'Periksa kartu pendaftaran',
    ],
];
