<?php

declare(strict_types=1);

namespace Modules\Berkas\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds the default type "Surat Keterangan Aktif dari SD/MI" (sk-aktif) to
 * installations seeded before it existed. Same rule as the InstallSeeder:
 * nothing happens when a type with that code exists, even a deleted one, so a
 * school that removed it keeps it removed. An empty table (not seeded yet) is
 * left to the seeder.
 *
 * The new type goes just before "rapor-skl" (that type and the ones after it
 * move down one place), or at the end when "rapor-skl" is gone. The row is a
 * copy of the InstallSeeder default, frozen here on purpose.
 */
class AddSkAktifDocumentType extends Migration
{
    public const CODE = 'sk-aktif';

    /**
     * @var array<string, mixed>
     */
    private const TYPE = [
        'code'        => self::CODE,
        'name'        => 'Surat Keterangan Aktif dari SD/MI',
        'description' => 'Surat dari kepala SD/MI yang menyatakan calon murid masih terdaftar sebagai murid di sekolah itu. Harus ada cap sekolah dan tanda tangan kepala sekolah.',
        'is_required' => 1,
        'may_follow'  => 1,
        'max_size_kb' => 2048,
        'extensions'  => 'jpg,jpeg,png,pdf',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('document_types')
            || $this->db->table('document_types')->countAllResults() === 0
            || $this->db->table('document_types')->where('code', self::CODE)->countAllResults() > 0) {
            return;
        }

        $rapor = $this->db->table('document_types')->where('code', 'rapor-skl')->where('deleted_at', null)->get()->getRowArray();

        if ($rapor !== null) {
            $order = (int) $rapor['sort_order'];
            $this->db->table('document_types')->set('sort_order', 'sort_order + 10', false)->where('sort_order >=', $order)->update();
        } else {
            $max   = $this->db->table('document_types')->selectMax('sort_order', 'max')->get()->getRowArray();
            $order = (int) ($max['max'] ?? 0) + 10;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('document_types')->insert(self::TYPE + [
            'sort_order' => $order,
            'is_active'  => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        // Kept on purpose: applicants may already have uploaded this document.
    }
}
