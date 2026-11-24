<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use App\Libraries\Locales;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\I18n\Time;
use Modules\Asisten\Models\QuestionLogModel;
use Modules\Situs\Libraries\AnswerRenderer;
use Throwable;

/**
 * Answers one message of the "Tanya Asisten" widget in the cheapest way first (D-110):
 *
 *   1. quick choice  → its saved answer (links open directly in the browser, see Widget::open)
 *   2. typed question → the best published Tanya Jawab entry (QaMatcher), with a link to it,
 *                       "Bukan ini? Tanya asisten" (only while AI is available) and the WhatsApp handover
 *   3. AI             → only when nothing matched well or the visitor pressed "Tanya asisten", within the
 *                       daily limits; every AI answer carries the WhatsApp handover, prominently when the
 *                       model says the school's content does not cover the question
 *
 * Questions are masked (long numbers, e-mail addresses) before they are matched, sent or logged.
 * The last few messages stay in the session for follow-up questions to the AI.
 * The result is the JSON payload for resources/js/asisten.js.
 */
final class Assistant
{
    public const HISTORY_KEY = 'asisten_history';

    private AiGateway $gateway;
    private QuestionLogModel $log;
    private UsageLimiter $limiter;
    private ?AnswerRenderer $renderer = null;

    public function __construct(
        private readonly IncomingRequest $request,
        ?AiGateway $gateway = null,
        private readonly ?QaMatcher $matcher = null,
        ?UsageLimiter $limiter = null,
    ) {
        $this->gateway = $gateway ?? new AiGateway();
        $this->log     = model(QuestionLogModel::class);
        $this->limiter = $limiter ?? new UsageLimiter($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function ask(string $question, bool $forceAi = false): array
    {
        $question = trim((string) preg_replace('/\s+/u', ' ', $question));
        $max      = max(20, (int) setting('Asisten.maxLength'));

        if ($question === '') {
            return ['type' => 'invalid', 'message' => lang('Asisten.errEmptyQuestion')];
        }

        if (mb_strlen($question) > $max) {
            return ['type' => 'invalid', 'message' => lang('Asisten.errTooLong', [$max])];
        }

        if (! $this->limiter->allowMinute()) {
            return ['type' => 'slow', 'message' => lang('Asisten.errTooFast')];
        }

        $this->pruneOncePerDay();

        [$masked, $hidden] = TextNormaliser::maskPersonalData($question);
        // The widget already masks in the browser; its placeholders mean something was hidden too.
        $hidden = $hidden || str_contains($question, '[####]') || str_contains($question, '[email]');
        $locale = Locales::current();
        $aiOn   = AiGateway::available();
        $base   = ['masked' => $hidden];

        if (! $forceAi) {
            $match = ($this->matcher ?? new QaMatcher())->match($masked);

            if ($match !== null) {
                $row = $match['row'];
                $this->log->record($masked, $locale, 'qa', ['site_question_id' => (int) $row['id']]);
                $this->remember($masked, Locales::field($row, 'answer'));

                return $base + [
                    'type'     => 'qa',
                    'title'    => Locales::field($row, 'question'),
                    'html'     => $this->render(Locales::field($row, 'answer')),
                    'source'   => ['label' => lang('Asisten.openInQa'), 'url' => self::qaUrl((int) $row['id'])],
                    'related'  => array_map(static fn (array $r): array => ['label' => Locales::field($r, 'question'), 'url' => self::qaUrl((int) $r['id'])], $match['related']),
                    'askAi'    => $aiOn,
                    'handover' => $this->handover($masked, false),
                ];
            }
        }

        if (! $aiOn) {
            $this->log->record($masked, $locale, 'none');

            return $base + [
                'type'     => 'none',
                'message'  => lang('Asisten.noMatch'),
                'source'   => ['label' => lang('Asisten.seeAllQa'), 'url' => site_url('tanya-jawab')],
                'handover' => $this->handover($masked, true),
            ];
        }

        $reached = $this->limiter->reached();

        if ($reached !== null) {
            $this->log->record($masked, $locale, 'limit');

            return $base + [
                'type'     => 'limit',
                'title'    => lang('Asisten.limitTitle'),
                'message'  => $reached === 'visitor' ? lang('Asisten.limitVisitor', [(int) setting('Asisten.dailyVisitorLimit')]) : lang('Asisten.limitSite'),
                'image'    => self::picture('state/chat-batas', 'state/kosong-data', lang('Asisten.limitTitle')),
                'handover' => $this->handover($masked, true, lang('Asisten.continueWhatsapp')),
            ];
        }

        $messages = [['role' => 'system', 'content' => (new KnowledgeBuilder())->systemPrompt($locale)]];

        foreach ($this->history() as $turn) {
            $messages[] = $turn;
        }

        $messages[] = ['role' => 'user', 'content' => $masked];
        $reply      = $this->gateway->chat($messages);

        if (! $reply['ok']) {
            $this->log->record($masked, $locale, 'error');

            return $base + [
                'type'     => 'error',
                'message'  => lang('Asisten.errBusy'),
                'retry'    => true,
                'handover' => $this->handover($masked, true),
            ];
        }

        $this->limiter->record();

        $content = $reply['content'];
        $unknown = str_contains($content, KnowledgeBuilder::UNKNOWN);
        $content = trim(str_replace(KnowledgeBuilder::UNKNOWN, '', $content));

        if ($content === '') {
            $content = lang('Asisten.unknown');
        }

        $this->remember($masked, $content);
        $this->log->record($masked, $locale, $unknown ? 'ai_unknown' : 'ai', [
            'provider_id'   => (int) $reply['provider']['id'],
            'provider_name' => (string) $reply['provider']['name'],
            'tokens_in'     => $reply['tokensIn'],
            'tokens_out'    => $reply['tokensOut'],
        ]);

        return $base + [
            'type'     => $unknown ? 'unknown' : 'ai',
            'html'     => $this->render($content),
            'auto'     => true,
            'handover' => $this->handover($masked, $unknown),
        ];
    }

    /**
     * Saved answer of a quick choice (kind "answer"); other kinds give their link.
     *
     * @return array<string, mixed>
     */
    public function choice(int $position): array
    {
        $choice = QuickChoices::find($position);

        if ($choice === null) {
            return ['type' => 'invalid', 'message' => lang('Asisten.errChoiceGone')];
        }

        if (! $this->limiter->allowMinute()) {
            return ['type' => 'slow', 'message' => lang('Asisten.errTooFast')];
        }

        $label = Locales::field($choice, 'label');
        $this->log->record($label, Locales::current(), 'chip', ['choice_label' => mb_substr((string) $choice['label'], 0, 80)]);

        if ($choice['kind'] !== 'answer') {
            return ['type' => 'link', 'title' => $label, 'url' => site_url('asisten/buka/' . $position)];
        }

        return [
            'type'     => 'choice',
            'title'    => $label,
            'html'     => $this->render(QuickChoices::answer($choice)),
            'handover' => $this->handover('', false),
        ];
    }

    /**
     * Forgets the conversation (button "Mulai dari awal").
     */
    public function reset(): void
    {
        session()->remove(self::HISTORY_KEY);
    }

    /**
     * Link to one entry on the Tanya Jawab page.
     */
    public static function qaUrl(int $id): string
    {
        return site_url('tanya-jawab') . '#pertanyaan-' . $id;
    }

    /**
     * WhatsApp button, or the contact block of Tanya Jawab when the school has no WhatsApp number.
     *
     * @return array{url: string, label: string, prominent: bool, external: bool}
     */
    public function handover(string $question, bool $prominent, ?string $label = null): array
    {
        $number = (string) setting('School.whatsapp');

        if ($number === '') {
            return ['url' => site_url('tanya-jawab') . '#kontak', 'label' => lang('Asisten.contactCommittee'), 'prominent' => $prominent, 'external' => false];
        }

        $text = $question === '' ? lang('Asisten.whatsappHello') : lang('Asisten.whatsappText', [$question]);

        return ['url' => whatsapp_url($number, $text), 'label' => $label ?? lang('Asisten.askOnWhatsapp'), 'prominent' => $prominent, 'external' => true];
    }

    /**
     * A painted picture, its fallback, or ''.
     */
    public static function picture(string $path, string $fallback, string $alt): string
    {
        $html = illustration($path, $alt, 'mx-auto h-28 w-auto', false);

        return $html !== '' ? $html : illustration($fallback, $alt, 'mx-auto h-28 w-auto', false);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function history(): array
    {
        $history = session(self::HISTORY_KEY);

        return is_array($history) ? array_values($history) : [];
    }

    private function remember(string $question, string $answer): void
    {
        $keep      = max(0, (int) setting('Asisten.historyMessages'));
        $history   = $this->history();
        $history[] = ['role' => 'user', 'content' => mb_substr($question, 0, 500)];
        $history[] = ['role' => 'assistant', 'content' => mb_substr($answer, 0, 1200)];
        session()->set(self::HISTORY_KEY, $keep === 0 ? [] : array_slice($history, -$keep));
    }

    private function render(string $markdown): string
    {
        return ($this->renderer ??= new AnswerRenderer())->render($markdown);
    }

    /**
     * Deletes old log entries and usage rows on the first question of a day.
     */
    private function pruneOncePerDay(): void
    {
        $today = Time::today()->toDateString();

        try {
            if (cache('asisten_prune_date') === $today) {
                return;
            }

            cache()->save('asisten_prune_date', $today, DAY);
            $this->log->prune((int) setting('Asisten.retentionDays'));
            UsageLimiter::prune($today);
        } catch (Throwable $e) {
            log_message('error', 'Asisten: pruning failed: {message}', ['message' => $e->getMessage()]);
        }
    }
}
