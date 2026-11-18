<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Pendaftaran module. Indonesian slugs; every admin route carries a
 * permission filter. The URLs are listed in docs/phase-2-plan.md.
 *
 * Other modules add routes under admin/pendaftar/<id>/… (berkas, kartu) in their
 * own route files; do not define those here.
 *
 * @var RouteCollection $routes
 */

// Settings: waves and registration options.
$routes->group('admin/pengaturan', ['namespace' => 'Modules\Pendaftaran\Controllers\Admin'], static function (RouteCollection $routes): void {
    $perm = ['filter' => 'permission:pengaturan.pendaftaran'];

    $routes->get('gelombang', 'Gelombang::index', ['as' => 'gelombang'] + $perm);
    $routes->get('gelombang/tambah', 'Gelombang::new', ['as' => 'gelombang.tambah'] + $perm);
    $routes->post('gelombang', 'Gelombang::create', $perm);
    $routes->get('gelombang/(:num)/ubah', 'Gelombang::edit/$1', ['as' => 'gelombang.ubah'] + $perm);
    $routes->post('gelombang/(:num)', 'Gelombang::update/$1', ['as' => 'gelombang.simpan'] + $perm);
    $routes->post('gelombang/(:num)/hapus', 'Gelombang::delete/$1', ['as' => 'gelombang.hapus'] + $perm);

    $routes->get('pendaftaran', 'Pengaturan::index', ['as' => 'pengaturan.pendaftaran'] + $perm);
    $routes->post('pendaftaran', 'Pengaturan::update', $perm);
});

// Applicant data for staff.
$routes->group('admin/pendaftar', ['namespace' => 'Modules\Pendaftaran\Controllers\Admin'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Pendaftar::index', ['as' => 'pendaftar', 'filter' => 'permission:pendaftar.view']);
    $routes->get('tambah', 'Pendaftar::new', ['as' => 'pendaftar.tambah', 'filter' => 'permission:pendaftar.create']);
    $routes->post('tambah', 'Pendaftar::create', ['filter' => 'permission:pendaftar.create']);
    $routes->get('(:num)', 'Pendaftar::show/$1', ['as' => 'pendaftar.detail', 'filter' => 'permission:pendaftar.view']);
    $routes->get('(:num)/ubah', 'Pendaftar::edit/$1', ['as' => 'pendaftar.ubah', 'filter' => 'permission:pendaftar.edit']);
    $routes->post('(:num)/ubah', 'Pendaftar::update/$1', ['filter' => 'permission:pendaftar.edit']);
    $routes->post('(:num)/status', 'Pendaftar::status/$1', ['as' => 'pendaftar.status', 'filter' => 'permission:pendaftar.edit']);
    $routes->post('(:num)/data-ganda', 'Pendaftar::reviewDuplicate/$1', ['as' => 'pendaftar.data-ganda', 'filter' => 'permission:pendaftar.edit']);
    $routes->post('(:num)/tautan', 'Pendaftar::privateLink/$1', ['as' => 'pendaftar.tautan', 'filter' => 'permission:pendaftar.edit']);
    $routes->post('(:num)/hapus', 'Pendaftar::delete/$1', ['as' => 'pendaftar.hapus', 'filter' => 'permission:pendaftar.delete']);
});

// Public pages: registration form, status check and the private link (D-008, D-034).
$routes->group('', ['namespace' => 'Modules\Pendaftaran\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('daftar', 'Daftar::index', ['as' => 'daftar']);
    $routes->post('daftar', 'Daftar::submit');
    $routes->get('daftar/selesai', 'Daftar::done', ['as' => 'daftar.selesai']);

    $routes->get('cek-status', 'CekStatus::index', ['as' => 'cek-status']);
    $routes->post('cek-status', 'CekStatus::check');
    $routes->get('cek-status/data', 'CekStatus::show', ['as' => 'cek-status.data']);
    $routes->get('cek-status/ubah', 'CekStatus::edit', ['as' => 'cek-status.ubah']);
    $routes->post('cek-status/ubah', 'CekStatus::update');
    $routes->match(['get', 'post'], 'cek-status/keluar', 'CekStatus::signOut', ['as' => 'cek-status.keluar']);

    $routes->get('tautan/(:segment)', 'Tautan::open/$1', ['as' => 'tautan']);
});
