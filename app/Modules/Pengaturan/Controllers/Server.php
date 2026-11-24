<?php

declare(strict_types=1);

namespace Modules\Pengaturan\Controllers;

use App\Controllers\BaseController;
use App\Libraries\ServerCheck;

/**
 * Server condition (Super Admin, D-101): upload limits, PHP settings, extensions,
 * disk, database and app facts. Read-only.
 */
class Server extends BaseController
{
    public function index(): string
    {
        $report = (new ServerCheck())->report();

        return view('Modules\Pengaturan\Views\server', [
            'title'       => lang('Pengaturan.serverTitle'),
            'subtitle'    => lang('Pengaturan.serverIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => lang('Pengaturan.serverTitle')]],
            'report'      => $report,
            'overall'     => ServerCheck::overall($report),
            'maxUpload'   => ServerCheck::maxUploadBytes(),
        ]);
    }
}
