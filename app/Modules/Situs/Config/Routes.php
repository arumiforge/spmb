<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Situs module (public information pages). Indonesian slugs.
 *
 * @var RouteCollection $routes
 */
$routes->get('persyaratan', '\Modules\Situs\Controllers\Situs::persyaratan', ['as' => 'situs.persyaratan']);
$routes->get('jadwal', '\Modules\Situs\Controllers\Situs::jadwal', ['as' => 'situs.jadwal']);
