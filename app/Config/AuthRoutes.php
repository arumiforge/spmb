<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthRoutes as ShieldAuthRoutes;

/**
 * Shield routes with friendly Indonesian URLs.
 *
 * Route names are kept as Shield expects them (login, logout, auth-action-*).
 * Public self-registration and magic links are not offered: staff accounts
 * are created by an administrator.
 */
class AuthRoutes extends ShieldAuthRoutes
{
    public array $routes = [
        'login' => [
            ['get', 'masuk', 'LoginController::loginView', 'login'],
            ['post', 'masuk', 'LoginController::loginAction'],
        ],
        'logout' => [
            ['get', 'keluar', 'LoginController::logoutAction', 'logout'],
        ],
        'auth-actions' => [
            ['get', 'verifikasi', 'ActionController::show', 'auth-action-show'],
            ['post', 'verifikasi/kirim', 'ActionController::handle', 'auth-action-handle'],
            ['post', 'verifikasi', 'ActionController::verify', 'auth-action-verify'],
        ],
    ];
}
