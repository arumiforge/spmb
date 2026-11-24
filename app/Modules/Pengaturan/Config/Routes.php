<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->addRedirect('admin/pengaturan', 'admin/pengaturan/sekolah');

$routes->group('admin/pengaturan', ['namespace' => 'Modules\Pengaturan\Controllers'], static function ($routes): void {
    $routes->get('sekolah', 'Sekolah::index', ['as' => 'pengaturan.sekolah', 'filter' => 'permission:pengaturan.sekolah']);
    $routes->post('sekolah', 'Sekolah::update', ['filter' => 'permission:pengaturan.sekolah']);
    $routes->get('keamanan', 'Keamanan::index', ['as' => 'pengaturan.keamanan', 'filter' => 'permission:sistem.keamanan']);
    $routes->post('keamanan', 'Keamanan::update', ['filter' => 'permission:sistem.keamanan']);
    $routes->get('fitur', 'Fitur::index', ['as' => 'pengaturan.fitur', 'filter' => 'permission:sistem.modul']);
    $routes->post('fitur', 'Fitur::update', ['filter' => 'permission:sistem.modul']);
    $routes->get('kondisi-server', 'Server::index', ['as' => 'pengaturan.server', 'filter' => 'permission:sistem.server']);
});
