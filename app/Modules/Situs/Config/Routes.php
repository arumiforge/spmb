<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Situs module (public information pages). Indonesian slugs.
 * Admin routes carry the situs.kelola permission filter.
 *
 * @var RouteCollection $routes
 */
$routes->get('persyaratan', '\Modules\Situs\Controllers\Situs::persyaratan', ['as' => 'situs.persyaratan']);
$routes->get('jadwal', '\Modules\Situs\Controllers\Situs::jadwal', ['as' => 'situs.jadwal']);
$routes->get('tanya-jawab', '\Modules\Situs\Controllers\Situs::tanyaJawab', ['as' => 'situs.tanya-jawab']);

$routes->group('admin/tanya-jawab', ['namespace' => 'Modules\Situs\Controllers', 'filter' => 'permission:situs.kelola'], static function ($routes): void {
    $routes->get('/', 'TanyaJawab::index', ['as' => 'situs.tanya-jawab.admin']);
    $routes->get('tambah', 'TanyaJawab::new', ['as' => 'situs.tanya-jawab.tambah']);
    $routes->post('tambah', 'TanyaJawab::create');
    $routes->post('topik/pindah', 'TanyaJawab::moveTopic', ['as' => 'situs.tanya-jawab.topik-pindah']);
    $routes->get('(:num)/ubah', 'TanyaJawab::edit/$1', ['as' => 'situs.tanya-jawab.ubah']);
    $routes->post('(:num)/ubah', 'TanyaJawab::update/$1');
    $routes->post('(:num)/pindah', 'TanyaJawab::move/$1', ['as' => 'situs.tanya-jawab.pindah']);
    $routes->post('(:num)/tampilkan', 'TanyaJawab::publish/$1', ['as' => 'situs.tanya-jawab.tampilkan']);
    $routes->post('(:num)/hapus', 'TanyaJawab::delete/$1', ['as' => 'situs.tanya-jawab.hapus']);
});

// Image and attachment of the home page popup (public while it shows, always for admins).
$routes->get('popup/gambar', '\Modules\Situs\Controllers\Popup::image', ['as' => 'situs.popup.gambar']);
$routes->get('popup/lampiran', '\Modules\Situs\Controllers\Popup::attachment', ['as' => 'situs.popup.lampiran']);

$routes->group('admin/berita-singkat', ['namespace' => 'Modules\Situs\Controllers', 'filter' => 'permission:situs.kelola'], static function ($routes): void {
    $routes->get('/', 'BeritaSingkat::index', ['as' => 'situs.berita.admin']);
    $routes->post('pengaturan', 'BeritaSingkat::updateSettings', ['as' => 'situs.berita.pengaturan']);
    $routes->get('tambah', 'BeritaSingkat::new', ['as' => 'situs.berita.tambah']);
    $routes->post('tambah', 'BeritaSingkat::create');
    $routes->get('(:num)/ubah', 'BeritaSingkat::edit/$1', ['as' => 'situs.berita.ubah']);
    $routes->post('(:num)/ubah', 'BeritaSingkat::update/$1');
    $routes->post('(:num)/pindah', 'BeritaSingkat::move/$1', ['as' => 'situs.berita.pindah']);
    $routes->post('(:num)/aktifkan', 'BeritaSingkat::activate/$1', ['as' => 'situs.berita.aktifkan']);
    $routes->post('(:num)/hapus', 'BeritaSingkat::delete/$1', ['as' => 'situs.berita.hapus']);
});

$routes->group('admin/popup', ['namespace' => 'Modules\Situs\Controllers', 'filter' => 'permission:situs.kelola'], static function ($routes): void {
    $routes->get('/', 'Popup::index', ['as' => 'situs.popup.admin']);
    $routes->post('/', 'Popup::update');
});

$routes->group('admin/tautan-footer', ['namespace' => 'Modules\Situs\Controllers', 'filter' => 'permission:situs.kelola'], static function ($routes): void {
    $routes->get('/', 'TautanFooter::index', ['as' => 'situs.tautan.admin']);
    $routes->get('tambah', 'TautanFooter::new', ['as' => 'situs.tautan.tambah']);
    $routes->post('tambah', 'TautanFooter::create');
    $routes->post('kembalikan', 'TautanFooter::reset', ['as' => 'situs.tautan.kembalikan']);
    $routes->get('(:num)/ubah', 'TautanFooter::edit/$1', ['as' => 'situs.tautan.ubah']);
    $routes->post('(:num)/ubah', 'TautanFooter::update/$1');
    $routes->post('(:num)/pindah', 'TautanFooter::move/$1', ['as' => 'situs.tautan.pindah']);
    $routes->post('(:num)/tampilkan', 'TautanFooter::publish/$1', ['as' => 'situs.tautan.tampilkan']);
    $routes->post('(:num)/hapus', 'TautanFooter::delete/$1', ['as' => 'situs.tautan.hapus']);
});
