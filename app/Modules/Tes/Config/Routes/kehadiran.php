<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of work package C (kehadiran), docs/phase-3-plan.md. Owned by package C.
 * Attendance, card scanning, the attendance sheet PDF.
 *
 * @var RouteCollection $routes
 */
$routes->group('', ['namespace' => 'Modules\Tes\Controllers'], static function (RouteCollection $routes): void {
    $staff = ['filter' => 'permission:tes.kehadiran'];

    $routes->get('admin/tes/kehadiran', 'Kehadiran::index', ['as' => 'tes.kehadiran'] + $staff);
    $routes->post('admin/tes/kehadiran/catat', 'Kehadiran::record', ['as' => 'tes.kehadiran.catat'] + $staff);
    $routes->get('admin/tes/kehadiran/sesi/(:num)', 'Kehadiran::session/$1', ['as' => 'tes.kehadiran.sesi'] + $staff);
    $routes->post('admin/tes/kehadiran/sesi/(:num)/pindai', 'Kehadiran::scan/$1', ['as' => 'tes.kehadiran.pindai'] + $staff);
    $routes->post('admin/tes/kehadiran/sesi/(:num)/pindahkan', 'Kehadiran::moveHere/$1', ['as' => 'tes.kehadiran.pindahkan'] + $staff);
    $routes->post('admin/tes/kehadiran/sesi/(:num)/tidak-hadir-semua', 'Kehadiran::markRemainingAbsent/$1', ['as' => 'tes.kehadiran.tidakHadirSemua'] + $staff);
    $routes->get('admin/tes/sesi/(:num)/daftar-hadir', 'DaftarHadir::show/$1', ['as' => 'tes.daftarHadir'] + $staff);
});
