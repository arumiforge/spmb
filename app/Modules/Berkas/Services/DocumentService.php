<?php

declare(strict_types=1);

namespace Modules\Berkas\Services;

use Modules\Berkas\Models\ApplicantDocumentModel;
use Throwable;

/**
 * Document actions for applicants and staff: upload (or replace), mark as
 * "menyusul" and verify. Every change goes through ApplicantDocumentModel and is
 * recorded in the audit log with one event per action (document_uploaded,
 * document_replaced, document_verified); applicants.document_status is
 * recomputed afterwards (DocumentStatus::refresh).
 */
class DocumentService
{
    private readonly ApplicantDocumentModel $documents;

    public function __construct(
        private readonly DocumentStorage $storage = new DocumentStorage(),
        ?ApplicantDocumentModel $documents = null,
    ) {
        $this->documents = $documents ?? model(ApplicantDocumentModel::class, false);
    }

    public function storage(): DocumentStorage
    {
        return $this->storage;
    }

    /**
     * Stores a file for one document type and makes it the current document
     * (status pending). An earlier document of that type is soft-deleted.
     *
     * @param array<string, mixed> $applicant applicants row
     * @param array<string, mixed> $type      document_types row
     * @param int|null             $staffId   staff user, or null for the applicant
     *
     * @return array<string, mixed> the new applicant_documents row
     *
     * @throws UploadRejected
     */
    public function upload(array $applicant, array $type, string $tmpPath, string $clientName, ?int $staffId): array
    {
        $applicantId = (int) $applicant['id'];
        $current     = $this->documents->currentOf($applicantId, (int) $type['id']);

        if ($staffId === null) {
            $this->assertApplicantMayChange($applicant, $current);
        }

        $stored = $this->storage->store($tmpPath, $clientName, $type);

        $row = $stored + [
            'applicant_id'     => $applicantId,
            'document_type_id' => (int) $type['id'],
            'status'           => 'pending',
            'uploaded_by'      => $staffId,
        ];

        $db = $this->documents->db;
        $db->transStart();

        try {
            $this->retire($current);
            $id = (int) $this->documents->allowCallbacks(false)->insert($row);
            service('audit')->log('document_uploaded', 'applicant_documents', $id, null, $row, $this->describe($applicant, $type), $staffId);
        } catch (Throwable $e) {
            $db->transRollback();
            $this->storage->delete($stored['file_path']);

            throw $e;
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            $this->storage->delete($stored['file_path']);

            throw new UploadRejected(lang('Berkas.errUploadFailed'));
        }

        DocumentStatus::refresh($applicantId);

        return $this->documents->find($id) ?? $row;
    }

    /**
     * Records that a document will be handed in later ("menyusul"), when the
     * type allows it and nothing usable is there yet.
     *
     * @param array<string, mixed> $applicant
     * @param array<string, mixed> $type
     *
     * @return array<string, mixed>
     *
     * @throws UploadRejected
     */
    public function markToFollow(array $applicant, array $type, ?int $staffId): array
    {
        if (! (bool) $type['may_follow']) {
            throw new UploadRejected(lang('Berkas.errCannotFollow'));
        }

        $applicantId = (int) $applicant['id'];
        $current     = $this->documents->currentOf($applicantId, (int) $type['id']);

        if ($staffId === null) {
            $this->assertApplicantMayChange($applicant, $current);
        }

        if ($current !== null && $current['status'] === 'to_follow') {
            return $current;
        }

        if ($current !== null && $current['status'] !== 'invalid') {
            throw new UploadRejected(lang('Berkas.errAlreadyUploaded'));
        }

        $row = [
            'applicant_id'     => $applicantId,
            'document_type_id' => (int) $type['id'],
            'status'           => 'to_follow',
            'uploaded_by'      => $staffId,
        ];

        $db = $this->documents->db;
        $db->transStart();
        $this->retire($current);
        $id = (int) $this->documents->allowCallbacks(false)->insert($row);
        service('audit')->log('document_to_follow', 'applicant_documents', $id, null, $row, $this->describe($applicant, $type), $staffId);
        $db->transComplete();

        DocumentStatus::refresh($applicantId);

        return $this->documents->find($id) ?? $row;
    }

    /**
     * The committee's check of one document: valid, or invalid with a note
     * telling the applicant what to fix.
     *
     * @param array<string, mixed> $document applicant_documents row
     *
     * @return array<string, mixed>
     *
     * @throws UploadRejected
     */
    public function verify(array $document, string $status, string $note, int $staffId): array
    {
        $note = trim($note);

        if (! in_array($status, ['valid', 'invalid'], true) || $document['file_path'] === null) {
            throw new UploadRejected(lang('Berkas.errCannotVerify'));
        }

        if ($status === 'invalid' && $note === '') {
            throw new UploadRejected(lang('Berkas.errNoteRequired'));
        }

        $changes = [
            'status'      => $status,
            'note'        => $note === '' ? null : mb_substr($note, 0, 255),
            'verified_by' => $staffId,
            'verified_at' => date('Y-m-d H:i:s'),
        ];

        $this->documents->allowCallbacks(false)->update((int) $document['id'], $changes);
        [$old, $new] = service('audit')->diff($document, $changes);
        service('audit')->log('document_verified', 'applicant_documents', (int) $document['id'], $old, $new, lang('Status.document.' . $status), $staffId);

        DocumentStatus::refresh((int) $document['applicant_id']);

        return $this->documents->find((int) $document['id']) ?? ($changes + $document);
    }

    /**
     * Applicants may not change a document the committee already accepted,
     * nor anything once their registration is cancelled.
     *
     * @param array<string, mixed>      $applicant
     * @param array<string, mixed>|null $current
     *
     * @throws UploadRejected
     */
    private function assertApplicantMayChange(array $applicant, ?array $current): void
    {
        if (($applicant['status'] ?? '') === 'cancelled') {
            throw new UploadRejected(lang('Berkas.errCancelled'));
        }

        if ($current !== null && $current['status'] === 'valid') {
            throw new UploadRejected(lang('Berkas.errAlreadyValid'));
        }
    }

    /**
     * Soft-deletes the current document (its file stays for the record).
     *
     * @param array<string, mixed>|null $current
     */
    private function retire(?array $current): void
    {
        if ($current === null) {
            return;
        }

        $this->documents->allowCallbacks(false)->delete((int) $current['id']);
        service('audit')->log('document_replaced', 'applicant_documents', (int) $current['id'], array_intersect_key($current, array_flip([
            'applicant_id', 'document_type_id', 'file_path', 'original_name', 'status', 'note',
        ])), null);
    }

    /**
     * @param array<string, mixed> $applicant
     * @param array<string, mixed> $type
     */
    private function describe(array $applicant, array $type): string
    {
        return $type['name'] . ' — ' . ($applicant['registration_number'] ?? ('#' . $applicant['id']));
    }
}
