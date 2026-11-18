<?php

declare(strict_types=1);

namespace Modules\Berkas\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Modules\Berkas\Models\DocumentTypeModel;

/**
 * Default document types, used until the school confirms its list
 * (docs/phase-2-plan.md, open questions). Idempotent: a type whose code
 * already exists (even if deleted or changed by an admin) is left alone.
 */
class InstallSeeder extends Seeder
{
    /**
     * @var list<array<string, mixed>>
     */
    public const DEFAULTS = [
        ['code' => 'kk', 'name' => 'Kartu Keluarga', 'description' => 'Foto atau scan Kartu Keluarga. Semua tulisan harus terbaca jelas.', 'is_required' => 1, 'may_follow' => 0, 'max_size_kb' => 2048, 'extensions' => 'jpg,jpeg,png,pdf'],
        ['code' => 'akta', 'name' => 'Akta Kelahiran', 'description' => 'Foto atau scan Akta Kelahiran calon murid.', 'is_required' => 1, 'may_follow' => 0, 'max_size_kb' => 2048, 'extensions' => 'jpg,jpeg,png,pdf'],
        ['code' => 'foto', 'name' => 'Pas Foto', 'description' => 'Foto wajah terbaru, tampak depan, latar polos.', 'is_required' => 1, 'may_follow' => 0, 'max_size_kb' => 1024, 'extensions' => 'jpg,jpeg,png'],
        ['code' => 'rapor-skl', 'name' => 'Rapor / Surat Keterangan Lulus', 'description' => 'Rapor kelas 6 semester terakhir atau Surat Keterangan Lulus. Boleh dikirim menyusul.', 'is_required' => 1, 'may_follow' => 1, 'max_size_kb' => 5120, 'extensions' => 'jpg,jpeg,png,pdf'],
    ];

    public function run(): void
    {
        $model = model(DocumentTypeModel::class, false);
        $order = 0;

        foreach (self::DEFAULTS as $type) {
            $order += 10;

            if ($model->withDeleted()->where('code', $type['code'])->countAllResults() > 0) {
                continue;
            }

            $model->insert($type + ['sort_order' => $order, 'is_active' => 1]);
        }
    }
}
