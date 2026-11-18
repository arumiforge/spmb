<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * What happens when a wave's quota is reached, chosen per wave (D-047):
 * none (quota is information only), close (online form closes at the quota),
 * online_limit (online form closes at a separate online limit).
 */
class AddQuotaModeToWaves extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('waves', [
            'quota_mode'   => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'none', 'after' => 'quota', 'comment' => 'none | close | online_limit'],
            'online_quota' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'quota_mode'],
        ]);

        // Until now a quota always closed the online form.
        $this->db->table('waves')->where('quota IS NOT NULL')->update(['quota_mode' => 'close']);
    }

    public function down(): void
    {
        $this->forge->dropColumn('waves', ['quota_mode', 'online_quota']);
    }
}
