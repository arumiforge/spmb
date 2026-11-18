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
 * Issue and revoke tokens through Modules\Kartu\Services\CardTokenService.
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

    /**
     * The applicant's current (not revoked) token row, or null.
     *
     * @return array<string, mixed>|null
     */
    public function activeFor(int $applicantId): ?array
    {
        return $this->where('applicant_id', $applicantId)
            ->where('revoked_at', null)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Token row by its exact value (revoked or not), or null.
     *
     * @return array<string, mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        if ($token === '' || strlen($token) > 64) {
            return null;
        }

        return $this->where('token', $token)->first();
    }

    /**
     * Records when a card was last scanned. A technical timestamp, not applicant
     * data, so it is written without an audit entry (one per scan would drown Riwayat).
     */
    public function touch(int $id): void
    {
        $this->builder()->where('id', $id)->update(['last_seen_at' => date('Y-m-d H:i:s')]);
    }
}
