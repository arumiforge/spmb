<?php

declare(strict_types=1);

namespace Modules\Referensi\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Referensi\Libraries\SchoolImporter;
use RuntimeException;

/**
 * CSV/XLSX import of previous schools with a row-level report (FR-RPT-04).
 */
class Impor extends BaseController
{
    /**
     * Rows with problems kept for the report page.
     */
    private const REPORT_ERRORS = 200;

    private const MAX_BYTES = 5 * 1024 * 1024;

    public function index(): string
    {
        return view('Modules\Referensi\Views\impor', [
            'title'       => lang('Referensi.importTitle'),
            'subtitle'    => lang('Referensi.importIntro'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Sekolah Asal', 'url' => 'admin/referensi/sekolah'],
                ['label' => 'Impor'],
            ],
            'report' => session('import_report'),
        ]);
    }

    public function upload(): RedirectResponse
    {
        $file = $this->request->getFile('berkas');

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return redirect()->back()->with('errors', ['berkas' => lang('Referensi.importNoFile')]);
        }

        // The file is only read from PHP's temporary upload path, never moved,
        // so the checks are done here instead of the "uploaded" rule.
        if ($file->getError() !== UPLOAD_ERR_OK || ! is_file($file->getTempName())) {
            return redirect()->back()->with('errors', ['berkas' => lang('Referensi.importUnreadable')]);
        }

        if (! in_array(strtolower($file->getClientExtension()), ['csv', 'xlsx', 'txt'], true)) {
            return redirect()->back()->with('errors', ['berkas' => lang('Referensi.importBadType')]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            return redirect()->back()->with('errors', ['berkas' => lang('Referensi.importFileHelp')]);
        }

        $importer = new SchoolImporter();

        try {
            $rows = $importer->readFile($file->getTempName(), $file->getClientExtension());
        } catch (RuntimeException $e) {
            return redirect()->back()->with('errors', ['berkas' => $e->getMessage()]);
        }

        if ($rows === []) {
            return redirect()->back()->with('errors', ['berkas' => lang('Referensi.importEmpty')]);
        }

        $report = $importer->import($rows, 'import');

        service('audit')->log('schools_imported', 'ref_sekolah', null, null, [
            'source'    => 'import',
            'file'      => mb_substr($file->getClientName(), 0, 100),
            'created'   => $report['created'],
            'updated'   => $report['updated'],
            'unchanged' => $report['unchanged'],
            'failed'    => $report['failed'],
        ], 'Impor sekolah asal dari berkas');

        $totalErrors      = count($report['errors']);
        $report['errors'] = array_slice($report['errors'], 0, self::REPORT_ERRORS);
        $report['more']   = max(0, $totalErrors - self::REPORT_ERRORS);
        $report['file']   = $file->getClientName();

        $redirect = redirect()->route('referensi.impor')
            ->with('import_report', $report)
            ->with('message', lang('Referensi.importDone', [$report['created'], $report['updated'], $report['unchanged']]));

        return $report['failed'] > 0
            ? $redirect->with('warning', lang('Referensi.importHasErrors', [$report['failed']]))
            : $redirect;
    }

    /**
     * Example CSV with the expected columns.
     */
    public function template(): ResponseInterface
    {
        $lines = [
            ['NPSN', 'Nama Sekolah', 'Jenjang', 'Status', 'Provinsi', 'Kabupaten/Kota', 'Kecamatan', 'Desa/Kelurahan', 'Alamat'],
            ['', 'SD Negeri 1 Contoh', 'SD', 'Negeri', 'Jawa Tengah', 'Kab. Kudus', 'Jati', 'Jati Kulon', 'Jl. Contoh No. 1'],
            ['', 'MI NU Contoh', 'MI', 'Swasta', 'Jawa Tengah', 'Kab. Kudus', 'Kota Kudus', '', ''],
        ];

        $handle = fopen('php://temp', 'r+b');

        foreach ($lines as $line) {
            fputcsv($handle, $line, ',', '"', '');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $this->response->download('contoh-sekolah-asal.csv', "\xEF\xBB\xBF" . $csv)->setContentType('text/csv');
    }
}
