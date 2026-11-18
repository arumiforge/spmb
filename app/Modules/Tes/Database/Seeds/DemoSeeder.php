<?php

declare(strict_types=1);

namespace Modules\Tes\Database\Seeds;

use CodeIgniter\Database\Seeder;
use DateTimeImmutable;
use Modules\Tes\Libraries\SessionGenerator;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Models\TestRoomModel;
use Modules\Tes\Models\TestSessionModel;

/**
 * Development data (php spark spmb:install --demo): four rooms, two holidays
 * and sessions on the six weekdays (Monday–Saturday) starting 30 days from
 * today, two slots a day in every room, made with SessionGenerator.
 * Idempotent: rooms, holidays and sessions are only added when missing.
 * Ends with package B's DemoAllocationSeeder when it exists.
 */
class DemoSeeder extends Seeder
{
    /**
     * @var list<array{name: string, capacity: int, location: string, sort_order: int}>
     */
    private const ROOMS = [
        ['name' => 'Ruang 1', 'capacity' => 20, 'location' => 'Gedung A lantai 1', 'sort_order' => 1],
        ['name' => 'Ruang 2', 'capacity' => 20, 'location' => 'Gedung A lantai 1', 'sort_order' => 2],
        ['name' => 'Lab Komputer', 'capacity' => 24, 'location' => 'Gedung B lantai 2', 'sort_order' => 3],
        ['name' => 'Aula', 'capacity' => 40, 'location' => 'Gedung C, dekat masjid', 'sort_order' => 4],
    ];

    public function run(): void
    {
        $rooms = model(TestRoomModel::class);

        foreach (self::ROOMS as $room) {
            if ($rooms->findByName($room['name']) === null) {
                $rooms->insert($room + ['is_active' => 1]);
            }
        }

        $days = self::testDays();
        $last = new DateTimeImmutable(end($days));

        // Holidays near the test days: the next weekday after them and one two weeks later.
        $holidays = model(TestHolidayModel::class);
        $first    = $last->modify('+1 day')->format('N') === '7' ? $last->modify('+2 days') : $last->modify('+1 day');

        foreach ([[$first, 'Rapat guru (contoh)'], [$first->modify('+14 days'), 'Libur hari besar (contoh)']] as [$date, $note]) {
            if (! $holidays->isHoliday($date->format('Y-m-d')) && model(TestSessionModel::class)->countOnDate($date->format('Y-m-d')) === 0) {
                $holidays->insert(['date' => $date->format('Y-m-d'), 'note' => $note]);
            }
        }

        $year = (string) setting('Pendaftaran.academicYear');

        if (model(TestSessionModel::class)->where('academic_year', $year)->countAllResults() === 0) {
            $staff = $this->db->table('users')->select('id')->orderBy('id', 'ASC')->get(1)->getRowArray();
            (new SessionGenerator())->create([
                'academic_year' => $year,
                'date_from'     => $days[0],
                'date_to'       => end($days),
                'weekdays'      => SessionGenerator::DEFAULT_WEEKDAYS,
                'slots'         => [['start' => '07:30', 'end' => '09:30'], ['start' => '10:00', 'end' => '12:00']],
                'rooms'         => array_map('intval', array_column($rooms->active(), 'id')),
                'wave_id'       => null,
                'label'         => SessionGenerator::DEFAULT_LABEL,
            ], (int) ($staff['id'] ?? 0));
        }

        if (class_exists(DemoAllocationSeeder::class)) {
            $this->call(DemoAllocationSeeder::class);
        }
    }

    /**
     * The six Monday–Saturday dates starting 30 days from today.
     *
     * @return list<string>
     */
    public static function testDays(?string $today = null): array
    {
        $day  = (new DateTimeImmutable($today ?? date('Y-m-d')))->modify('+30 days');
        $days = [];

        while (count($days) < 6) {
            if ($day->format('N') !== '7') {
                $days[] = $day->format('Y-m-d');
            }

            $day = $day->modify('+1 day');
        }

        return $days;
    }
}
