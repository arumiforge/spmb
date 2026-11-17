<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('admin/pengguna', ['namespace' => 'Modules\Pengguna\Controllers'], static function ($routes): void {
    $routes->get('/', 'Pengguna::index', ['as' => 'pengguna', 'filter' => 'permission:pengguna.view']);
    $routes->get('tambah', 'Pengguna::new', ['as' => 'pengguna.tambah', 'filter' => 'permission:pengguna.create']);
    $routes->post('tambah', 'Pengguna::create', ['filter' => 'permission:pengguna.create']);
    $routes->get('(:num)/ubah', 'Pengguna::edit/$1', ['as' => 'pengguna.ubah', 'filter' => 'permission:pengguna.edit']);
    $routes->post('(:num)/ubah', 'Pengguna::update/$1', ['filter' => 'permission:pengguna.edit']);
    $routes->post('(:num)/nonaktifkan', 'Pengguna::deactivate/$1', ['as' => 'pengguna.nonaktifkan', 'filter' => 'permission:pengguna.edit']);
    $routes->post('(:num)/aktifkan', 'Pengguna::activate/$1', ['as' => 'pengguna.aktifkan', 'filter' => 'permission:pengguna.edit']);
    $routes->post('(:num)/atur-ulang-kata-sandi', 'Pengguna::resetPassword/$1', ['as' => 'pengguna.reset-sandi', 'filter' => 'permission:pengguna.edit']);
    $routes->post('(:num)/matikan-dua-langkah', 'Pengguna::resetTwoFactor/$1', ['as' => 'pengguna.reset-dua-langkah', 'filter' => 'permission:pengguna.edit']);
});
