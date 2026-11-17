<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Records inserts, updates and deletes made through a CodeIgniter Model in
 * the audit log, including old and new values of changed columns.
 *
 * Usage in a model:
 *
 *     use Auditable;
 *
 *     protected function initialize(): void
 *     {
 *         $this->registerAuditCallbacks();
 *     }
 *
 * Only changes made through the model (insert/update/delete/save) are
 * captured; raw query builder writes bypass it.
 */
trait Auditable
{
    /**
     * Rows as they were before the current update/delete, keyed by primary key.
     *
     * @var array<int|string, array<string, mixed>>
     */
    private array $auditBefore = [];

    protected function registerAuditCallbacks(): void
    {
        $this->allowCallbacks = true;

        $this->beforeUpdate[] = 'auditCaptureBefore';
        $this->beforeDelete[] = 'auditCaptureBefore';
        $this->afterInsert[]  = 'auditAfterInsert';
        $this->afterUpdate[]  = 'auditAfterUpdate';
        $this->afterDelete[]  = 'auditAfterDelete';
    }

    /**
     * Subject type stored in the audit log. Override for a friendlier name.
     */
    protected function auditSubjectType(): string
    {
        return $this->table;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function auditCaptureBefore(array $data): array
    {
        $this->auditBefore = [];
        $ids               = $this->auditIds($data['id'] ?? null);

        if ($ids === []) {
            return $data;
        }

        // Query the table directly so soft-deleted rows are included.
        $rows = $this->db->table($this->table)
            ->whereIn($this->primaryKey, $ids)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $this->auditBefore[$row[$this->primaryKey]] = $row;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function auditAfterInsert(array $data): array
    {
        if (empty($data['result'])) {
            return $data;
        }

        service('audit')->log(
            'created',
            $this->auditSubjectType(),
            $data['id'] ?? null,
            null,
            $this->auditValues($data['data'] ?? []),
        );

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function auditAfterUpdate(array $data): array
    {
        if (empty($data['result'])) {
            $this->auditBefore = [];

            return $data;
        }

        $values = $this->auditValues($data['data'] ?? []);

        foreach ($this->auditBefore as $id => $before) {
            [$old, $new] = service('audit')->diff($before, $values);

            if ($new !== []) {
                service('audit')->log('updated', $this->auditSubjectType(), $id, $old, $new);
            }
        }

        $this->auditBefore = [];

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function auditAfterDelete(array $data): array
    {
        if (empty($data['result'])) {
            $this->auditBefore = [];

            return $data;
        }

        $event = ($this->useSoftDeletes && empty($data['purge'])) ? 'deleted' : 'purged';

        foreach ($this->auditBefore as $id => $before) {
            service('audit')->log($event, $this->auditSubjectType(), $id, $before, null);
        }

        $this->auditBefore = [];

        return $data;
    }

    /**
     * @return list<int|string>
     */
    private function auditIds(mixed $id): array
    {
        if ($id === null || $id === []) {
            return [];
        }

        return array_values((array) $id);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(mixed $values): array
    {
        if (is_object($values) && method_exists($values, 'toRawArray')) {
            return $values->toRawArray(true);
        }

        return (array) $values;
    }
}
