<?php

declare(strict_types=1);

namespace Modules\Referensi\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Referensi\Libraries\AddressBackfill;
use Modules\Referensi\Libraries\BundledRegions;
use Modules\Referensi\Libraries\RegionInput;
use Throwable;

/**
 * Sekolah Asal → Alamat pendaftar (D-111): the state of the national region
 * list and the one-time matching of earlier, typed applicant addresses
 * (AddressBackfill). A preview shows what would change before anything is saved.
 */
class Alamat extends BaseController
{
    public function index(): string
    {
        $available = RegionInput::available();
        $backfill  = new AddressBackfill();
        $preview   = $available && $this->request->getGet('pratinjau') === '1' ? $backfill->run(true) : null;

        try {
            $manifest = (new BundledRegions())->manifest();
        } catch (Throwable) {
            $manifest = null;
        }

        $db     = db_connect();
        $counts = ['total' => 0, 'coded' => 0];

        if ($db->tableExists('applicants') && $db->fieldExists('village_code', 'applicants')) {
            $counts['total'] = $db->table('applicants')->where('deleted_at', null)->countAllResults();
            $counts['coded'] = $db->table('applicants')->where('deleted_at', null)->where('village_code IS NOT NULL', null, false)->countAllResults();
        }

        return view('Modules\Referensi\Views\alamat', [
            'title'       => lang('Referensi.addressTitle'),
            'subtitle'    => lang('Referensi.addressIntro'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Sekolah Asal', 'url' => 'admin/referensi/sekolah'],
                ['label' => lang('Referensi.addressCrumb')],
            ],
            'available' => $available,
            'version'   => (string) setting('Referensi.regionsVersion'),
            'manifest'  => $manifest,
            'pending'   => $available ? $backfill->pendingCount() : 0,
            'counts'    => $counts,
            'preview'   => $preview,
            'regionSet' => (string) setting('School.regionCode') !== '',
        ]);
    }

    public function run(): RedirectResponse
    {
        if (! RegionInput::available()) {
            return redirect()->route('referensi.alamat')->with('error', lang('Referensi.addressNoList'));
        }

        $report = (new AddressBackfill())->run(false, $this->request->getPost('mirip') !== '0');

        return redirect()->route('referensi.alamat')->with('message', lang('Referensi.addressDone', [$report['changed'], $report['checked']]));
    }
}
