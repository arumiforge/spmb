<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Referensi module. Indonesian slugs; every admin route carries a
 * permission filter. The URLs are listed in docs/phase-2-plan.md.
 *
 * @var RouteCollection $routes
 */
$routes->addRedirect('admin/referensi', 'admin/referensi/sekolah');

$routes->group('admin/referensi', ['namespace' => 'Modules\Referensi\Controllers', 'filter' => 'permission:referensi.kelola'], static function ($routes): void {
    $routes->get('sekolah', 'Sekolah::index', ['as' => 'referensi.sekolah']);
    $routes->get('sekolah/tambah', 'Sekolah::new', ['as' => 'referensi.sekolah.tambah']);
    $routes->post('sekolah/tambah', 'Sekolah::create');
    $routes->get('sekolah/(:num)/ubah', 'Sekolah::edit/$1', ['as' => 'referensi.sekolah.ubah']);
    $routes->post('sekolah/(:num)/ubah', 'Sekolah::update/$1');
    $routes->post('sekolah/(:num)/nonaktifkan', 'Sekolah::deactivate/$1', ['as' => 'referensi.sekolah.nonaktifkan']);
    $routes->post('sekolah/(:num)/aktifkan', 'Sekolah::activate/$1', ['as' => 'referensi.sekolah.aktifkan']);

    $routes->get('sekolah/impor', 'Impor::index', ['as' => 'referensi.impor']);
    $routes->post('sekolah/impor', 'Impor::upload');
    $routes->get('sekolah/impor/contoh', 'Impor::template', ['as' => 'referensi.impor.contoh']);

    $routes->get('sekolah-lain', 'SekolahLain::index', ['as' => 'referensi.lain']);
    $routes->get('sekolah-lain/petakan', 'SekolahLain::map', ['as' => 'referensi.lain.petakan']);
    $routes->post('sekolah-lain/petakan', 'SekolahLain::assign');
    $routes->post('sekolah-lain/tambah', 'SekolahLain::addAndAssign', ['as' => 'referensi.lain.tambah']);
});

// Public school search for the registration form (JSON, throttled per visitor).
$routes->get('sekolah-asal/cari', '\Modules\Referensi\Controllers\Cari::index', ['as' => 'referensi.cari']);
