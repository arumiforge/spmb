<?php

/**
 * Shared UI strings of the Tes module (coordinator). English keys, friendly
 * Indonesian values (spmb-copy). Each work package keeps its own strings in
 * TesJadwal.php (A), TesPenjadwalan.php (B) and TesKehadiran.php (C).
 * Attendance status labels: lang('Status.test.<code>').
 */
return [
    'title'      => 'Tes Penempatan',
    'breadcrumb' => 'Tes',

    // Facts of a session, used on several pages.
    'date'          => 'Tanggal',
    'time'          => 'Jam',
    'room'          => 'Ruang',
    'location'      => 'Lokasi',
    'session'       => 'Sesi',
    'quota'         => 'Kuota',
    'filled'        => 'Terisi',
    'free'          => 'Sisa kursi',
    'wave'          => 'Gelombang',
    'allWaves'      => 'Semua gelombang',
    'status'        => 'Status tes',
    'schedule'      => 'Jadwal Tes',
    'seat'          => 'No. urut',
    'instructions'  => 'Yang perlu disiapkan',
    'notScheduled'  => 'Belum dijadwalkan',
    'scheduleLater' => 'Jadwal tes akan diumumkan oleh panitia. Silakan cek halaman ini lagi nanti.',

    // Kinds of schedule change (test_schedule_changes.kind).
    'change' => [
        'allocated' => 'Dijadwalkan otomatis',
        'assigned'  => 'Dijadwalkan petugas',
        'moved'     => 'Dipindah jadwal',
        'removed'   => 'Dikeluarkan dari jadwal',
    ],
    'system' => 'Sistem',
];
