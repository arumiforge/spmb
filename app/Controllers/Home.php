<?php

declare(strict_types=1);

namespace App\Controllers;

use Modules\Situs\Services\WaveSchedule;

/**
 * Public home page: registration state and the main actions (FR-WEB-01).
 * Works with any set of modules switched on: without Pendaftaran it only
 * points to the guide.
 */
class Home extends BaseController
{
    public function index(): string
    {
        $modules     = service('modules');
        $pendaftaran = $modules->isEnabled('Pendaftaran');
        $banner      = $pendaftaran ? WaveSchedule::banner() : null;

        return view('home', [
            'title' => (string) setting('School.tagline'),
            'home'  => [
                'registration' => $pendaftaran,
                'banner'       => $banner,
                'onlineOpen'   => $banner !== null && $banner['state'] === 'open',
                'situs'        => $modules->isEnabled('Situs'),
            ],
        ]);
    }
}
