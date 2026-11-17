<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('admin/riwayat', ['namespace' => 'Modules\Riwayat\Controllers'], static function ($routes): void {
    $routes->get('/', 'Riwayat::index', ['as' => 'riwayat', 'filter' => 'permission:audit.view']);
    $routes->get('(:num)', 'Riwayat::show/$1', ['as' => 'riwayat.detail', 'filter' => 'permission:audit.view']);
});
