<?php

declare(strict_types=1);

namespace Modules\Kartu\Services;

use CodeIgniter\Database\BaseConnection;
use Modules\Kartu\Models\CardTokenModel;
use Modules\Pendaftaran\Models\ApplicantModel;
use RuntimeException;

/**
 * Random, revocable card tokens (FR-CARD-02, D-034). The QR on a card holds
 * only site_url('kartu/<token>'); the token is 32 random bytes, base64url,
 * never NIK/NISN or a sequential id.
 *
 * Writes skip the generic "created/updated" audit callbacks and log one
 * meaningful event instead (card_issued, card_revoked) on the applicant.
 */
class CardTokenService
{
    private readonly CardTokenModel $tokens;
    private readonly BaseConnection $db;

    public function __construct(?CardTokenModel $tokens = null)
    {
        $this->tokens = $tokens ?? model(CardTokenModel::class);
        $this->db     = db_connect();
    }

    /**
     * The applicant's active token, or a new one when they have none yet
     * (first print or download). Reprinting reuses the same token.
     */
    public function tokenFor(int $applicantId, ?int $staffId = null): string
    {
        $active = $this->tokens->activeFor($applicantId);

        if ($active !== null) {
            return (string) $active['token'];
        }

        $this->db->transException(true)->transStart();

        try {
            // Serialise concurrent first prints of the same applicant.
            $this->lockApplicant($applicantId);
            $active = $this->tokens->activeFor($applicantId);
            $token  = $active !== null ? (string) $active['token'] : $this->issue($applicantId, $staffId);
            $this->db->transComplete();
        } finally {
            $this->db->transException(false);
        }

        return $token;
    }

    /**
     * Revokes the current token (a lost or misused card) and issues a new one.
     */
    public function renew(int $applicantId, int $staffId): string
    {
        $this->db->transException(true)->transStart();

        try {
            $this->lockApplicant($applicantId);
            $active = $this->tokens->activeFor($applicantId);

            if ($active !== null) {
                $this->tokens->allowCallbacks(false)->update($active['id'], ['revoked_at' => date('Y-m-d H:i:s')]);
                service('audit')->log('card_revoked', 'applicants', $applicantId, null, ['card' => (int) $active['id']], 'Kartu lama tidak berlaku lagi', $staffId);
            }

            $token = $this->issue($applicantId, $staffId);
            $this->db->transComplete();
        } finally {
            $this->db->transException(false);
        }

        return $token;
    }

    /**
     * The applicant behind a card token, or null when the token is unknown,
     * revoked, or the applicant was deleted. Records the scan time.
     *
     * @return array<string, mixed>|null
     */
    public function resolve(string $token): ?array
    {
        $row = $this->tokens->findByToken($token);

        if ($row === null || $row['revoked_at'] !== null) {
            return null;
        }

        $applicant = model(ApplicantModel::class)->find((int) $row['applicant_id']);

        if ($applicant === null) {
            return null;
        }

        $this->tokens->touch((int) $row['id']);

        return $applicant;
    }

    /**
     * Whether $token was issued once and has since been revoked (an old card).
     */
    public function isRevoked(string $token): bool
    {
        $row = $this->tokens->findByToken($token);

        return $row !== null && $row['revoked_at'] !== null;
    }

    /**
     * 32 random bytes, base64url without padding (43 characters).
     */
    public static function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function issue(int $applicantId, ?int $staffId): string
    {
        $token = self::generate();
        $id    = $this->tokens->allowCallbacks(false)->insert([
            'applicant_id' => $applicantId,
            'token'        => $token,
            'created_by'   => $staffId,
        ]);

        if ($id === false) {
            throw new RuntimeException('Card token could not be saved.');
        }

        service('audit')->log('card_issued', 'applicants', $applicantId, null, ['card' => (int) $id], 'Kartu pendaftaran dibuat', $staffId);

        return $token;
    }

    private function lockApplicant(int $applicantId): void
    {
        $exists = $this->db->query(
            'SELECT id FROM ' . $this->db->protectIdentifiers('applicants', true) . ' WHERE id = ? FOR UPDATE',
            [$applicantId],
        )->getRowArray();

        if ($exists === null) {
            throw new RuntimeException('Applicant not found.');
        }
    }
}
