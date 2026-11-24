<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Visitor statistics (module Analitik). Counting itself is the global after-filter
 * `analitik` (Config\Filters); these are the staff pages.
 *
 * @var RouteCollection $routes
 */
$routes->group('admin/pengunjung', ['namespace' => 'Modules\Analitik\Controllers'], static function ($routes): void {
    $routes->get('/', 'Pengunjung::index', ['as' => 'analitik', 'filter' => 'permission:analitik.view']);
    $routes->post('pengaturan', 'Pengunjung::update', ['as' => 'analitik.pengaturan', 'filter' => 'permission:pengaturan.sekolah']);
});
