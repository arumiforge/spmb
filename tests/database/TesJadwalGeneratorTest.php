<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Tes\Libraries\SessionGenerator;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Models\TestSessionModel;
use Tests\Support\Tes\TesFixtures;
use Tests\Support\Tes\TesJadwalHelpers;

/**
 * Package A — SessionGenerator: weekdays × slots × rooms, holidays and
 * overlaps skipped, labels, input checks, one audit event per run.
 *
 * @internal
 */
final class TesJadwalGeneratorTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use TesFixtures;
    use TesJadwalHelpers;

    protected $namespace;
    protected $refresh = true;

    /**
     * Mon 2027-06-07 … Sun 2027-06-13, two slots, two rooms.
     *
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function input(array $rooms, array $override = []): array
    {
        return $override + [
            'date_from' => '2027-06-07',
            'date_to'   => '2027-06-13',
            'weekdays'  => [1, 2, 3, 4, 5, 6],
            'slots'     => [['start' => '10:00', 'end' => '12:00'], ['start' => '07:30', 'end' => '']],
            'rooms'     => $rooms,
            'wave_id'   => null,
            'label'     => 'Sesi {n}',
        ];
    }

    public function testPreviewSkipsHolidaysSundaysAndOverlaps(): void
    {
        $a = $this->makeRoom(['name' => 'Ruang A', 'capacity' => 20, 'sort_order' => 1]);
        $b = $this->makeRoom(['name' => 'Ruang B', 'capacity' => 30, 'sort_order' => 2]);
        model(TestHolidayModel::class)->insert(['date' => '2027-06-09', 'note' => 'Rapat']);
        $this->makeSession(['room_id' => $b, 'test_date' => '2027-06-10', 'starts_at' => '11:00:00', 'ends_at' => '13:00:00', 'label' => 'Lama']);

        $preview = (new SessionGenerator())->preview($this->input([$a, $b]));

        $this->assertSame([], $preview['errors']);
        // 5 test days (Mon–Sat minus Wednesday) × 2 slots × 2 rooms − 1 overlap.
        $this->assertSame(19, $preview['count']);
        $this->assertSame(19 * 25 - 5, $preview['seats']);
        $this->assertCount(5, $preview['days']);
        $this->assertSame([['date' => '2027-06-09', 'day' => 'Rabu, 9 Juni 2027', 'note' => 'Rapat']], $preview['skippedHolidays']);
        $this->assertSame('Ruang B', $preview['skippedOverlaps'][0]['room']);
        $this->assertSame('2027-06-10', $preview['skippedOverlaps'][0]['date']);

        $first = $preview['sessions'][0];
        $this->assertSame(['2027-06-07', '07:30:00', '09:30:00', 'Sesi 1', 20], [$first['test_date'], $first['starts_at'], $first['ends_at'], $first['label'], $first['quota']]);
        $this->assertSame('Sesi 2', $preview['sessions'][2]['label'], 'numbered by start time per day');
        $this->assertSame(1, model(TestSessionModel::class)->countAllResults(), 'preview writes nothing');
    }

    public function testCreateInsertsInOneGoAndLogsOneEvent(): void
    {
        $a     = $this->makeRoom();
        $wave  = $this->makeWave();
        $staff = $this->makeStaff('admin');

        $count = (new SessionGenerator())->create($this->input([$a], ['wave_id' => $wave, 'weekdays' => [6]]), (int) $staff->id);

        $this->assertSame(2, $count);
        $rows = model(TestSessionModel::class)->orderBy('starts_at')->findAll();
        $this->assertSame(['2027-06-12', '2027-06-12'], array_column($rows, 'test_date'));
        $this->assertSame([(string) $wave, (string) $wave], array_map('strval', array_column($rows, 'wave_id')));
        $this->assertSame('2027/2028', $rows[0]['academic_year']);
        $this->seeInDatabase('audit_logs', ['event' => 'sessions_generated', 'subject_type' => 'test_sessions', 'user_id' => $staff->id]);
        $this->dontSeeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'test_sessions']);

        // Running it again finds every slot taken.
        try {
            (new SessionGenerator())->create($this->input([$a], ['wave_id' => $wave, 'weekdays' => [6]]), (int) $staff->id);
            $this->fail('nothing left to create');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Tidak ada sesi yang bisa dibuat', $e->getMessage());
        }

        $this->assertSame(2, model(TestSessionModel::class)->countAllResults());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidInput(): iterable
    {
        yield 'to before from' => [['date_to' => '2027-06-01'], 'date_to', 'setelah tanggal awal'];
        yield 'range too long' => [['date_to' => '2028-07-01'], 'date_to', '366 hari'];
        yield 'no weekday' => [['weekdays' => []], 'weekdays', 'minimal satu hari'];
        yield 'no slot' => [['slots' => [['start' => '', 'end' => '']]], 'slots', 'minimal satu jam'];
        yield 'slot end before start' => [['slots' => [['start' => '10:00', 'end' => '09:00']]], 'slots', 'setelah jam mulainya'];
        yield 'slots overlap' => [['slots' => [['start' => '07:30', 'end' => '09:30'], ['start' => '09:00', 'end' => '10:00']]], 'slots', 'bertabrakan'];
        yield 'no room' => [['rooms' => []], 'rooms', 'minimal satu ruang'];
        yield 'unknown wave' => [['wave_id' => 999], 'wave_id', 'Gelombang tidak ditemukan'];
    }

    /**
     * @param array<string, mixed> $override
     *
     * @dataProvider invalidInput
     */
    public function testInvalidInputIsRefused(array $override, string $field, string $message): void
    {
        $preview = (new SessionGenerator())->preview($this->input([$this->makeRoom()], $override));

        $this->assertStringContainsString($message, $preview['errors'][$field] ?? '');
        $this->assertSame(0, $preview['count']);
    }

    public function testInactiveRoomIsRefused(): void
    {
        $preview = (new SessionGenerator())->preview($this->input([$this->makeRoom(['is_active' => 0])]));

        $this->assertArrayHasKey('rooms', $preview['errors']);
    }

    public function testDefaultEndUsesSettingAndRoomQuotaIsCapped(): void
    {
        setting('Tes.defaultDurationMinutes', 90);
        $room    = $this->makeRoom(['capacity' => 999]);
        $preview = (new SessionGenerator())->preview($this->input([$room], ['weekdays' => [1], 'slots' => [['start' => '08:00', 'end' => '']]]));

        $this->assertSame('09:30:00', $preview['sessions'][0]['ends_at']);
        $this->assertSame(999, $preview['sessions'][0]['quota']);
        $this->assertSame('09:30', SessionGenerator::addMinutes('08:00', 90));
        $this->assertSame('23:59', SessionGenerator::addMinutes('23:00', 120));
    }
}
