<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Tes\Libraries\AttendanceSheetPdf;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Printable attendance sheet of one session (tes.kehadiran). Reading only:
 * printing changes no data.
 */
class DaftarHadir extends BaseController
{
    public function show(int $id): ResponseInterface
    {
        $session = model(TestSessionModel::class)->findWithStats($id);

        if ($session === null) {
            throw PageNotFoundException::forPageNotFound(lang('TesKehadiran.notFound'));
        }

        $rows = model(TestAssignmentModel::class)->forSession($id);
        $file = 'daftar-hadir-' . $session['test_date'] . '-' . (preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $session['room_name'])) ?: 'ruang') . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . trim($file, '-') . '"')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((new AttendanceSheetPdf())->render($session, $rows));
    }
}
