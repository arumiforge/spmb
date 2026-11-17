<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Public guide pages, one per role (no sign-in needed).
 *
 * @var RouteCollection $routes
 */
$routes->group('panduan', ['namespace' => 'Modules\Panduan\Controllers'], static function ($routes): void {
    $routes->get('/', 'Panduan::index', ['as' => 'panduan']);
    $routes->get('(:segment)', 'Panduan::show/$1', ['as' => 'panduan.peran']);
});
