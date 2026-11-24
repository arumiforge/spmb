<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Applicant statistics (module Statistik, D-111): one page with folding
 * sections and a CSV download per section.
 *
 * @var RouteCollection $routes
 */
$routes->group('admin/statistik', ['namespace' => 'Modules\Statistik\Controllers'], static function ($routes): void {
    $routes->get('/', 'Statistik::index', ['as' => 'statistik', 'filter' => 'permission:laporan.view']);
    $routes->get('unduh/(:segment)', 'Statistik::unduh/$1', ['as' => 'statistik.unduh', 'filter' => 'permission:laporan.export']);
});
