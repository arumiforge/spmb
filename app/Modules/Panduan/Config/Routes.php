<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Guide pages (D-097). The public site only has the guides for applicants and parents;
 * staff guides live in the dashboard (admin/*: sign-in required), each role sees its own.
 *
 * @var RouteCollection $routes
 */
$routes->group('panduan', ['namespace' => 'Modules\Panduan\Controllers'], static function ($routes): void {
    $routes->get('/', 'Panduan::index', ['as' => 'panduan']);
    $routes->get('(:segment)', 'Panduan::show/$1', ['as' => 'panduan.peran']);
});

$routes->group('admin/panduan', ['namespace' => 'Modules\Panduan\Controllers'], static function ($routes): void {
    $routes->get('/', 'PanduanPetugas::index', ['as' => 'panduan.petugas']);
    $routes->get('(:segment)', 'PanduanPetugas::show/$1', ['as' => 'panduan.petugas.peran']);
});
