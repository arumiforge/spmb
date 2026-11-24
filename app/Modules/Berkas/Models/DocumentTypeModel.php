<?php

declare(strict_types=1);

namespace Modules\Berkas\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;
use Modules\Berkas\Services\TemplateStorage;

/**
 * Document types applicants hand in (table document_types). Owner: work package C (Berkas).
 *
 * Created with the phase 2 foundation as a shared interface (docs/phase-2-plan.md).
 * The owning work package may add methods and validation; other packages use
 * only the documented methods and must not change existing signatures.
 */
class DocumentTypeModel extends Model
{
    use Auditable;

    /**
     * File formats an admin can allow, keyed by the value stored in
     * document_types.extensions (csv), with the real MIME types they accept.
     *
     * @var array<string, list<string>>
     */
    public const FORMATS = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'pdf'  => ['application/pdf'],
    ];

    protected $table          = 'document_types';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'code', 'name', 'description', 'is_required', 'may_follow', 'max_size_kb', 'extensions', 'sort_order', 'is_active',
        'template_path', 'template_name', 'template_enabled', 'template_label',
    ];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    /**
     * Active document types in display order (for /persyaratan, the upload
     * panels and the status computation).
     *
     * @return list<array<string, mixed>>
     */
    public function active(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Every type (active or not) in display order, for the settings page.
     *
     * @return list<array<string, mixed>>
     */
    public function ordered(): array
    {
        return $this->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    public function nextSortOrder(): int
    {
        $row = $this->builder()->selectMax('sort_order', 'max')->get()->getRowArray();

        return (int) ($row['max'] ?? 0) + 10;
    }

    /**
     * Allowed extensions of a type, lowercase.
     *
     * @param array<string, mixed> $type
     *
     * @return list<string>
     */
    public static function extensionsOf(array $type): array
    {
        $list = array_map(static fn (string $e): string => strtolower(trim($e)), explode(',', (string) ($type['extensions'] ?? '')));

        return array_values(array_unique(array_filter($list, static fn (string $e): bool => isset(self::FORMATS[$e]))));
    }

    /**
     * Real MIME types accepted for a type.
     *
     * @param array<string, mixed> $type
     *
     * @return list<string>
     */
    public static function mimesOf(array $type): array
    {
        $mimes = [];

        foreach (self::extensionsOf($type) as $ext) {
            array_push($mimes, ...self::FORMATS[$ext]);
        }

        return array_values(array_unique($mimes));
    }

    /**
     * Value for the accept attribute of a file input. Images accept image/* so
     * phones offer the camera; the server still checks the real format.
     *
     * @param array<string, mixed> $type
     */
    public static function acceptOf(array $type): string
    {
        $accept = [];
        $mimes  = self::mimesOf($type);

        if (array_intersect($mimes, ['image/jpeg', 'image/png']) !== []) {
            $accept[] = 'image/*';
        }

        foreach (self::extensionsOf($type) as $ext) {
            $accept[] = '.' . $ext;
        }

        if (in_array('application/pdf', $mimes, true)) {
            $accept[] = 'application/pdf';
        }

        return implode(',', $accept);
    }

    /**
     * Friendly list of formats, e.g. "JPG, PNG, atau PDF".
     *
     * @param array<string, mixed> $type
     */
    public static function formatLabel(array $type): string
    {
        $labels = [];

        foreach (self::extensionsOf($type) as $ext) {
            $labels[] = $ext === 'jpeg' ? 'JPG' : strtoupper($ext);
        }

        $labels = array_values(array_unique($labels));

        if (count($labels) <= 1) {
            return $labels[0] ?? '-';
        }

        $last = array_pop($labels);

        return implode(', ', $labels) . ' ' . lang('Berkas.or') . ' ' . $last;
    }

    /**
     * True when the type has an example file stored, shown or not.
     *
     * @param array<string, mixed> $type
     */
    public static function hasTemplate(array $type): bool
    {
        return (new TemplateStorage())->path($type['template_path'] ?? null) !== null;
    }

    /**
     * True when applicants may download the example file: the type is
     * active, the school switched the example on and the file exists.
     *
     * @param array<string, mixed> $type
     */
    public static function templateShown(array $type): bool
    {
        return (bool) ($type['is_active'] ?? false) && (bool) ($type['template_enabled'] ?? false) && self::hasTemplate($type);
    }

    /**
     * Link text for the example file ("Unduh contoh" unless the school wrote its own).
     *
     * @param array<string, mixed> $type
     */
    public static function templateLabel(array $type): string
    {
        $label = trim((string) ($type['template_label'] ?? ''));

        return $label !== '' ? $label : lang('Berkas.templateDefaultLabel');
    }
}
