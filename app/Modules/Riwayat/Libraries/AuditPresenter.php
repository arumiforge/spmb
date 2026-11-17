<?php

declare(strict_types=1);

namespace Modules\Riwayat\Libraries;

use App\Libraries\AuditLogger;
use DateTimeImmutable;

/**
 * Turns raw audit_logs rows into plain Indonesian for the history pages:
 * event and subject labels, field labels, readable old/new values.
 *
 * Unknown events, subjects and fields fall back to a tidied version of the
 * raw key, so new modules show up readably before they add a label.
 */
class AuditPresenter
{
    /**
     * Colour of the event badge (text is always shown too).
     */
    private const EVENT_COLORS = [
        'created'          => 'green',
        'updated'          => 'blue',
        'deleted'          => 'red',
        'purged'           => 'red',
        'user_deactivated' => 'red',
        'user_activated'   => 'green',
        '2fa_failed'       => 'yellow',
        '2fa_disabled'     => 'yellow',
        '2fa_enabled'      => 'green',
    ];

    /**
     * Fields whose values are role keys (shown as role titles).
     */
    private const ROLE_FIELDS = ['roles', 'requiredRoles'];

    /**
     * Fields stored as 0/1 (shown as Ya/Tidak).
     */
    private const BOOL_FIELDS = ['active'];

    /**
     * @param array<string, string>                $roleTitles   Role key => title (Config\AuthGroups)
     * @param array<string, array<string, string>> $moduleLabels Labels from modules' Config/Audit.php,
     *                                                           by section (events, subjects, settingGroups, fields,
     *                                                           eventColors, boolFields)
     */
    public function __construct(private readonly array $roleTitles = [], private readonly array $moduleLabels = [])
    {
    }

    public static function fromConfig(): self
    {
        return new self(
            array_map(static fn (array $g): string => $g['title'], config('AuthGroups')->groups),
            self::moduleLabels(service('modules')->all()),
        );
    }

    /**
     * Collects Config/Audit.php of every module, switched on or off (old
     * history must stay readable). Riwayat's own language file wins on a clash.
     *
     * @param list<string> $modules
     *
     * @return array<string, array<string, string>>
     */
    public static function moduleLabels(array $modules, string $path = APPPATH . 'Modules'): array
    {
        $labels = [];

        foreach ($modules as $module) {
            $file = rtrim($path, '/') . '/' . $module . '/Config/Audit.php';
            $data = is_file($file) ? require $file : [];

            foreach (is_array($data) ? $data : [] as $section => $entries) {
                if (is_array($entries)) {
                    $labels[$section] = ($labels[$section] ?? []) + array_map('strval', $entries);
                }
            }
        }

        return $labels;
    }

    public function eventLabel(string $event): string
    {
        return $this->lookup('Riwayat.events.' . $event, $event);
    }

    public function eventColor(string $event): string
    {
        return self::EVENT_COLORS[$event] ?? $this->moduleLabels['eventColors'][$event] ?? 'gray';
    }

    public function subjectLabel(?string $type): string
    {
        if ($type === null || $type === '') {
            return lang('Riwayat.noSubject');
        }

        return $this->lookup('Riwayat.subjects.' . $type, $type);
    }

    public function fieldLabel(string $field): string
    {
        return $this->lookup('Riwayat.fields.' . $field, $field);
    }

    /**
     * Readable name of one record, e.g. "Pengguna · Siti Aminah" or "Pengaturan · Keamanan".
     *
     * @param array<int, string> $userNames user id => display name
     */
    public function subjectName(?string $type, ?string $id, array $userNames = []): string
    {
        if ($id === null || $id === '') {
            return $this->subjectLabel($type);
        }

        $name = match ($type) {
            'users'    => $userNames[(int) $id] ?? lang('Riwayat.deletedUser', [$id]),
            'settings' => $this->lookup('Riwayat.settingGroups.' . $id, $id),
            default    => '#' . $id,
        };

        return $this->subjectLabel($type) . ' · ' . $name;
    }

    /**
     * Decodes the JSON value columns of a row.
     *
     * @param array<string, mixed> $row
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public function values(array $row): array
    {
        $decode = static function (mixed $json): array {
            if (! is_string($json) || $json === '') {
                return [];
            }

            $data = json_decode($json, true);

            return is_array($data) ? $data : ['nilai' => $data];
        };

        return [$decode($row['old_values'] ?? null), $decode($row['new_values'] ?? null)];
    }

    /**
     * One line per changed field: label, old and new value as display text.
     *
     * @param array<string, mixed> $row
     *
     * @return list<array{field: string, label: string, old: string|null, new: string|null}>
     */
    public function changes(array $row): array
    {
        [$old, $new] = $this->values($row);
        $changes     = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $field) {
            $field     = (string) $field;
            $changes[] = [
                'field' => $field,
                'label' => $this->fieldLabel($field),
                'old'   => array_key_exists($field, $old) ? $this->format($field, $old[$field]) : null,
                'new'   => array_key_exists($field, $new) ? $this->format($field, $new[$field]) : null,
            ];
        }

        return $changes;
    }

    /**
     * Short text for the list: the description, or the changed field labels.
     *
     * @param array<string, mixed> $row
     */
    public function summary(array $row): string
    {
        $description = trim((string) ($row['description'] ?? ''));

        if ($description !== '') {
            return $description;
        }

        $labels = array_column($this->changes($row), 'label');

        if ($labels === []) {
            return $this->eventLabel((string) $row['event']);
        }

        $shown = array_slice($labels, 0, 3);
        $more  = count($labels) - count($shown);

        $key = ($row['event'] ?? '') === 'created' ? 'Riwayat.createdFields' : 'Riwayat.changedFields';

        return lang($key, [implode(', ', $shown)]) . ($more > 0 ? ' ' . lang('Riwayat.andMore', [$more]) : '');
    }

    /**
     * Display text for one stored value.
     */
    public function format(string $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return lang('Riwayat.empty');
        }

        if ($value === AuditLogger::REDACTED) {
            return lang('Riwayat.hidden');
        }

        if ((in_array($field, self::BOOL_FIELDS, true) || in_array($field, $this->moduleLabels['boolFields'] ?? [], true)) && in_array((string) $value, ['0', '1'], true)) {
            $value = (string) $value === '1';
        }

        if (is_bool($value)) {
            return $value ? lang('App.yes') : lang('App.no');
        }

        if (is_array($value)) {
            if (in_array($field, self::ROLE_FIELDS, true)) {
                $value = array_map(fn (mixed $v): mixed => is_string($v) ? ($this->roleTitles[$v] ?? $v) : $v, $value);
            }

            if (array_is_list($value) && array_filter($value, is_scalar(...)) === $value) {
                return implode(', ', array_map('strval', $value));
            }

            return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $text = (string) $value;

        if (preg_match('/\A\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\z/', $text) === 1) {
            return tanggal_indo(new DateTimeImmutable($text), true);
        }

        return $text;
    }

    private function lookup(string $key, string $fallback): string
    {
        $text = lang($key);

        if ($text !== $key) {
            return $text;
        }

        // Riwayat.<section>.<name>: fall back to the labels a module declares.
        $parts = explode('.', $key, 3);

        return $this->moduleLabels[$parts[1] ?? ''][$parts[2] ?? '']
            ?? ucfirst(str_replace(['_', '-', '.'], ' ', $fallback));
    }
}
