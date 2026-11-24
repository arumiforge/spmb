<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * "Tanya Asisten" defaults (module Asisten, D-110, docs/modules/asisten.md). Real values:
 * setting('Asisten.<property>'). Admin Sekolah (asisten.atur) edits the widget switch, greeting,
 * picture, quick choices and log retention; Super Admin (asisten.ai) edits the AI part.
 */
class Asisten extends BaseConfig
{
    /**
     * The widget on public pages. Off until the school switches it on.
     */
    public bool $enabled = false;

    /**
     * Greeting at the top of the chat ('' = the built-in text). Optional English/Arabic versions.
     */
    public string $greeting = '';

    public string $greeting_en = '';
    public string $greeting_ar = '';

    /**
     * Quick choices as JSON ('' = the built-in list), see Modules\Asisten\Libraries\QuickChoices.
     */
    public string $quickChoices = '';

    /**
     * Stored file name of the assistant picture uploaded by the school ('' = the painted pelita
     * or a Lucide icon). Files live in writable/uploads/asisten/.
     */
    public string $avatar = '';

    /**
     * Days the question log is kept.
     */
    public int $retentionDays = 180;

    /**
     * AI answers (Super Admin). Without AI the widget answers with quick choices and Q&A search only.
     */
    public bool $aiEnabled = false;

    /**
     * AI answers per visitor per day.
     */
    public int $dailyVisitorLimit = 10;

    /**
     * AI answers for the whole website per day.
     */
    public int $dailySiteLimit = 300;

    /**
     * Questions per visitor per minute (every kind, against abuse).
     */
    public int $perMinuteLimit = 6;

    /**
     * Longest question in characters.
     */
    public int $maxLength = 300;

    /**
     * Extra instruction appended to the AI system prompt (Super Admin).
     */
    public string $extraInstructions = '';

    /**
     * Minutes a connection rests after a quota error, server error or timeout.
     */
    public int $restMinutes = 5;

    /**
     * Share of the question's important words (0–1) a Tanya Jawab entry must cover to count as the answer.
     */
    public float $matchThreshold = 0.6;

    /**
     * Messages of the conversation (questions and answers) sent along for follow-up questions.
     */
    public int $historyMessages = 6;

    /**
     * Largest amount of school content (characters) put into the AI instructions.
     */
    public int $knowledgeMaxChars = 24000;
}
