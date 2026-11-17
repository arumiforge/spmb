<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Route slugs are friendly Indonesian (see .claude/skills/spmb-module).
 * Feature modules register their own routes in app/Modules/<Name>/Config/Routes.php.
 *
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Staff sign-in, sign-out and verification (/masuk, /keluar, /verifikasi) — see Config\AuthRoutes.
service('auth')->routes($routes);

$routes->group('admin', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Admin\Dashboard::index', ['as' => 'admin.dasbor', 'filter' => 'permission:dasbor.view']);
});
