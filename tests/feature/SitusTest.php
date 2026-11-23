<?php

declare(strict_types=1);

use App\Libraries\ModuleRegistry;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Situs\Services\WaveSchedule;
use Tests\Support\Kartu\KartuFixtures;

/**
 * Public information pages /persyaratan and /jadwal (FR-WEB-01, FR-WEB-02).
 *
 * @internal
 */
final class SitusTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use KartuFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        Time::setTestNow('2027-01-10 09:00:00', 'Asia/Jakarta');
        service('routes')->loadRoutes();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        setting()->forget('Pendaftaran.onlineOpen');
        setting()->forget('Pendaftaran.academicYear');
        Services::resetSingle('modules');
        parent::tearDown();
    }

    /**
     * @param list<string> $disabled
     */
    private function disable(array $disabled): void
    {
        Services::injectMock('modules', new ModuleRegistry(APPPATH . 'Modules', config('Features')->core, $disabled));
    }

    private function seedDocuments(): void
    {
        $types = model(DocumentTypeModel::class);
        $types->insert(['code' => 'kk', 'name' => 'Kartu Keluarga', 'is_required' => 1, 'max_size_kb' => 2048, 'extensions' => 'jpg,jpeg,png,pdf', 'sort_order' => 1]);
        $types->insert(['code' => 'rapor', 'name' => 'Rapor Kelas 6', 'is_required' => 1, 'may_follow' => 1, 'max_size_kb' => 512, 'extensions' => 'pdf', 'sort_order' => 2]);
        $types->insert(['code' => 'lama', 'name' => 'Berkas Lama', 'is_active' => 0, 'sort_order' => 3]);
    }

    public function testRequirementsListActiveDocumentsAndSteps(): void
    {
        $this->seedDocuments();

        $result = $this->get('persyaratan');

        $result->assertOK();
        $result->assertSee('Persyaratan Pendaftaran');
        $result->assertSee('Kartu Keluarga');
        $result->assertSee('Format JPG, PNG, PDF, paling besar 2 MB');
        $result->assertSee('Rapor Kelas 6');
        $result->assertSee('Boleh menyusul');
        $result->assertSee('paling besar 512 KB');
        $result->assertDontSee('Berkas Lama');
        $result->assertSee('Unggah berkas melalui halaman Cek Status');
        $result->assertSee('Unduh dan cetak kartu pendaftaran');
        $this->assertLessThan(strpos($result->getBody(), 'Rapor Kelas 6'), strpos($result->getBody(), 'Kartu Keluarga'));
    }

    public function testRequirementsWithoutBerkasModule(): void
    {
        $this->seedDocuments();
        $this->disable(['Berkas']);

        $result = $this->get('persyaratan');

        $result->assertOK();
        $result->assertSee('Daftar berkas belum diumumkan');
        $result->assertDontSee('Kartu Keluarga');
        $result->assertDontSee('Unggah berkas melalui halaman Cek Status');
    }

    public function testRequirementsWithoutDocumentTypes(): void
    {
        $this->get('persyaratan')->assertSee('Daftar berkas belum diumumkan');
    }

    public function testScheduleWithoutWaves(): void
    {
        $result = $this->get('jadwal');

        $result->assertOK();
        $result->assertSee('Jadwal belum diumumkan');
    }

    public function testScheduleShowsWaveStates(): void
    {
        $this->makeWave(['name' => 'Gelombang Awal', 'starts_on' => '2026-11-01', 'ends_on' => '2026-12-31']);
        $this->makeWave(['name' => 'Gelombang Utama', 'starts_on' => '2027-01-04', 'ends_on' => '2027-02-27', 'quota' => 120]);
        $this->makeWave(['name' => 'Gelombang Akhir', 'starts_on' => '2027-03-01', 'ends_on' => '2027-04-15']);
        $this->makeWave(['name' => 'Gelombang Nonaktif', 'is_active' => 0]);
        $this->makeWave(['name' => 'Gelombang Tahun Lalu', 'academic_year' => '2026/2027']);

        $result = $this->get('jadwal');

        $result->assertOK();
        $result->assertSee('Sudah ditutup');
        $result->assertSee('Sedang dibuka');
        $result->assertSee('Akan dibuka');
        $result->assertSee('Kuota 120 murid');
        $result->assertSee('4 Januari 2027 sampai 27 Februari 2027');
        $result->assertSee('Gelombang Utama sedang dibuka sampai 27 Februari 2027.');
        $result->assertSee('Daftar Sekarang');
        $result->assertDontSee('Gelombang Nonaktif');
        $result->assertDontSee('Gelombang Tahun Lalu');
    }

    public function testLongWaveDescriptionFolds(): void
    {
        $this->makeWave(['name' => 'Gelombang Utama', 'starts_on' => '2027-01-04', 'ends_on' => '2027-02-27', 'description' => "Baris pertama.\nBaris kedua."]);

        $body = $this->get('jadwal')->getBody();

        // Folded by ui.js only when it is really long ("Lihat selengkapnya", D-088); open without JavaScript.
        $this->assertStringContainsString('data-lipat data-lipat-tinggi="6rem"', $body);
        $this->assertMatchesRegularExpression('#Baris pertama\.<br ?/?>\s*Baris kedua\.#', $body);
    }

    public function testScheduleWhenOnlineFormIsOff(): void
    {
        setting('Pendaftaran.onlineOpen', false);
        $this->makeWave(['name' => 'Gelombang Utama']);

        $result = $this->get('jadwal');

        $result->assertSee('Pendaftaran online sedang ditutup');
        $result->assertDontSee('Daftar Sekarang');
    }

    public function testWaveSchedule(): void
    {
        $this->makeWave(['name' => 'G1', 'starts_on' => '2027-01-04', 'ends_on' => '2027-01-10']);
        $this->makeWave(['name' => 'G2', 'starts_on' => '2027-01-11', 'ends_on' => '2027-02-27']);

        $this->assertSame('G1', WaveSchedule::current('2027-01-10')['name']);
        $this->assertSame('G2', WaveSchedule::next('2027-01-10')['name']);
        $this->assertTrue(WaveSchedule::onlineOpen('2027-01-04'));
        $this->assertFalse(WaveSchedule::onlineOpen('2027-03-01'));
        $this->assertSame('upcoming', WaveSchedule::banner('2027-01-01')['state']);
        $this->assertSame('closed', WaveSchedule::banner('2027-03-01')['state']);

        setting('Pendaftaran.onlineOpen', false);
        $this->assertFalse(WaveSchedule::onlineOpen('2027-01-04'));
        $this->assertSame('offline', WaveSchedule::banner('2027-01-04')['state']);
    }
}
