<?php

declare(strict_types=1);

namespace Modules\Situs\Services;

use CodeIgniter\I18n\Time;
use Modules\Situs\Libraries\AnswerRenderer;
use Modules\Situs\Libraries\PopupFiles;
use Modules\Situs\Libraries\SiteLink;

/**
 * The popup on the home page (settings Situs.popup*). Shown only on "/", while
 * it is switched on and today is inside its dates. Which visit shows it is
 * decided in the browser (resources/js/popup.js) from the frequency and the
 * version, which every save raises.
 */
final class HomePopup
{
    /**
     * Frequency => label key (Situs language file).
     */
    public const FREQUENCIES = ['once' => 'popupFreqOnce', 'daily' => 'popupFreqDaily', 'always' => 'popupFreqAlways'];

    /**
     * Raw settings of the popup.
     *
     * @return array{enabled: bool, title: string, text: string, image: string, buttonLabel: string, buttonUrl: string, attachment: string, attachmentName: string, startsOn: string, endsOn: string, frequency: string, version: int}
     */
    public static function settings(): array
    {
        $frequency = (string) setting('Situs.popupFrequency');

        return [
            'enabled'        => (bool) setting('Situs.popupEnabled'),
            'title'          => (string) setting('Situs.popupTitle'),
            'title_en'       => (string) setting('Situs.popupTitle_en'),
            'title_ar'       => (string) setting('Situs.popupTitle_ar'),
            'text'           => (string) setting('Situs.popupText'),
            'text_en'        => (string) setting('Situs.popupText_en'),
            'text_ar'        => (string) setting('Situs.popupText_ar'),
            'image'          => (string) setting('Situs.popupImage'),
            'buttonLabel'    => (string) setting('Situs.popupButtonLabel'),
            'buttonLabel_en' => (string) setting('Situs.popupButtonLabel_en'),
            'buttonLabel_ar' => (string) setting('Situs.popupButtonLabel_ar'),
            'buttonUrl'      => (string) setting('Situs.popupButtonUrl'),
            'attachment'     => (string) setting('Situs.popupAttachment'),
            'attachmentName' => (string) setting('Situs.popupAttachmentName'),
            'startsOn'       => (string) setting('Situs.popupStartsOn'),
            'endsOn'         => (string) setting('Situs.popupEndsOn'),
            'frequency'      => isset(self::FREQUENCIES[$frequency]) ? $frequency : 'once',
            'version'        => (int) setting('Situs.popupVersion'),
        ];
    }

    /**
     * Whether visitors can see the popup today: on, inside its dates, with something to show.
     */
    public static function isLive(?string $today = null): bool
    {
        $s = self::settings();
        $today ??= Time::today()->toDateString();

        return $s['enabled']
            && ($s['startsOn'] === '' || $s['startsOn'] <= $today)
            && ($s['endsOn'] === '' || $s['endsOn'] >= $today)
            && self::hasContent($s);
    }

    /**
     * @param array<string, mixed> $s settings()
     */
    public static function hasContent(array $s): bool
    {
        return trim((string) $s['title']) !== '' || trim((string) $s['text']) !== '' || PopupFiles::path((string) $s['image']) !== null;
    }

    /**
     * Whether the signed-in user may preview the popup (and fetch its files while it is off).
     */
    public static function canManage(): bool
    {
        return auth()->loggedIn() && auth()->user()->can('situs.kelola');
    }

    /**
     * Data for the popup view, or null when nothing should be rendered.
     * $preview (admins only, "?popup=pratinjau") ignores the switch and the dates.
     *
     * @return array<string, mixed>|null
     */
    public static function forPage(bool $preview = false): ?array
    {
        $s = self::settings();

        if ($preview ? ! self::hasContent($s) : ! self::isLive()) {
            return null;
        }

        $image      = PopupFiles::path($s['image']) !== null;
        $attachment = PopupFiles::path($s['attachment']) !== null;
        $buttonUrl  = SiteLink::normalise($s['buttonUrl']) ?? '';
        // Texts in the page's language when the school filled in a translation (D-109).
        $text            = localized($s, 'text');
        $s['buttonLabel'] = localized($s, 'buttonLabel');

        return [
            'title'      => localized($s, 'title'),
            'html'       => trim($text) === '' ? '' : (new AnswerRenderer(true))->render($text),
            'image'      => $image ? self::fileUrl('gambar', $s) : null,
            'attachment' => $attachment ? ['url' => self::fileUrl('lampiran', $s), 'name' => PopupFiles::downloadName($s['attachmentName'], $s['attachment'])] : null,
            'button'     => $s['buttonLabel'] !== '' && $buttonUrl !== '' ? ['label' => $s['buttonLabel'], 'url' => SiteLink::href($buttonUrl), 'external' => SiteLink::isExternal($buttonUrl)] : null,
            'frequency'  => $s['frequency'],
            'version'    => $s['version'],
            'preview'    => $preview,
        ];
    }

    /**
     * @param array<string, mixed> $s settings()
     */
    private static function fileUrl(string $kind, array $s): string
    {
        $name = (string) ($kind === 'gambar' ? $s['image'] : $s['attachment']);

        return site_url('popup/' . $kind) . '?v=' . substr(md5($name), 0, 8);
    }
}
