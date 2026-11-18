<?php

/** Labels for the audit log viewer (Riwayat), merged by AuditPresenter. */
return [
    'events' => [
        'document_uploaded' => 'Berkas diunggah',
        'document_verified' => 'Berkas diperiksa',
        'document_replaced'  => 'Berkas diganti',
        'document_to_follow' => 'Berkas ditandai menyusul',
    ],
    'eventColors' => [
        'document_uploaded'  => 'blue',
        'document_verified'  => 'green',
        'document_replaced'  => 'yellow',
        'document_to_follow' => 'gray',
    ],
    'subjects' => [
        'document_types'      => 'Jenis Berkas',
        'applicant_documents' => 'Berkas Pendaftar',
    ],
    'settingGroups' => [
        'Berkas' => 'Pengaturan Berkas',
    ],
    'boolFields' => ['is_required', 'may_follow'],
    'fields'     => [
        'code'          => 'Kode',
        'is_required'   => 'Wajib',
        'may_follow'    => 'Boleh menyusul',
        'max_size_kb'   => 'Ukuran maksimal (KB)',
        'extensions'    => 'Jenis file',
        'sort_order'    => 'Urutan',
        'file_path'     => 'File',
        'original_name' => 'Nama file asli',
        'mime'          => 'Jenis file',
        'size_bytes'    => 'Ukuran file',
        'note'          => 'Catatan',
        'verified_by'   => 'Diperiksa oleh',
        'verified_at'   => 'Waktu diperiksa',
        'uploaded_by'   => 'Diunggah oleh',
        'document_type_id' => 'Jenis berkas',
        'imageUploadLimitKb' => 'Batas unggah foto (KB)',
        'imageMaxEdge'  => 'Sisi gambar terpanjang (piksel)',
        'imageQuality'  => 'Kualitas gambar',
        'deadline'      => 'Batas akhir berkas menyusul',
    ],
];
