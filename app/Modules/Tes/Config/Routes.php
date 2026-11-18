<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Routes of the Tes module. Each work package owns one file in Config/Routes/
 * (docs/phase-3-plan.md) so packages never edit the same file. Indonesian
 * slugs; every admin route carries a permission filter.
 *
 * @var RouteCollection $routes
 */
foreach (['jadwal', 'penjadwalan', 'kehadiran'] as $package) {
    $file = __DIR__ . '/Routes/' . $package . '.php';

    if (is_file($file)) {
        require $file;
    }
}
