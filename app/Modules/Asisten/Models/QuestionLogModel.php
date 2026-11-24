<?php

declare(strict_types=1);

namespace Modules\Asisten\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use Modules\Asisten\Libraries\TextNormaliser;
use Throwable;

/**
 * Questions visitors asked the assistant (table asisten_questions, D-110), so the school sees what
 * people want to know and can turn it into Tanya Jawab. Nothing identifies a visitor: no IP, no
 * visitor key; long numbers and e-mail addresses are masked before the question is stored.
 *
 * answered_by: chip (quick choice), qa (Tanya Jawab match), ai (AI answer), ai_unknown (AI said the
 * school's content does not cover it), none (no match and AI off), limit (a daily limit was reached),
 * error (every AI connection failed).
 */
class QuestionLogModel extends Model
{
    public const KINDS = ['chip', 'qa', 'ai', 'ai_unknown', 'none', 'limit', 'error'];

    /**
     * Answers the school may want to add to Tanya Jawab.
     */
    public const UNANSWERED = ['ai', 'ai_unknown', 'none', 'limit', 'error'];

    protected $table         = 'asisten_questions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'question', 'question_key', 'locale', 'answered_by', 'site_question_id', 'choice_label',
        'provider_id', 'provider_name', 'tokens_in', 'tokens_out', 'created_at',
    ];

    /**
     * Stores one entry. Never throws: the visitor's answer matters more than the log.
     *
     * @param array<string, mixed> $extra site_question_id, choice_label, provider_id, provider_name, tokens_in, tokens_out
     */
    public function record(string $question, string $locale, string $answeredBy, array $extra = []): void
    {
        try {
            [$masked] = TextNormaliser::maskPersonalData($question);
            $this->insert([
                'question'     => mb_substr(trim($masked), 0, 500),
                'question_key' => TextNormaliser::key($masked),
                'locale'       => mb_substr($locale, 0, 5),
                'answered_by'  => $answeredBy,
                'created_at'   => Time::now()->toDateTimeString(),
            ] + array_intersect_key($extra, array_flip(['site_question_id', 'choice_label', 'provider_id', 'provider_name', 'tokens_in', 'tokens_out'])));
        } catch (Throwable $e) {
            log_message('error', 'Asisten: question not logged: {message}', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Counts per answer kind since $since (Y-m-d H:i:s).
     *
     * @return array<string, int>
     */
    public function countsSince(string $since): array
    {
        $counts = array_fill_keys(self::KINDS, 0);
        $rows   = $this->builder()->select('answered_by, COUNT(*) AS n')->where('created_at >=', $since)->groupBy('answered_by')->get()->getResultArray();

        foreach ($rows as $row) {
            $counts[$row['answered_by']] = (int) $row['n'];
        }

        return $counts;
    }

    /**
     * Questions asked most often that Tanya Jawab did not answer, grouped by their words.
     *
     * @return list<array{question: string, count: int, last: string, kinds: list<string>}>
     */
    public function topUnanswered(string $since, int $limit = 15): array
    {
        $groups = $this->builder()
            ->select('question_key, COUNT(*) AS n, MAX(id) AS last_id, MAX(created_at) AS last_at, GROUP_CONCAT(DISTINCT answered_by) AS kinds')
            ->whereIn('answered_by', self::UNANSWERED)
            ->where('created_at >=', $since)
            ->where('question_key !=', '')
            ->groupBy('question_key')
            ->orderBy('n', 'DESC')
            ->orderBy('last_at', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();

        if ($groups === []) {
            return [];
        }

        $examples = array_column($this->builder()->select('id, question')->whereIn('id', array_column($groups, 'last_id'))->get()->getResultArray(), 'question', 'id');
        $list     = [];

        foreach ($groups as $group) {
            $list[] = [
                'question' => (string) ($examples[$group['last_id']] ?? $group['question_key']),
                'count'    => (int) $group['n'],
                'last'     => (string) $group['last_at'],
                'kinds'    => array_values(array_filter(explode(',', (string) $group['kinds']))),
            ];
        }

        return $list;
    }

    /**
     * Deletes entries older than $days (minimum 1). Returns the number deleted.
     */
    public function prune(int $days, ?string $now = null): int
    {
        $cutoff = Time::parse($now ?? Time::now()->toDateTimeString())->subDays(max(1, $days))->toDateTimeString();
        $this->builder()->where('created_at <', $cutoff)->delete();

        return $this->db->affectedRows();
    }
}
