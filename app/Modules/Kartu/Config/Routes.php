<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Kartu module. Indonesian slugs; every admin route carries a
 * permission filter. The URLs are listed in docs/phase-2-plan.md.
 *
 * @var RouteCollection $routes
 */
$routes->group('', ['namespace' => 'Modules\Kartu\Controllers'], static function (RouteCollection $routes): void {
    // Public: QR check page (random token, minimal data, throttled) and the applicant's own card.
    $routes->get('kartu/(:segment)', 'Periksa::show/$1', ['as' => 'kartu.periksa']);
    $routes->get('cek-status/kartu', 'Unduh::index', ['as' => 'kartu.unduh']);

    // Staff.
    $routes->get('admin/kartu', 'Periksa::lookup', ['as' => 'kartu.cari', 'filter' => 'permission:kartu.cetak']);
    $routes->get('admin/pendaftar/(:num)/kartu', 'Cetak::show/$1', ['as' => 'kartu.cetak', 'filter' => 'permission:kartu.cetak']);
    $routes->post('admin/pendaftar/(:num)/kartu/baru', 'Cetak::renew/$1', ['as' => 'kartu.baru', 'filter' => 'permission:kartu.cetak']);
    $routes->get('admin/pengaturan/kartu', 'Pengaturan::index', ['as' => 'kartu.pengaturan', 'filter' => 'permission:pengaturan.pendaftaran']);
    $routes->post('admin/pengaturan/kartu', 'Pengaturan::update', ['filter' => 'permission:pengaturan.pendaftaran']);
});
