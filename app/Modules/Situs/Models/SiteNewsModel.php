<?php

declare(strict_types=1);

namespace Modules\Situs\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use Throwable;

/**
 * Items of the news ticker under the public header (table site_news).
 * Changes are audited (subject site_news).
 */
class SiteNewsModel extends Model
{
    use Auditable;

    protected $table          = 'site_news';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['text', 'text_en', 'text_ar', 'url', 'starts_on', 'ends_on', 'is_active', 'sort_order'];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    /**
     * Every item in display order (active or not).
     *
     * @return list<array<string, mixed>>
     */
    public function ordered(): array
    {
        return $this->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Active items whose date range covers $today (Y-m-d, inclusive), in order.
     * Empty when the table does not exist yet (before migrations).
     *
     * @return list<array<string, mixed>>
     */
    public function visible(?string $today = null): array
    {
        $today ??= Time::today()->toDateString();

        try {
            return $this->where('is_active', 1)
                ->groupStart()->where('starts_on', null)->orWhere('starts_on <=', $today)->groupEnd()
                ->groupStart()->where('ends_on', null)->orWhere('ends_on >=', $today)->groupEnd()
                ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')
                ->findAll();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Whether an item shows today: active and inside its dates.
     *
     * @param array<string, mixed> $item
     */
    public static function isShowing(array $item, ?string $today = null): bool
    {
        $today ??= Time::today()->toDateString();
        $from  = (string) ($item['starts_on'] ?? '');
        $until = (string) ($item['ends_on'] ?? '');

        return (bool) $item['is_active'] && ($from === '' || $from <= $today) && ($until === '' || $until >= $today);
    }

    public function nextSortOrder(): int
    {
        $row = $this->builder()->selectMax('sort_order', 'max')->where('deleted_at', null)->get()->getRowArray();

        return (int) ($row['max'] ?? 0) + 10;
    }

    /**
     * Renumbers the list in steps of 10. Only rows whose position changes are written (and audited).
     *
     * @param list<array<string, mixed>>|null $list Desired order; defaults to the current one
     */
    public function normalise(?array $list = null): void
    {
        $list ??= $this->ordered();

        foreach (array_values($list) as $position => $row) {
            $order = ($position + 1) * 10;

            if ((int) $row['sort_order'] !== $order) {
                $this->update((int) $row['id'], ['sort_order' => $order]);
            }
        }
    }

    /**
     * Moves an item one place up or down. False at either end or for an unknown id.
     */
    public function move(int $id, bool $up): bool
    {
        $list   = $this->ordered();
        $index  = array_search($id, array_map('intval', array_column($list, 'id')), true);
        $target = $index === false ? null : ($up ? $index - 1 : $index + 1);

        if ($target === null || ! isset($list[$target])) {
            return false;
        }

        [$list[$index], $list[$target]] = [$list[$target], $list[$index]];
        $this->normalise($list);

        return true;
    }
}
