<?php

declare(strict_types=1);

namespace Modules\Situs\Services;

use CodeIgniter\I18n\Time;
use Modules\Pendaftaran\Models\WaveModel;

/**
 * Waves of the configured academic year as the public sees them: open,
 * upcoming or closed today (dates inclusive), and whether the online form is open.
 */
class WaveSchedule
{
    public const OPEN     = 'open';
    public const UPCOMING = 'upcoming';
    public const CLOSED   = 'closed';

    /**
     * Active waves of the year in date order, each with a 'state'.
     *
     * @return list<array<string, mixed>>
     */
    public static function forYear(?string $academicYear = null, ?string $today = null): array
    {
        $academicYear ??= (string) setting('Pendaftaran.academicYear');
        $today ??= Time::today()->toDateString();

        $waves = model(WaveModel::class)
            ->where('academic_year', $academicYear)
            ->where('is_active', 1)
            ->orderBy('starts_on', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($waves as &$wave) {
            $wave['state'] = self::state($wave, $today);
        }

        return $waves;
    }

    /**
     * @param array<string, mixed> $wave
     */
    public static function state(array $wave, string $today): string
    {
        if ($today < (string) $wave['starts_on']) {
            return self::UPCOMING;
        }

        return $today > (string) $wave['ends_on'] ? self::CLOSED : self::OPEN;
    }

    /**
     * The wave open today, or null.
     *
     * @return array<string, mixed>|null
     */
    public static function current(?string $today = null): ?array
    {
        foreach (self::forYear(null, $today) as $wave) {
            if ($wave['state'] === self::OPEN) {
                return $wave;
            }
        }

        return null;
    }

    /**
     * The next wave that has not started yet, or null.
     *
     * @return array<string, mixed>|null
     */
    public static function next(?string $today = null): ?array
    {
        foreach (self::forYear(null, $today) as $wave) {
            if ($wave['state'] === self::UPCOMING) {
                return $wave;
            }
        }

        return null;
    }

    /**
     * Online registration is open: the master switch is on and a wave covers today.
     */
    public static function onlineOpen(?string $today = null): bool
    {
        return (bool) setting('Pendaftaran.onlineOpen') && self::current($today) !== null;
    }

    /**
     * Registration state for banners on the home and schedule pages.
     *
     * @return array{state: string, text: string, wave: array<string, mixed>|null}
     *                                                                             state: open (online form open) | offline (wave running, online form off) | upcoming | closed
     */
    public static function banner(?string $today = null): array
    {
        $current = self::current($today);

        if ($current !== null) {
            $online = (bool) setting('Pendaftaran.onlineOpen');

            return [
                'state' => $online ? 'open' : 'offline',
                'text'  => $online
                    ? lang('Situs.openBanner', [localized($current, 'name'), tanggal_indo($current['ends_on'])])
                    : lang('Situs.offlineOnly', [localized($current, 'name')]),
                'wave' => $current,
            ];
        }

        $next = self::next($today);

        if ($next !== null) {
            return ['state' => 'upcoming', 'text' => lang('Situs.nextBanner', [localized($next, 'name'), tanggal_indo($next['starts_on'])]), 'wave' => $next];
        }

        return ['state' => 'closed', 'text' => lang('Situs.closedBanner'), 'wave' => null];
    }
}
