<?php

declare(strict_types=1);

namespace Modules\Berkas\Services;

use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Overall document status of an applicant (applicants.document_status, FR-DOC-04, D-036).
 *
 * - incomplete: a required document has no file (and is not "menyusul"), or was marked "perlu diganti"
 * - pending:    nothing is missing, but something waits for the committee's check
 * - to_follow:  only "menyusul" documents are left
 * - complete:   every required document is valid
 */
final class DocumentStatus
{
    public const INCOMPLETE = 'incomplete';
    public const PENDING    = 'pending';
    public const TO_FOLLOW  = 'to_follow';
    public const COMPLETE   = 'complete';

    /**
     * Recomputes and stores the status through ApplicantModel (audited only
     * when it changes). Returns the status.
     */
    public static function refresh(int $applicantId): string
    {
        $status = self::compute(
            model(DocumentTypeModel::class, false)->active(),
            model(ApplicantDocumentModel::class, false)->currentFor($applicantId),
        );

        $applicants = model(ApplicantModel::class, false);
        $current    = $applicants->select('id, document_status')->find($applicantId);

        if ($current !== null && $current['document_status'] !== $status) {
            $applicants->update($applicantId, ['document_status' => $status]);
        }

        return $status;
    }

    /**
     * Pure computation, unit-tested.
     *
     * @param list<array<string, mixed>>       $types     active document types
     * @param array<int, array<string, mixed>> $documents current documents keyed by document type id
     */
    public static function compute(array $types, array $documents): string
    {
        $pending  = false;
        $toFollow = false;

        foreach ($types as $type) {
            $doc      = $documents[(int) $type['id']] ?? null;
            $status   = $doc['status'] ?? null;
            $required = (bool) $type['is_required'];

            if ($status === 'pending') {
                $pending = true;
            }

            if (! $required) {
                continue;
            }

            if ($status === null || $status === 'invalid') {
                return self::INCOMPLETE;
            }

            if ($status === 'to_follow') {
                if (! (bool) $type['may_follow']) {
                    return self::INCOMPLETE;
                }

                $toFollow = true;
            }
        }

        if ($pending) {
            return self::PENDING;
        }

        return $toFollow ? self::TO_FOLLOW : self::COMPLETE;
    }
}
