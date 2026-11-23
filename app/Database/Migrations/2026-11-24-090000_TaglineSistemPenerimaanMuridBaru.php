<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The official name is "Sistem Penerimaan Murid Baru (SPMB)" (D-093). Installations that still carry
 * the old default tagline "Penerimaan Murid Baru" get the new one; a tagline the school wrote itself
 * is left alone. down() restores the old default only where this migration set it.
 */
class TaglineSistemPenerimaanMuridBaru extends Migration
{
    private const OLD = 'Penerimaan Murid Baru';
    private const NEW = 'Sistem Penerimaan Murid Baru (SPMB)';

    public function up(): void
    {
        $this->swap(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->swap(self::NEW, self::OLD);
    }

    private function swap(string $from, string $to): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->where('class', 'Config\School')
            ->where('key', 'tagline')
            ->where('value', $from)
            ->update(['value' => $to, 'updated_at' => date('Y-m-d H:i:s')]);
    }
}
