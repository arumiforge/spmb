<?php

declare(strict_types=1);

namespace Modules\Situs\Models;

use App\Models\Concerns\Auditable;
use CodeIgniter\Model;
use Throwable;

/**
 * Questions on the public /tanya-jawab page (table site_questions, D-072).
 *
 * One sort_order runs through the whole list. Questions of one topic are kept
 * together (normalise()), so topics appear in the order of their first question.
 */
class SiteQuestionModel extends Model
{
    use Auditable;

    /**
     * Marks text only the school can write. A question whose answer still holds
     * it cannot be published.
     */
    public const PLACEHOLDER = '[ISI SEKOLAH';

    protected $table          = 'site_questions';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['topic', 'question', 'answer', 'topic_en', 'topic_ar', 'question_en', 'question_ar', 'answer_en', 'answer_ar', 'sort_order', 'is_published'];

    protected function initialize(): void
    {
        $this->registerAuditCallbacks();
    }

    /**
     * Every question (published or hidden), topics kept together.
     *
     * @return list<array<string, mixed>>
     */
    public function ordered(): array
    {
        return self::flatten(self::group($this->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll()));
    }

    /**
     * Published questions grouped by topic, for the public page.
     *
     * @return list<array{topic: string, items: list<array<string, mixed>>}>
     */
    public function publishedByTopic(): array
    {
        return self::group($this->where('is_published', 1)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll());
    }

    /**
     * Whether the public page has anything to show (the menu link is hidden otherwise).
     * False when the table does not exist yet (before migrations).
     */
    public function hasPublished(): bool
    {
        try {
            return $this->where('is_published', 1)->countAllResults() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Topic names in display order, for suggestions in the form.
     *
     * @return list<string>
     */
    public function topics(): array
    {
        return array_column(self::group($this->ordered()), 'topic');
    }

    public function nextSortOrder(): int
    {
        $row = $this->builder()->selectMax('sort_order', 'max')->where('deleted_at', null)->get()->getRowArray();

        return (int) ($row['max'] ?? 0) + 10;
    }

    /**
     * Renumbers the list in steps of 10 with each topic's questions together.
     * Only rows whose position changes are written (and audited).
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
     * Moves a question one place up or down inside its topic.
     */
    public function moveQuestion(int $id, bool $up): bool
    {
        $groups = self::group($this->ordered());

        foreach ($groups as &$group) {
            $ids   = array_map('intval', array_column($group['items'], 'id'));
            $index = array_search($id, $ids, true);

            if ($index === false) {
                continue;
            }

            $target = $up ? $index - 1 : $index + 1;

            if (! isset($group['items'][$target])) {
                return false;
            }

            [$group['items'][$index], $group['items'][$target]] = [$group['items'][$target], $group['items'][$index]];
            unset($group);
            $this->normalise(self::flatten($groups));

            return true;
        }

        return false;
    }

    /**
     * Moves a whole topic one place up or down.
     */
    public function moveTopic(string $topic, bool $up): bool
    {
        $groups = self::group($this->ordered());
        $keys   = array_map(self::topicKey(...), array_column($groups, 'topic'));
        $index  = array_search(self::topicKey($topic), $keys, true);

        if ($index === false) {
            return false;
        }

        $target = $up ? $index - 1 : $index + 1;

        if (! isset($groups[$target])) {
            return false;
        }

        [$groups[$index], $groups[$target]] = [$groups[$target], $groups[$index]];
        $this->normalise(self::flatten($groups));

        return true;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function needsSchoolInput(array $row): bool
    {
        return str_contains((string) ($row['answer'] ?? ''), self::PLACEHOLDER)
            || str_contains((string) ($row['question'] ?? ''), self::PLACEHOLDER);
    }

    /**
     * Groups rows by topic (case and spacing ignored) in order of first appearance.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array{topic: string, items: list<array<string, mixed>>}>
     */
    public static function group(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = self::topicKey((string) $row['topic']);
            $groups[$key] ??= ['topic' => trim((string) $row['topic']), 'items' => []];
            $groups[$key]['items'][] = $row;
        }

        return array_values($groups);
    }

    /**
     * @param list<array{topic: string, items: list<array<string, mixed>>}> $groups
     *
     * @return list<array<string, mixed>>
     */
    private static function flatten(array $groups): array
    {
        return array_merge([], ...array_column($groups, 'items'));
    }

    private static function topicKey(string $topic): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($topic)));
    }
}
