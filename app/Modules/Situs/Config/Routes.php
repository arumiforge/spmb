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
