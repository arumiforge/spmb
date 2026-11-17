<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AuditLogModel;
use CodeIgniter\HTTP\IncomingRequest;
use Config\Audit;
use Stringable;
use Throwable;

/**
 * Writes the audit trail. Use service('audit')->log(...) for explicit events
 * (login, export, status change) and the Auditable model trait for data changes.
 */
class AuditLogger
{
    public const REDACTED = '[disembunyikan]';

    public function __construct(
        private readonly AuditLogModel $model,
        private readonly Audit $config,
    ) {
    }

    /**
     * @param array<string, mixed>|null $old
     * @param array<string, mixed>|null $new
     *
     * @return int Inserted audit log ID
     */
    public function log(
        string $event,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
        ?int $userId = null,
    ): int {
        $request = service('request');
        $isHttp  = $request instanceof IncomingRequest;

        $this->model->insert([
            'user_id'      => $userId ?? $this->currentUserId(),
            'event'        => $event,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId === null ? null : (string) $subjectId,
            'description'  => $description === null ? null : mb_substr($description, 0, 255),
            'old_values'   => $old === null ? null : json_encode($this->redact($old), JSON_UNESCAPED_UNICODE),
            'new_values'   => $new === null ? null : json_encode($this->redact($new), JSON_UNESCAPED_UNICODE),
            'ip_address'   => $isHttp ? $request->getIPAddress() : null,
            'user_agent'   => $isHttp ? mb_substr((string) $request->getUserAgent(), 0, 255) : null,
            'url'          => $isHttp ? mb_substr((string) current_url(), 0, 255) : null,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->model->getInsertID();
    }

    /**
     * Returns only the columns whose values differ, as [old, new].
     *
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function diff(array $before, array $after): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            if (in_array($key, $this->config->ignore, true)) {
                continue;
            }

            $previous = $before[$key] ?? null;

            if ($this->normalise($previous) !== $this->normalise($value)) {
                $old[$key] = $previous;
                $new[$key] = $value;
            }
        }

        return [$old, $new];
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array($key, $this->config->redact, true)) {
                $values[$key] = self::REDACTED;
            }
        }

        return $values;
    }

    private function normalise(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return json_encode($value) ?: null;
    }

    private function currentUserId(): ?int
    {
        try {
            $auth = auth('session');

            return $auth->loggedIn() ? (int) $auth->id() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
