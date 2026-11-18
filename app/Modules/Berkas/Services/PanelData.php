<?php

declare(strict_types=1);

namespace Modules\Berkas\Services;

use DateTimeImmutable;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;

/**
 * Data for the applicant panels and the review screen: one row per active
 * document type with the applicant's current document.
 */
final class PanelData
{
    /**
     * @return list<array{type: array<string, mixed>, doc: array<string, mixed>|null, verifier: string|null}>
     */
    public static function rows(int $applicantId): array
    {
        $types = model(DocumentTypeModel::class, false)->active();
        $docs  = model(ApplicantDocumentModel::class, false)->currentFor($applicantId);
        $names = self::staffNames(array_column($docs, 'verified_by'));
        $rows  = [];

        foreach ($types as $type) {
            $doc    = $docs[(int) $type['id']] ?? null;
            $rows[] = [
                'type'     => $type,
                'doc'      => $doc,
                'verifier' => $doc !== null && $doc['verified_by'] !== null ? ($names[(int) $doc['verified_by']] ?? null) : null,
            ];
        }

        return $rows;
    }

    /**
     * Deadline for "menyusul" documents as an Indonesian date, or null.
     */
    public static function deadline(): ?string
    {
        $value = (string) setting('Berkas.deadline');

        if ($value === '' || DateTimeImmutable::createFromFormat('!Y-m-d', $value) === false) {
            return null;
        }

        return tanggal_indo($value);
    }

    /**
     * Display names of staff users.
     *
     * @param list<mixed> $ids
     *
     * @return array<int, string>
     */
    public static function staffNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        $rows = db_connect()->table('users')->select('id, full_name, username')->whereIn('id', $ids)->get()->getResultArray();
        $out  = [];

        foreach ($rows as $row) {
            $out[(int) $row['id']] = (string) ($row['full_name'] ?: $row['username']);
        }

        return $out;
    }
}
