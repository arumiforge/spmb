<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Tes\Models\TestHolidayModel;
use Modules\Tes\Models\TestSessionModel;
use Tests\Support\Tes\TesFixtures;
use Tests\Support\Tes\TesJadwalHelpers;

/**
 * Package A — test calendar, holidays and the generator pages.
 *
 * @internal
 */
final class TesJadwalKalenderTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use TesFixtures;
    use TesJadwalHelpers;

    protected $namespace;
    protected $refresh = true;

    public function testPermissions(): void
    {
        $id      = (int) model(TestHolidayModel::class)->insert(['date' => '2027-06-17', 'note' => 'Idul Adha']);
        $panitia = $this->makeStaff('panitia');

        foreach ([['get', 'admin/tes/kalender'], ['post', 'admin/tes/kalender/libur'], ['post', 'admin/tes/kalender/libur/' . $id . '/hapus']] as [$method, $url]) {
            $this->resetTesVisitor();
            $this->{$method}($url)->assertRedirectTo(site_url('masuk'));
            $this->actingAs($panitia)->{$method}($url)->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        $this->assertNotNull(model(TestHolidayModel::class)->find($id));
    }

    public function testMonthShowsSessionsSeatsAndHolidays(): void
    {
        $s = $this->makeSession(['quota' => 10]);
        $this->makeAssignment((int) $this->makeApplicant()['id'], $s);
        model(TestHolidayModel::class)->insert(['date' => '2027-06-17', 'note' => 'Idul Adha']);

        $admin = $this->makeStaff('admin');
        $page  = $this->actingAs($admin)->get('admin/tes/kalender?bulan=2027-06');
        $page->assertSee('Juni 2027');
        $page->assertSee('1 hari tes, 1 sesi, 1 dari 10 kursi terisi');
        $page->assertSee('1/10 kursi');
        $page->assertSee('Idul Adha');
        $page->assertSee('Kamis, 17 Juni 2027');
        $page->assertSee('Sabtu, 12 Juni 2027'); // phone list

        $this->actingAs($admin)->get('admin/tes/kalender?bulan=bukan-bulan')->assertOK();
    }

    public function testAddAndDeleteHoliday(): void
    {
        $admin = $this->makeStaff('admin');

        $this->actingAs($admin)->post('admin/tes/kalender/libur', ['date' => '2027-06-17', 'note' => 'Idul Adha'])
            ->assertRedirectTo(site_url('admin/tes/kalender') . '?bulan=2027-06');
        $holiday = model(TestHolidayModel::class)->where('date', '2027-06-17')->first();
        $this->assertSame('Idul Adha', $holiday['note']);
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'test_holidays']);

        $this->actingAs($admin)->post('admin/tes/kalender/libur', ['date' => '2027-06-17'])->assertSessionHas('errors');
        $this->assertStringContainsString('sudah ditandai', session('errors')['date']);

        $this->resetTesVisitor();
        $this->actingAs($admin)->post('admin/tes/kalender/libur/' . $holiday['id'] . '/hapus')->assertSessionHas('message');
        $this->assertSame(0, model(TestHolidayModel::class)->countAllResults());
        $this->seeInDatabase('audit_logs', ['event' => 'purged', 'subject_type' => 'test_holidays']);
        $this->actingAs($admin)->post('admin/tes/kalender/libur/' . $holiday['id'] . '/hapus')->assertSessionHas('error');
    }

    public function testHolidayRefusedOnADayWithSessions(): void
    {
        $this->makeSession();
        $admin = $this->makeStaff('admin');

        $this->actingAs($admin)->post('admin/tes/kalender/libur', ['date' => '2027-06-12', 'note' => 'Rapat'])->assertSessionHas('errors');
        $this->assertStringContainsString('sudah ada 1 sesi tes', session('errors')['date']);
        $this->assertSame(0, model(TestHolidayModel::class)->countAllResults());

        $this->actingAs($admin)->post('admin/tes/kalender/libur', ['date' => 'besok'])->assertSessionHas('errors');
    }

    public function testGeneratorPreviewThenCreate(): void
    {
        $admin = $this->makeStaff('admin');
        $room  = $this->makeRoom(['name' => 'Aula', 'capacity' => 40]);
        $post  = [
            'date_from'  => '2027-06-07', 'date_to' => '2027-06-12', 'weekdays' => ['6'],
            'slot_start' => ['07:30', '10:00'], 'slot_end' => ['09:30', ''], 'rooms' => [(string) $room], 'wave_id' => '', 'label' => 'Sesi {n}',
        ];

        $this->actingAs($admin)->get('admin/tes/sesi/buat-banyak')->assertSee('Aula');

        $preview = $this->actingAs($admin)->post('admin/tes/sesi/buat-banyak/pratinjau', $post);
        $preview->assertSee('Pratinjau');
        $preview->assertSee('Buat 2 Sesi');
        $preview->assertSee('Sabtu, 12 Juni 2027');
        $this->assertSame(0, model(TestSessionModel::class)->countAllResults());

        $invalid = $this->actingAs($admin)->post('admin/tes/sesi/buat-banyak/pratinjau', ['weekdays' => []] + $post);
        $invalid->assertSee('Pilih minimal satu hari.');
        $invalid->assertDontSee('Buat 2 Sesi');

        $this->actingAs($admin)->post('admin/tes/sesi/buat-banyak', $post)->assertRedirectTo(site_url('admin/tes/sesi'));
        $this->assertSame(2, model(TestSessionModel::class)->countAllResults());
        $this->seeInDatabase('test_sessions', ['starts_at' => '10:00:00', 'ends_at' => '12:00:00', 'label' => 'Sesi 2', 'quota' => 40]);
        $this->seeInDatabase('audit_logs', ['event' => 'sessions_generated']);

        $this->resetTesVisitor();
        $this->actingAs($admin)->post('admin/tes/sesi/buat-banyak', $post)->assertRedirectTo(site_url('admin/tes/sesi/buat-banyak'));
        $this->assertSame(2, model(TestSessionModel::class)->countAllResults());
    }
}
