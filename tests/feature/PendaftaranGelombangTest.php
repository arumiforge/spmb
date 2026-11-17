<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Pendaftaran\Models\WaveModel;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * Waves (FR-REG-04): CRUD, no overlapping dates within a year, permission.
 *
 * @internal
 */
final class PendaftaranGelombangTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PendaftaranFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetVisitor();
    }

    protected function tearDown(): void
    {
        $this->forgetPendaftaranSettings();
        parent::tearDown();
    }

    public function testPermissions(): void
    {
        $this->get('admin/pengaturan/gelombang')->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->makeUser('panitia'))->get('admin/pengaturan/gelombang')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($this->makeUser('panitia'))->post('admin/pengaturan/gelombang', ['name' => 'X'])->assertRedirectTo(site_url('admin/akses-ditolak'));

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('admin/pengaturan/gelombang')->assertSee('Belum ada gelombang');
        $this->actingAs($admin)->get('admin/pengaturan/gelombang/tambah')->assertOK();
    }

    public function testAdminCreatesEditsAndDeletesWave(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('admin/pengaturan/gelombang', [
            'name' => 'Gelombang 1', 'starts_on' => '2027-01-04', 'ends_on' => '2027-02-27', 'quota' => '100', 'is_active' => '1',
        ])->assertRedirect();

        $wave = model(WaveModel::class)->where('name', 'Gelombang 1')->first();
        $this->assertNotNull($wave);
        $this->assertSame('2027/2028', $wave['academic_year']);
        $this->assertSame('100', (string) $wave['quota']);
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'waves']);

        $list = $this->actingAs($admin)->get('admin/pengaturan/gelombang');
        $list->assertSee('Gelombang 1');
        $list->assertSee('4 Januari 2027');
        $this->actingAs($admin)->get('admin/pengaturan/gelombang/' . $wave['id'] . '/ubah')->assertOK();

        $this->actingAs($admin)->post('admin/pengaturan/gelombang/' . $wave['id'], [
            'name' => 'Gelombang Satu', 'starts_on' => '2027-01-04', 'ends_on' => '2027-03-01', 'quota' => '', 'is_active' => '0',
        ])->assertRedirect();

        $wave = model(WaveModel::class)->find($wave['id']);
        $this->assertSame('Gelombang Satu', $wave['name']);
        $this->assertNull($wave['quota']);
        $this->assertSame('0', (string) $wave['is_active']);

        $this->actingAs($admin)->post('admin/pengaturan/gelombang/' . $wave['id'] . '/hapus')->assertRedirect();
        $this->assertNull(model(WaveModel::class)->find($wave['id']));
    }

    public function testOverlappingDatesAreRejected(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeWave(['name' => 'Gelombang 1', 'starts_on' => '2027-01-04', 'ends_on' => '2027-02-27']);

        $result = $this->actingAs($admin)->post('admin/pengaturan/gelombang', [
            'name' => 'Gelombang 2', 'starts_on' => '2027-02-27', 'ends_on' => '2027-03-31', 'is_active' => '1',
        ]);

        $result->assertSessionHas('errors');
        $this->assertStringContainsString('bertabrakan', session('errors')['starts_on']);
        $this->assertSame(1, model(WaveModel::class)->countAllResults());

        // Another academic year may use the same dates.
        $this->makeWave(['academic_year' => '2028/2029', 'name' => 'Gelombang 1', 'starts_on' => '2027-01-04', 'ends_on' => '2027-02-27']);
        $this->assertSame(2, model(WaveModel::class)->countAllResults());
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/gelombang', [
            'name' => 'Gelombang 1', 'starts_on' => '2027-03-01', 'ends_on' => '2027-02-01',
        ])->assertSessionHas('errors');

        $this->assertSame(0, model(WaveModel::class)->countAllResults());
    }

    public function testWaveWithApplicantsCannotBeDeleted(): void
    {
        $waveId = $this->makeWave();
        db_connect()->table('applicants')->insert([
            'registration_number' => 'PMB-2027-0001', 'academic_year' => '2027/2028', 'wave_id' => $waveId, 'method' => 'online',
            'full_name'           => 'Ahmad', 'name_key' => 'ahmad', 'gender' => 'L', 'birth_place' => 'Kudus', 'birth_date' => '2015-01-01',
            'parent_phone'        => '081234567890', 'registered_at' => date('Y-m-d H:i:s'),
        ]);

        $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/gelombang/' . $waveId . '/hapus')->assertSessionHas('error');
        $this->assertNotNull(model(WaveModel::class)->find($waveId));
    }

    public function testCurrentAndForYear(): void
    {
        $model = model(WaveModel::class);
        $this->assertNull($model->current());

        $this->makeWave(['name' => 'Lama', 'starts_on' => '2020-01-01', 'ends_on' => '2020-02-01']);
        $open = $this->makeWave(['name' => 'Sekarang']);
        $this->makeWave(['name' => 'Mati', 'starts_on' => date('Y-m-d', strtotime('+20 days')), 'ends_on' => date('Y-m-d', strtotime('+30 days')), 'is_active' => 0]);

        $this->assertSame($open, (int) $model->current()['id']);
        $this->assertNull($model->current(new DateTimeImmutable('+25 days')));
        $this->assertSame(['Lama', 'Sekarang', 'Mati'], array_column($model->forYear('2027/2028'), 'name'));
        $this->assertSame([], $model->forYear('2030/2031'));
    }
}
