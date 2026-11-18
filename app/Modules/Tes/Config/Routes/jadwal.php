<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of work package A (jadwal), docs/phase-3-plan.md. Owned by package A:
 * rooms, test calendar with holidays, sessions and the session generator,
 * Pengaturan → Tes. Everything needs tes.atur.
 *
 * @var RouteCollection $routes
 */
$routes->group('', ['namespace' => 'Modules\Tes\Controllers'], static function (RouteCollection $routes): void {
    $perm = ['filter' => 'permission:tes.atur'];

    $routes->group('admin/tes', static function (RouteCollection $routes) use ($perm): void {
        // Rooms
        $routes->get('ruang', 'Ruang::index', ['as' => 'tes.ruang'] + $perm);
        $routes->get('ruang/tambah', 'Ruang::new', ['as' => 'tes.ruang.tambah'] + $perm);
        $routes->post('ruang/tambah', 'Ruang::create', $perm);
        $routes->get('ruang/(:num)/ubah', 'Ruang::edit/$1', ['as' => 'tes.ruang.ubah'] + $perm);
        $routes->post('ruang/(:num)/ubah', 'Ruang::update/$1', $perm);
        $routes->post('ruang/(:num)/nonaktifkan', 'Ruang::deactivate/$1', ['as' => 'tes.ruang.nonaktifkan'] + $perm);
        $routes->post('ruang/(:num)/aktifkan', 'Ruang::activate/$1', ['as' => 'tes.ruang.aktifkan'] + $perm);
        $routes->post('ruang/(:num)/hapus', 'Ruang::delete/$1', ['as' => 'tes.ruang.hapus'] + $perm);

        // Calendar and holidays
        $routes->get('kalender', 'Kalender::index', ['as' => 'tes.kalender'] + $perm);
        $routes->post('kalender/libur', 'Kalender::addHoliday', ['as' => 'tes.kalender.libur'] + $perm);
        $routes->post('kalender/libur/(:num)/hapus', 'Kalender::deleteHoliday/$1', ['as' => 'tes.kalender.libur.hapus'] + $perm);

        // Sessions and the generator
        $routes->get('sesi', 'Sesi::index', ['as' => 'tes.sesi'] + $perm);
        $routes->get('sesi/tambah', 'Sesi::new', ['as' => 'tes.sesi.tambah'] + $perm);
        $routes->post('sesi/tambah', 'Sesi::create', $perm);
        $routes->get('sesi/buat-banyak', 'Sesi::generator', ['as' => 'tes.sesi.buat-banyak'] + $perm);
        $routes->post('sesi/buat-banyak/pratinjau', 'Sesi::preview', ['as' => 'tes.sesi.pratinjau'] + $perm);
        $routes->post('sesi/buat-banyak', 'Sesi::generate', $perm);
        $routes->get('sesi/(:num)/ubah', 'Sesi::edit/$1', ['as' => 'tes.sesi.ubah'] + $perm);
        $routes->post('sesi/(:num)/ubah', 'Sesi::update/$1', $perm);
        $routes->post('sesi/(:num)/nonaktifkan', 'Sesi::deactivate/$1', ['as' => 'tes.sesi.nonaktifkan'] + $perm);
        $routes->post('sesi/(:num)/aktifkan', 'Sesi::activate/$1', ['as' => 'tes.sesi.aktifkan'] + $perm);
        $routes->post('sesi/(:num)/hapus', 'Sesi::delete/$1', ['as' => 'tes.sesi.hapus'] + $perm);
    });

    // Settings
    $routes->get('admin/pengaturan/tes', 'Pengaturan::index', ['as' => 'tes.pengaturan'] + $perm);
    $routes->post('admin/pengaturan/tes', 'Pengaturan::update', $perm);
});
