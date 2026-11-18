<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of work package B (penjadwalan), docs/phase-3-plan.md. Owned by package B.
 *
 * @var RouteCollection $routes
 */
$routes->group('', ['namespace' => 'Modules\Tes\Controllers'], static function (RouteCollection $routes): void {
    $atur      = ['filter' => 'permission:tes.atur'];
    $kehadiran = ['filter' => 'permission:tes.kehadiran'];

    // Automatic allocation: form + preview (GET), run after confirmation (POST).
    $routes->get('admin/tes/penjadwalan', 'Penjadwalan::index', ['as' => 'tes.penjadwalan'] + $atur);
    $routes->post('admin/tes/penjadwalan', 'Penjadwalan::run', $atur);

    $routes->get('admin/tes/peserta', 'Peserta::index', ['as' => 'tes.peserta'] + $kehadiran);
    $routes->get('admin/tes/riwayat-jadwal', 'RiwayatJadwal::index', ['as' => 'tes.riwayat-jadwal'] + $kehadiran);

    // One applicant's seat: give, move, remove.
    $routes->get('admin/pendaftar/(:num)/tes', 'JadwalPendaftar::edit/$1', ['as' => 'tes.jadwal-pendaftar'] + $kehadiran);
    $routes->post('admin/pendaftar/(:num)/tes', 'JadwalPendaftar::update/$1', $kehadiran);
    $routes->post('admin/pendaftar/(:num)/tes/hapus', 'JadwalPendaftar::delete/$1', ['as' => 'tes.jadwal-pendaftar.hapus'] + $atur);
});
