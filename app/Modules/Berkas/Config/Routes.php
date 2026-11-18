<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Berkas module. Indonesian slugs; every admin route carries a
 * permission filter. The URLs are listed in docs/phase-2-plan.md.
 * Public routes (/cek-status/berkas/...) check the applicant grant
 * (service('applicantAccess')) in the controller.
 *
 * @var RouteCollection $routes
 */
$routes->group('admin/pengaturan/berkas', ['namespace' => 'Modules\Berkas\Controllers'], static function ($routes): void {
    $routes->get('/', 'Pengaturan::index', ['as' => 'berkas.pengaturan', 'filter' => 'permission:pengaturan.pendaftaran']);
    $routes->post('/', 'Pengaturan::updateSettings', ['filter' => 'permission:pengaturan.pendaftaran']);
    $routes->get('tambah', 'Pengaturan::new', ['as' => 'berkas.jenis.tambah', 'filter' => 'permission:pengaturan.pendaftaran']);
    $routes->post('tambah', 'Pengaturan::create', ['filter' => 'permission:pengaturan.pendaftaran']);
    $routes->get('(:num)/ubah', 'Pengaturan::edit/$1', ['as' => 'berkas.jenis.ubah', 'filter' => 'permission:pengaturan.pendaftaran']);
    $routes->post('(:num)/ubah', 'Pengaturan::update/$1', ['filter' => 'permission:pengaturan.pendaftaran']);
    $routes->post('(:num)/pindah', 'Pengaturan::move/$1', ['as' => 'berkas.jenis.pindah', 'filter' => 'permission:pengaturan.pendaftaran']);
    $routes->post('(:num)/hapus', 'Pengaturan::delete/$1', ['as' => 'berkas.jenis.hapus', 'filter' => 'permission:pengaturan.pendaftaran']);
});

$routes->group('admin/berkas', ['namespace' => 'Modules\Berkas\Controllers'], static function ($routes): void {
    $routes->get('/', 'Periksa::index', ['as' => 'berkas.antrean', 'filter' => 'permission:pendaftar.verifikasi']);
    $routes->get('(:num)/lihat', 'Periksa::show/$1', ['as' => 'berkas.lihat', 'filter' => 'permission:pendaftar.view']);
    $routes->get('(:num)/periksa', 'Periksa::review/$1', ['as' => 'berkas.periksa', 'filter' => 'permission:pendaftar.verifikasi']);
    $routes->post('(:num)/periksa', 'Periksa::verify/$1', ['filter' => 'permission:pendaftar.verifikasi']);
});

$routes->post('admin/pendaftar/(:num)/berkas/(:num)', '\Modules\Berkas\Controllers\Periksa::upload/$1/$2', ['as' => 'berkas.unggah-petugas', 'filter' => 'permission:pendaftar.edit']);

$routes->group('cek-status/berkas', ['namespace' => 'Modules\Berkas\Controllers'], static function ($routes): void {
    $routes->post('(:num)', 'Pendaftar::upload/$1', ['as' => 'berkas.unggah']);
    $routes->get('(:num)', 'Pendaftar::show/$1', ['as' => 'berkas.milik-saya']);
});
