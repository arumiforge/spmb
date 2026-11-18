<?php

declare(strict_types=1);

namespace Modules\Berkas\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\DocumentService;
use Modules\Berkas\Services\UploadRejected;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Demo documents (development only): for applicants that have no documents
 * yet, uploads generated sample images with a mix of statuses so the queue
 * and panels have something to show. Idempotent; does nothing when there are
 * no applicants (run "php spark spmb:install --demo" again after the
 * Pendaftaran demo data exists).
 */
class DemoSeeder extends Seeder
{
    private const LIMIT = 12;

    private ?int $staffId = null;

    public function run(): void
    {
        if (! extension_loaded('gd')) {
            return;
        }

        $types      = model(DocumentTypeModel::class, false)->active();
        $documents  = model(ApplicantDocumentModel::class, false);
        $applicants = model(ApplicantModel::class, false)->orderBy('id', 'ASC')->findAll(self::LIMIT);
        $service    = new DocumentService();

        if ($types === []) {
            return;
        }

        foreach ($applicants as $n => $applicant) {
            if ($documents->withDeleted()->where('applicant_id', $applicant['id'])->countAllResults() > 0) {
                continue;
            }

            foreach ($types as $t => $type) {
                // Leave some documents missing; mark "may follow" ones as to follow.
                if (($n + $t) % 5 === 4) {
                    continue;
                }

                try {
                    if ((bool) $type['may_follow'] && $n % 2 === 0) {
                        $service->markToFollow($applicant, $type, null);

                        continue;
                    }

                    $tmp = $this->sampleImage((string) $type['name'], (string) $applicant['full_name']);
                    $doc = $service->upload($applicant, $type, $tmp, 'demo-' . $type['code'] . '.jpg', null);
                    @unlink($tmp);

                    $staff = $this->firstStaffId();

                    if ($staff !== null && $n % 3 === 1) {
                        $service->verify($doc, 'valid', '', $staff);
                    } elseif ($staff !== null && $n % 3 === 2 && $t === 0) {
                        $service->verify($doc, 'invalid', 'Foto buram, tulisan tidak terbaca. Mohon foto ulang di tempat terang.', $staff);
                    }
                } catch (UploadRejected) {
                    continue;
                }
            }
        }
    }

    private function sampleImage(string $title, string $name): string
    {
        $img = imagecreatetruecolor(900, 600);
        imagefill($img, 0, 0, (int) imagecolorallocate($img, 245, 247, 250));
        $ink = (int) imagecolorallocate($img, 31, 41, 55);
        imagerectangle($img, 20, 20, 879, 579, $ink);
        imagestring($img, 5, 50, 60, 'CONTOH - ' . strtoupper($title), $ink);
        imagestring($img, 4, 50, 110, $name, $ink);
        imagestring($img, 3, 50, 540, 'Data demo SPMB, bukan dokumen asli.', $ink);
        $path = (string) tempnam(sys_get_temp_dir(), 'spmb');
        imagejpeg($img, $path, 85);

        return $path;
    }

    private function firstStaffId(): ?int
    {
        if ($this->staffId === null) {
            $row           = $this->db->table('users')->select('id')->orderBy('id', 'ASC')->get(1)->getRowArray();
            $this->staffId = $row === null ? null : (int) $row['id'];
        }

        return $this->staffId;
    }
}
