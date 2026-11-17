<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Own account pages. Every signed-in staff member may use them (no permission needed).
 *
 * @var RouteCollection $routes
 */
$routes->group('admin/profil', ['namespace' => 'Modules\Profil\Controllers'], static function ($routes): void {
    $routes->get('/', 'Profil::index', ['as' => 'profil']);
    $routes->get('kata-sandi', 'KataSandi::index', ['as' => 'profil.kata-sandi']);
    $routes->post('kata-sandi', 'KataSandi::update');
    $routes->get('verifikasi-dua-langkah', 'DuaLangkah::index', ['as' => 'profil.dua-langkah']);
    $routes->post('verifikasi-dua-langkah/aktifkan', 'DuaLangkah::enable', ['as' => 'profil.dua-langkah.aktifkan']);
    $routes->post('verifikasi-dua-langkah/matikan', 'DuaLangkah::disable', ['as' => 'profil.dua-langkah.matikan']);
    $routes->get('verifikasi-dua-langkah/kode-cadangan', 'DuaLangkah::codes', ['as' => 'profil.dua-langkah.kode']);
    $routes->post('verifikasi-dua-langkah/kode-cadangan', 'DuaLangkah::regenerate');
});
