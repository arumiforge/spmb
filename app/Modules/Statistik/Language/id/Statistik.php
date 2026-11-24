<?php

/** Statistik (applicant statistics for school planning, D-111). Friendly Indonesian, see the spmb-copy skill. */
return [
    'title' => 'Statistik pendaftar',
    'intro' => 'Dari mana calon murid berasal dan seperti apa latar belakang keluarganya, untuk bahan perencanaan sekolah. Tidak ada nama atau data pribadi di halaman ini.',
    'menu'  => 'Statistik',
    'group' => 'Laporan',

    // Filters
    'filterTitle'    => 'Pilih data',
    'filterYear'     => 'Tahun ajaran',
    'filterWave'     => 'Gelombang',
    'filterWaveAll'  => 'Semua gelombang',
    'filterWaveNone' => 'Tanpa gelombang',
    'filterStatus'   => 'Status pendaftar',
    'statusOptions'  => [
        'aktif'      => 'Semua, kecuali yang dibatalkan',
        'registered' => 'Terdaftar',
        'verified'   => 'Sudah diperiksa',
        'cancelled'  => 'Dibatalkan saja',
        'semua'      => 'Semua, termasuk yang dibatalkan',
    ],
    'filterApply' => 'Tampilkan',
    'filterReset' => 'Kembalikan',
    'filterNow'   => 'Menampilkan: {0}.',
    'jumpLabel'   => 'Langsung ke bagian',

    // Summary tiles
    'tilesLabel'   => 'Ringkasan',
    'tileTotal'    => 'Jumlah pendaftar',
    'tileOnline'   => 'Daftar online',
    'tileOffline'  => 'Daftar di sekolah',
    'tileComplete' => 'Berkas lengkap',
    'tileOf'       => '{0} dari semua pendaftar',

    // Privacy
    'small'        => '< 3',
    'smallLabel'   => 'kurang dari 3',
    'privacyTitle' => 'Menjaga data pribadi',
    'privacyText'  => 'Kelompok yang berisi 1 atau 2 orang ditulis "< 3" supaya tidak ada calon murid yang bisa dikenali dari angkanya. Persentasenya juga tidak ditampilkan.',

    // Groups of sections
    'groupOrigin'       => 'Asal calon murid',
    'groupFamily'       => 'Orang tua dan wali',
    'groupStudent'      => 'Calon murid',
    'groupRegistration' => 'Pendaftaran',
    'groupCross'        => 'Perbandingan',
    'download'          => 'Unduh CSV',
    'downloadLabel'     => 'Unduh tabel bagian {0} sebagai CSV',

    // Common table words
    'colCount'     => 'Jumlah',
    'colPercent'   => 'Persen',
    'colTotal'     => 'Jumlah',
    'rowTotal'     => 'Semua',
    'tableToggle'  => 'Lihat tabel',
    'tableAll'     => 'Lihat tabel lengkap ({0} baris)',
    'otherGroups'  => 'Lainnya ({0} kelompok)',
    'notFilled'    => 'Belum diisi',
    'noRows'       => 'Belum ada data untuk pilihan ini.',
    'chartSummary' => '{0}. Terbanyak: {1}.',
    'chartTopList' => '{0} ({1})',
    'chartNoData'  => '{0}: belum ada data.',
    'openHint'     => 'Tekan batang di grafik, atau nama di tabel, untuk melihat rinciannya.',

    // Location
    'locationTitle' => 'Tempat tinggal',
    'locationIntro' => 'Jumlah calon murid menurut alamat rumahnya.',
    'locationAll'   => 'Semua kabupaten/kota',
    'locationTrail' => 'Tingkat wilayah',
    'levelRegency'  => 'Kabupaten/kota',
    'levelDistrict' => 'Kecamatan',
    'levelVillage'  => 'Desa/kelurahan',
    'locationChart' => 'Calon murid per {0}',
    'locationIn'    => 'Calon murid per {0} di {1}',

    // Origin schools
    'schoolTitle'      => 'Sekolah asal',
    'schoolIntro'      => 'Dari sekolah mana calon murid datang, menurut letak sekolahnya.',
    'schoolModeLabel'  => 'Kelompokkan sekolah asal',
    'schoolModes'      => ['kecamatan' => 'Per kecamatan', 'kabupaten' => 'Per kabupaten/kota', 'nama' => 'Per sekolah'],
    'schoolHeader'     => ['kecamatan' => 'Kecamatan sekolah', 'kabupaten' => 'Kabupaten/kota sekolah', 'nama' => 'Sekolah'],
    'schoolChart'      => ['kecamatan' => 'Calon murid per kecamatan sekolah asal', 'kabupaten' => 'Calon murid per kabupaten/kota sekolah asal', 'nama' => 'Calon murid per sekolah asal'],
    'schoolPlace'      => 'Letak',
    'schoolOther'      => 'Sekolah lain (belum ada di daftar)',
    'schoolOtherNote'  => 'Ditulis sendiri, belum ada di daftar',
    'levelTitle'       => 'SD atau MI',
    'levelIntro'       => 'Jenjang sekolah asal.',
    'levelHeader'      => 'Jenjang',
    'levelUnknown'     => 'Jenjang belum tercatat',
    'ownershipTitle'   => 'Negeri atau swasta',
    'ownershipIntro'   => 'Status sekolah asal.',
    'ownershipHeader'  => 'Status sekolah',
    'ownership'        => ['negeri' => 'Negeri', 'swasta' => 'Swasta'],
    'ownershipUnknown' => 'Status belum tercatat',

    // Family
    'fatherJobTitle'    => 'Pekerjaan ayah',
    'motherJobTitle'    => 'Pekerjaan ibu',
    'jobHeader'         => 'Pekerjaan',
    'fatherIncomeTitle' => 'Penghasilan ayah',
    'motherIncomeTitle' => 'Penghasilan ibu',
    'incomeIntro'       => 'Penghasilan per bulan, urut dari yang paling kecil.',
    'incomeHeader'      => 'Penghasilan per bulan',
    'guardianTitle'     => 'Ada wali',
    'guardianIntro'     => 'Calon murid yang tinggal dengan atau diasuh oleh wali.',
    'guardianHeader'    => 'Wali',
    'guardian'          => ['1' => 'Ada wali', '0' => 'Tidak ada wali'],

    // Student
    'genderTitle'    => 'Jenis kelamin',
    'genderHeader'   => 'Jenis kelamin',
    'religionTitle'  => 'Agama',
    'religionHeader' => 'Agama',
    'ageTitle'       => 'Usia saat mendaftar',
    'ageIntro'       => 'Dihitung dari tanggal lahir sampai tanggal mendaftar.',
    'ageHeader'      => 'Usia',
    'ageUnder'       => 'Di bawah {0} tahun',
    'ageYears'       => '{0} tahun',
    'ageOver'        => '{0} tahun atau lebih',

    // Registration
    'timeTitle'       => 'Pendaftar dari waktu ke waktu',
    'timeModeLabel'   => 'Hitung per',
    'timeModes'       => ['hari' => 'Per hari', 'minggu' => 'Per minggu'],
    'timeHeader'      => ['hari' => 'Tanggal', 'minggu' => 'Minggu'],
    'timeChart'       => ['hari' => 'Pendaftar per hari', 'minggu' => 'Pendaftar per minggu'],
    'timeSummary'     => '{0} dari {1} sampai {2}. Paling ramai {3}.',
    'waveTitle'       => 'Per gelombang',
    'waveHeader'      => 'Gelombang',
    'waveNone'        => 'Tanpa gelombang',
    'methodTitle'     => 'Cara mendaftar',
    'methodHeader'    => 'Cara mendaftar',
    'documentsTitle'  => 'Kelengkapan berkas',
    'documentsHeader' => 'Berkas',

    // Cross-tabs
    'crossIncomeLabel' => 'Penghasilan siapa',
    'crossIncome'      => ['ayah' => 'Penghasilan ayah', 'ibu' => 'Penghasilan ibu'],
    'crossWaveTitle'   => 'Penghasilan per gelombang',
    'crossWaveIntro'   => 'Sebaran penghasilan orang tua di setiap gelombang. Batang menunjukkan bagian (persen) setiap kelompok penghasilan.',
    'crossDistTitle'   => 'Penghasilan per kecamatan',
    'crossDistIntro'   => 'Sebaran penghasilan orang tua di kecamatan tempat tinggal dengan calon murid terbanyak.',
    'crossOtherDist'   => 'Kecamatan lain',
    'crossSummary'     => '{0}, {1} kelompok.',

    // Empty state
    'emptyTitle'       => 'Belum ada pendaftar',
    'emptyText'        => 'Statistik akan muncul setelah ada calon murid yang mendaftar pada tahun ajaran ini.',
    'emptyFilterTitle' => 'Tidak ada pendaftar untuk pilihan ini',
    'emptyFilterText'  => 'Coba pilih tahun ajaran, gelombang, atau status yang lain.',

    // CSV
    'csvFile'    => 'statistik-{0}-{1}.csv',
    'csvFilters' => 'Pilihan data',
    'csvMade'    => 'Dibuat',
];
