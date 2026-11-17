<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Append-only audit trail. Rows are never updated or deleted by the app.
 */
class AuditLogModel extends Model
{
    protected $table          = 'audit_logs';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = false;
    protected $useSoftDeletes = false;
    protected $allowedFields  = [
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'created_at',
    ];

    /**
     * History of one record, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function forSubject(string $type, int|string $id): array
    {
        return $this->where('subject_type', $type)
            ->where('subject_id', (string) $id)
            ->orderBy('id', 'DESC')
            ->findAll();
    }
}
