<?php

declare(strict_types=1);

namespace Modules\Kartu\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;

/**
 * QR tokens of registration cards (table card_tokens). Owner: work package D (Kartu).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class CardTokenModel extends Model
{
    use Auditable;

    protected $table          = 'card_tokens';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps  = true;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'applicant_id', 'token', 'created_by', 'revoked_at', 'last_seen_at',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }
}
