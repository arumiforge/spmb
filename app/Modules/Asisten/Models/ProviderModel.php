<?php

declare(strict_types=1);

namespace Modules\Asisten\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;

/**
 * OpenAI-compatible AI connections (table asisten_providers, Super Admin, D-110).
 *
 * api_key holds the key encrypted with the app encryption key (base64), never the key itself; only
 * Modules\Asisten\Libraries\ProviderKeys reads or writes it. Changes are logged by the controller
 * without the key (not through the Auditable trait, so the encrypted value never reaches the log).
 */
class ProviderModel extends Model
{
    protected $table         = 'asisten_providers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name', 'base_url', 'model', 'api_key', 'key_hint', 'timeout_seconds', 'max_tokens', 'temperature',
        'is_active', 'sort_order', 'used_seq', 'last_used_at', 'rested_until', 'last_error', 'last_error_at',
    ];

    /**
     * Every connection in display order.
     *
     * @return list<array<string, mixed>>
     */
    public function ordered(): array
    {
        return $this->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Active connections that are not resting, least recently used first (round robin), then in
     * display order.
     *
     * @return list<array<string, mixed>>
     */
    public function rotation(?string $now = null): array
    {
        $now ??= Time::now()->toDateTimeString();

        return $this->where('is_active', 1)
            ->where('api_key IS NOT NULL')
            ->groupStart()->where('rested_until', null)->orWhere('rested_until <=', $now)->groupEnd()
            ->orderBy('used_seq', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Whether at least one active connection with a key exists (resting ones included).
     */
    public function hasActive(): bool
    {
        return $this->where('is_active', 1)->where('api_key IS NOT NULL')->countAllResults() > 0;
    }

    /**
     * Marks a connection as just used: it goes to the end of the rotation.
     */
    public function markUsed(int $id): void
    {
        $table = $this->db->prefixTable($this->table);
        $this->db->query(
            'UPDATE ' . $table . ' SET used_seq = (SELECT next_seq FROM (SELECT COALESCE(MAX(used_seq), 0) + 1 AS next_seq FROM ' . $table . ') AS t),'
            . ' last_used_at = ?, rested_until = NULL WHERE id = ?',
            [Time::now()->toDateTimeString(), $id],
        );
    }

    /**
     * Puts a connection to rest for $minutes after a failure and keeps the (key-free) reason.
     */
    public function rest(int $id, int $minutes, string $reason): void
    {
        $now = Time::now();
        $this->builder()->where('id', $id)->update([
            'rested_until'  => $now->addMinutes(max(1, $minutes))->toDateTimeString(),
            'last_error'    => mb_substr($reason, 0, 255),
            'last_error_at' => $now->toDateTimeString(),
        ]);
    }

    public function nextSortOrder(): int
    {
        $row = $this->builder()->selectMax('sort_order', 'max')->get()->getRowArray();

        return (int) ($row['max'] ?? 0) + 10;
    }
}
