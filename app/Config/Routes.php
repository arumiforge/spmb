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

// Branding files uploaded in settings (public: shown on the sign-in page and website).
$routes->get('aset/logo', 'Branding::logo', ['as' => 'aset.logo']);
$routes->get('aset/favicon', 'Branding::favicon', ['as' => 'aset.favicon']);
$routes->get('aset/(:segment)', 'Branding::show/$1', ['as' => 'aset']);

// Staff area. Filters for the whole group are set in Config\Filters::$filters.
$routes->group('admin', static function (RouteCollection $routes): void {
    $routes->get('/', 'Admin\Dashboard::index', ['as' => 'admin.dasbor', 'filter' => 'permission:dasbor.view']);
    $routes->get('akses-ditolak', 'Admin\Dashboard::denied', ['as' => 'admin.akses-ditolak']);
});

// Routes of enabled feature modules (app/Modules/<Name>/Config/Routes.php).
foreach (service('modules')->enabled() as $module) {
    $file = service('modules')->file($module, 'Config/Routes.php');

    if ($file !== null) {
        require $file;
    }
}
