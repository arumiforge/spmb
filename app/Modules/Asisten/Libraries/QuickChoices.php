<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use App\Libraries\Locales;
use Modules\Situs\Libraries\FooterLinks;
use Modules\Situs\Libraries\SiteLink;

/**
 * Quick choices at the start of the chat (D-110, step 1): Cara daftar, Persyaratan, Jadwal, Biaya,
 * Cek status, Hubungi panitia by default. Admin Sekolah edits the list (asisten.atur).
 *
 * Stored as JSON in setting('Asisten.quickChoices'); '' means the built-in list, whose texts come from
 * the language files (so they follow the page language while unchanged). Each choice:
 *   kind   link     opens a page of this website ("/persyaratan") or a full https:// address
 *          answer   shows a saved answer (Markdown, like Tanya Jawab)
 *          whatsapp opens the committee's WhatsApp (School.whatsapp)
 *   label, label_en, label_ar; url (link); answer, answer_en, answer_ar (answer).
 * Choices are addressed by position (1-based).
 */
final class QuickChoices
{
    public const MAX   = 8;
    public const KINDS = ['link', 'answer', 'whatsapp'];

    /**
     * @return list<array<string, string>>
     */
    public static function defaults(): array
    {
        return [
            ['kind' => 'link', 'label' => lang('Asisten.choiceHowTo'), 'url' => '/panduan/calon-murid'],
            ['kind' => 'link', 'label' => lang('Asisten.choiceRequirements'), 'url' => '/persyaratan'],
            ['kind' => 'link', 'label' => lang('Asisten.choiceSchedule'), 'url' => '/jadwal'],
            ['kind' => 'answer', 'label' => lang('Asisten.choiceFees'), 'answer' => lang('Asisten.choiceFeesAnswer')],
            ['kind' => 'link', 'label' => lang('Asisten.choiceStatus'), 'url' => '/cek-status'],
            ['kind' => 'whatsapp', 'label' => lang('Asisten.choiceContact')],
        ];
    }

    public static function isCustom(): bool
    {
        return self::decode() !== null;
    }

    /**
     * The list as edited (or the built-in list), in order.
     *
     * @return list<array<string, string>>
     */
    public static function all(): array
    {
        return self::decode() ?? self::defaults();
    }

    /**
     * One choice by position (1-based), or null.
     *
     * @return array<string, string>|null
     */
    public static function find(int $position): ?array
    {
        return self::all()[$position - 1] ?? null;
    }

    /**
     * Choices to show in the widget right now, with their position, text in the page language and
     * where they lead. A link into a switched-off module and a WhatsApp choice without a number are left out.
     *
     * @return list<array{position: int, kind: string, label: string, href: string|null, external: bool}>
     */
    public static function visible(): array
    {
        $nav     = function_exists('public_nav') ? public_nav() : [];
        $choices = [];

        foreach (self::all() as $i => $choice) {
            $kind = $choice['kind'];
            $href = null;

            if ($kind === 'link') {
                if (! FooterLinks::available((string) ($choice['url'] ?? ''), $nav)) {
                    continue;
                }

                $href = SiteLink::href((string) $choice['url']);
            } elseif ($kind === 'whatsapp') {
                $number = (string) setting('School.whatsapp');

                if ($number === '') {
                    continue;
                }

                $href = whatsapp_url($number);
            }

            $choices[] = [
                'position' => $i + 1,
                'kind'     => $kind,
                'label'    => Locales::field($choice, 'label'),
                'href'     => $href,
                'external' => $kind === 'whatsapp' || ($kind === 'link' && SiteLink::isExternal((string) $choice['url'])),
            ];
        }

        return $choices;
    }

    /**
     * Saved answer of a choice in the page language (Markdown).
     *
     * @param array<string, string> $choice
     */
    public static function answer(array $choice): string
    {
        return Locales::field($choice, 'answer');
    }

    /**
     * Saves the list and logs the change. Returns false when nothing changed.
     *
     * @param list<array<string, mixed>> $choices
     */
    public static function save(array $choices, string $description): bool
    {
        $old = self::all();
        $new = array_values(array_map(self::clean(...), $choices));

        if ($old === $new && self::isCustom()) {
            return false;
        }

        setting('Asisten.quickChoices', json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        service('audit')->log('settings_updated', 'settings', 'AsistenQuickChoices', ['choices' => self::describe($old)], ['choices' => self::describe($new)], $description);

        return true;
    }

    /**
     * Back to the built-in list. Returns false when it already was the built-in list.
     */
    public static function reset(): bool
    {
        if (! self::isCustom()) {
            return false;
        }

        $old = self::all();
        setting('Asisten.quickChoices', '');
        service('audit')->log('settings_updated', 'settings', 'AsistenQuickChoices', ['choices' => self::describe($old)], ['choices' => self::describe(self::defaults())], 'Pilihan cepat Tanya Asisten dikembalikan ke bawaan');

        return true;
    }

    /**
     * One choice with only the fields its kind uses; empty translations left out.
     *
     * @param array<string, mixed> $choice
     *
     * @return array<string, string>
     */
    public static function clean(array $choice): array
    {
        $kind  = in_array($choice['kind'] ?? '', self::KINDS, true) ? (string) $choice['kind'] : 'link';
        $clean = ['kind' => $kind, 'label' => trim((string) ($choice['label'] ?? ''))];

        foreach (['label_en', 'label_ar'] as $key) {
            $value = trim((string) ($choice[$key] ?? ''));

            if ($value !== '') {
                $clean[$key] = $value;
            }
        }

        if ($kind === 'link') {
            $clean['url'] = trim((string) ($choice['url'] ?? ''));
        }

        if ($kind === 'answer') {
            foreach (['answer', 'answer_en', 'answer_ar'] as $key) {
                $value = trim(str_replace("\r\n", "\n", (string) ($choice[$key] ?? '')));

                if ($value !== '' || $key === 'answer') {
                    $clean[$key] = $value;
                }
            }
        }

        return $clean;
    }

    /**
     * Readable one-line summary per choice for the audit log.
     *
     * @param list<array<string, string>> $choices
     */
    private static function describe(array $choices): string
    {
        return implode('; ', array_map(static fn (array $c): string => $c['label'] . ' (' . match ($c['kind']) {
            'link'     => $c['url'] ?? '',
            'answer'   => 'jawaban',
            'whatsapp' => 'WhatsApp',
            default    => $c['kind'],
        } . ')', $choices));
    }

    /**
     * @return list<array<string, string>>|null
     */
    private static function decode(): ?array
    {
        $raw = (string) setting('Asisten.quickChoices');

        if (trim($raw) === '') {
            return null;
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            return null;
        }

        $choices = [];

        foreach ($data as $choice) {
            if (is_array($choice) && trim((string) ($choice['label'] ?? '')) !== '') {
                $choices[] = self::clean($choice);
            }
        }

        return $choices;
    }
}
