<?php

declare(strict_types=1);

use App\Libraries\ModuleRegistry;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\Kartu\KartuFixtures;

/**
 * Home page actions follow the registration state (FR-WEB-01).
 *
 * @internal
 */
final class HomeTest extends CIUnitTestCase
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
        Services::resetSingle('modules');
        parent::tearDown();
    }

    public function testRegisterButtonWhileAWaveIsOpen(): void
    {
        $this->makeWave(['name' => 'Gelombang 1', 'starts_on' => '2027-01-04', 'ends_on' => '2027-02-27']);

        $result = $this->get('/');

        $result->assertOK();
        $result->assertSee('Daftar Sekarang');
        $result->assertSee('href="' . site_url('daftar') . '"');
        $result->assertSee('Cek Status');
        $result->assertSee(site_url('persyaratan'));
        $result->assertSee('Gelombang 1 sedang dibuka sampai 27 Februari 2027.');
    }

    public function testNoRegisterButtonWithoutOpenWave(): void
    {
        $this->makeWave(['name' => 'Gelombang 2', 'starts_on' => '2027-03-01', 'ends_on' => '2027-04-15']);

        $result = $this->get('/');

        $result->assertDontSee('Daftar Sekarang');
        $result->assertSee('Cek Status');
        $result->assertSee('Gelombang 2 dibuka mulai 1 Maret 2027.');
    }

    public function testNoRegisterButtonWhenOnlineFormIsOff(): void
    {
        setting('Pendaftaran.onlineOpen', false);
        $this->makeWave();

        $result = $this->get('/');

        $result->assertDontSee('Daftar Sekarang');
        $result->assertSee('datang langsung');
        $result->assertSee('Cek Status');
    }

    public function testWithoutRegistrationModuleOnlyTheGuideIsOffered(): void
    {
        Services::injectMock('modules', new ModuleRegistry(APPPATH . 'Modules', config('Features')->core, ['Pendaftaran']));
        $this->makeWave();

        $result = $this->get('/');

        $result->assertOK();
        $result->assertDontSee('Daftar Sekarang');
        $result->assertDontSee('Cek Status');
        $result->assertSee('Baca Panduan Pendaftaran');
    }
}
