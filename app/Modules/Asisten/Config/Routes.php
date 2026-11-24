<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Asisten module ("Tanya Asisten", D-110). Public JSON endpoints live under the
 * public site, so they also answer under /en/… and /ar/… (the prefix sets the answer language).
 * Admin routes: asisten.atur (Admin Sekolah) and asisten.ai (Super Admin).
 *
 * @var RouteCollection $routes
 */
$routes->group('asisten', ['namespace' => 'Modules\Asisten\Controllers'], static function ($routes): void {
    $routes->get('mulai', 'Widget::start', ['as' => 'asisten.mulai']);
    $routes->post('tanya', 'Widget::ask', ['as' => 'asisten.tanya']);
    $routes->get('buka/(:num)', 'Widget::open/$1', ['as' => 'asisten.buka']);
    $routes->get('gambar', 'Widget::picture', ['as' => 'asisten.gambar']);
});

$routes->group('admin/asisten', ['namespace' => 'Modules\Asisten\Controllers'], static function ($routes): void {
    $atur = ['filter' => 'permission:asisten.atur'];
    $ai   = ['filter' => 'permission:asisten.ai'];

    $routes->get('/', 'Pengaturan::index', ['as' => 'asisten.admin'] + $atur);
    $routes->post('/', 'Pengaturan::update', $atur);
    $routes->post('gambar/hapus', 'Pengaturan::removePicture', ['as' => 'asisten.gambar.hapus'] + $atur);
    $routes->get('pilihan/tambah', 'Pengaturan::newChoice', ['as' => 'asisten.pilihan.tambah'] + $atur);
    $routes->post('pilihan/tambah', 'Pengaturan::createChoice', $atur);
    $routes->post('pilihan/kembalikan', 'Pengaturan::resetChoices', ['as' => 'asisten.pilihan.kembalikan'] + $atur);
    $routes->get('pilihan/(:num)/ubah', 'Pengaturan::editChoice/$1', ['as' => 'asisten.pilihan.ubah'] + $atur);
    $routes->post('pilihan/(:num)/ubah', 'Pengaturan::updateChoice/$1', $atur);
    $routes->post('pilihan/(:num)/pindah', 'Pengaturan::moveChoice/$1', ['as' => 'asisten.pilihan.pindah'] + $atur);
    $routes->post('pilihan/(:num)/hapus', 'Pengaturan::deleteChoice/$1', ['as' => 'asisten.pilihan.hapus'] + $atur);

    $routes->get('pertanyaan', 'Pertanyaan::index', ['as' => 'asisten.pertanyaan'] + $atur);
    $routes->post('pertanyaan/pengaturan', 'Pertanyaan::update', ['as' => 'asisten.pertanyaan.pengaturan'] + $atur);

    $routes->get('ai', 'KoneksiAi::index', ['as' => 'asisten.ai'] + $ai);
    $routes->post('ai', 'KoneksiAi::update', $ai);
    $routes->get('ai/koneksi/tambah', 'KoneksiAi::new', ['as' => 'asisten.koneksi.tambah'] + $ai);
    $routes->post('ai/koneksi/tambah', 'KoneksiAi::create', $ai);
    $routes->get('ai/koneksi/(:num)/ubah', 'KoneksiAi::edit/$1', ['as' => 'asisten.koneksi.ubah'] + $ai);
    $routes->post('ai/koneksi/(:num)/ubah', 'KoneksiAi::updateProvider/$1', $ai);
    $routes->post('ai/koneksi/(:num)/pindah', 'KoneksiAi::move/$1', ['as' => 'asisten.koneksi.pindah'] + $ai);
    $routes->post('ai/koneksi/(:num)/aktifkan', 'KoneksiAi::toggle/$1', ['as' => 'asisten.koneksi.aktifkan'] + $ai);
    $routes->post('ai/koneksi/(:num)/tes', 'KoneksiAi::test/$1', ['as' => 'asisten.koneksi.tes'] + $ai);
    $routes->post('ai/koneksi/(:num)/hapus', 'KoneksiAi::delete/$1', ['as' => 'asisten.koneksi.hapus'] + $ai);
});
