<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use App\Libraries\Locales;
use Modules\Situs\Models\SiteQuestionModel;

/**
 * Finds the published Tanya Jawab entry that answers a typed question (D-110, step 2: no AI cost).
 *
 * Each entry is a "document" of its question and topic (strong) and its answer (weak), in the page's
 * language and, on English and Arabic pages, also in Indonesian. Words come from TextNormaliser.
 * Every question word is weighted by how rare it is among the entries (BM25 idf), so "biaya" counts
 * more than a word every answer contains. Coverage = weight of the question words found / weight of
 * all question words, a word in the answer only counting half. The best entry wins when its coverage
 * reaches the threshold (setting Asisten.matchThreshold, default 0.6) and at least one word is in its
 * question or topic. Ties go to the entry whose own question has the fewest other words ("Berapa
 * biaya pendaftaran?" before "Apakah ada keringanan biaya atau beasiswa?" for "berapa biayanya"),
 * then to the higher BM25 score.
 */
final class QaMatcher
{
    private const K1 = 1.2;
    private const B  = 0.75;

    /**
     * @var list<array{row: array<string, mixed>, strong: array<string, int>, weak: array<string, int>, own: array<string, true>, length: int}>|null
     */
    private ?array $documents = null;

    /**
     * @param list<array<string, mixed>>|null $rows Published questions (default: from the database)
     */
    public function __construct(private readonly ?array $rows = null, private ?float $threshold = null)
    {
    }

    /**
     * Best entry and up to $relatedCount other close entries, or null when nothing is close enough.
     *
     * @return array{row: array<string, mixed>, coverage: float, related: list<array<string, mixed>>}|null
     */
    public function match(string $question, int $relatedCount = 2): ?array
    {
        $ranked = $this->rank($question);

        if ($ranked === [] || $ranked[0]['coverage'] < $this->threshold() || ! $ranked[0]['strongHit']) {
            return null;
        }

        $related = [];

        foreach (array_slice($ranked, 1) as $candidate) {
            if (count($related) >= $relatedCount) {
                break;
            }

            if ($candidate['strongHit'] && $candidate['coverage'] >= $this->threshold() * 0.6) {
                $related[] = $candidate['row'];
            }
        }

        return ['row' => $ranked[0]['row'], 'coverage' => $ranked[0]['coverage'], 'related' => $related];
    }

    /**
     * Every entry that shares a word with the question, best first.
     *
     * @return list<array{row: array<string, mixed>, coverage: float, precision: float, score: float, strongHit: bool}>
     */
    public function rank(string $question): array
    {
        $terms = array_values(array_unique(TextNormaliser::words($question)));
        $docs  = $this->documents();

        if ($terms === [] || $docs === []) {
            return [];
        }

        $count   = count($docs);
        $average = max(1, array_sum(array_column($docs, 'length')) / $count);
        $idf     = [];

        foreach ($terms as $term) {
            $df = 0;

            foreach ($docs as $doc) {
                if (isset($doc['strong'][$term]) || isset($doc['weak'][$term])) {
                    $df++;
                }
            }

            $idf[$term] = log(1 + ($count - $df + 0.5) / ($df + 0.5));
        }

        $total  = array_sum($idf);
        $ranked = [];

        foreach ($docs as $doc) {
            $matched   = 0.0;
            $score     = 0.0;
            $strongHit = false;

            foreach ($terms as $term) {
                $strong = $doc['strong'][$term] ?? 0;
                $weak   = $doc['weak'][$term] ?? 0;

                if ($strong > 0) {
                    $matched += $idf[$term];
                    $strongHit = true;
                } elseif ($weak > 0) {
                    $matched += $idf[$term] * 0.5;
                }

                $tf = $strong * 2 + $weak;

                if ($tf > 0) {
                    $score += $idf[$term] * ($tf * (self::K1 + 1)) / ($tf + self::K1 * (1 - self::B + self::B * $doc['length'] / $average));
                }
            }

            if ($matched > 0) {
                $own       = count($doc['own']);
                $precision = $own > 0 ? round(count(array_intersect_key($doc['own'], array_flip($terms))) / $own, 4) : 0.0;
                $ranked[]  = ['row' => $doc['row'], 'coverage' => $total > 0 ? round($matched / $total, 4) : 0.0, 'precision' => $precision, 'score' => $score, 'strongHit' => $strongHit];
            }
        }

        usort($ranked, static fn (array $a, array $b): int => [$b['coverage'], $b['precision'], $b['score']] <=> [$a['coverage'], $a['precision'], $a['score']]);

        return $ranked;
    }

    public function threshold(): float
    {
        return $this->threshold ??= max(0.1, min(1.0, (float) setting('Asisten.matchThreshold')));
    }

    /**
     * @return list<array{row: array<string, mixed>, strong: array<string, int>, weak: array<string, int>, own: array<string, true>, length: int}>
     */
    private function documents(): array
    {
        if ($this->documents !== null) {
            return $this->documents;
        }

        $rows   = $this->rows ?? model(SiteQuestionModel::class)->where('is_published', 1)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
        $locale = Locales::current();
        $docs   = [];

        foreach ($rows as $row) {
            $questionText = Locales::field($row, 'question');
            $topicText    = Locales::field($row, 'topic');
            $weakText     = Locales::field($row, 'answer');

            if ($locale !== Locales::DEFAULT) {
                $questionText .= ' ' . $row['question'];
                $topicText    .= ' ' . $row['topic'];
                $weakText     .= ' ' . $row['answer'];
            }

            $own    = TextNormaliser::words($questionText);
            $strong = array_count_values([...$own, ...TextNormaliser::words($topicText)]);
            $weak   = array_count_values(TextNormaliser::words($weakText));
            $docs[] = ['row' => $row, 'strong' => $strong, 'weak' => $weak, 'own' => array_fill_keys($own, true), 'length' => array_sum($strong) * 2 + array_sum($weak)];
        }

        return $this->documents = $docs;
    }
}
