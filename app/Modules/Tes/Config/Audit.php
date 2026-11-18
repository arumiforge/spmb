<?php

/**
 * Labels for the audit log viewer (Riwayat), merged by AuditPresenter.
 * Owned by the coordinator; work packages ask for new events in their PR.
 */
return [
    'events' => [
        'test_allocated'     => 'Penjadwalan tes otomatis',
        'test_assigned'      => 'Jadwal tes diberikan',
        'test_moved'         => 'Jadwal tes dipindah',
        'test_removed'       => 'Dikeluarkan dari jadwal tes',
        'test_attendance'    => 'Kehadiran tes dicatat',
        'sessions_generated' => 'Sesi tes dibuat sekaligus',
    ],
    'eventColors' => [
        'test_allocated'     => 'blue',
        'test_assigned'      => 'blue',
        'test_moved'         => 'yellow',
        'test_removed'       => 'red',
        'test_attendance'    => 'green',
        'sessions_generated' => 'blue',
    ],
    'subjects' => [
        'test_rooms'       => 'Ruang tes',
        'test_holidays'    => 'Hari libur tes',
        'test_sessions'    => 'Sesi tes',
        'test_assignments' => 'Jadwal tes pendaftar',
    ],
    'settingGroups' => [
        'Tes' => 'Pengaturan Tes',
    ],
    'fields' => [
        'capacity'                 => 'Kapasitas',
        'location'                 => 'Lokasi',
        'sort_order'               => 'Urutan',
        'note'                     => 'Keterangan',
        'label'                    => 'Nama sesi',
        'date'                     => 'Tanggal',
        'count'                    => 'Jumlah sesi',
        'date_from'                => 'Dari tanggal',
        'date_to'                  => 'Sampai tanggal',
        'rooms'                    => 'Ruang',
        'slots'                    => 'Jam sesi',
        'test_date'                => 'Tanggal tes',
        'starts_at'                => 'Jam mulai',
        'ends_at'                  => 'Jam selesai',
        'room_id'                  => 'Ruang',
        'session_id'               => 'Sesi tes',
        'seat_number'              => 'Nomor urut',
        'attended_at'              => 'Waktu hadir',
        'recorded_by'              => 'Dicatat oleh',
        'assigned_by'              => 'Dijadwalkan oleh',
        'scheduleVisible'          => 'Jadwal tampil untuk pendaftar',
        'eligibleStatuses'         => 'Status yang dijadwalkan',
        'requireCompleteDocuments' => 'Hanya yang berkasnya lengkap',
        'instructions'             => 'Pesan untuk peserta tes',
        'defaultDurationMinutes'   => 'Lama sesi (menit)',
    ],
    'boolFields' => ['scheduleVisible', 'requireCompleteDocuments'],
];
