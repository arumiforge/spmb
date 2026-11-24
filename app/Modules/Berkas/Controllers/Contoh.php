<?php

declare(strict_types=1);

namespace Modules\Berkas\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Berkas\Models\DocumentTypeModel;

/**
 * Public example files ("Unduh contoh") of document types. No sign-in:
 * applicants have no account (D-008). Only active types whose example the
 * school switched on are served; everything else is 404.
 */
class Contoh extends BaseController
{
    use ServesTemplates;

    /**
     * GET /berkas/contoh/<code>
     */
    public function download(string $code): ResponseInterface
    {
        $type = model(DocumentTypeModel::class)->where('code', strtolower($code))->first();

        if ($type === null || ! DocumentTypeModel::templateShown($type)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->sendTemplate($type);
    }
}
