<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * Registration settings page (D-031, D-037).
 *
 * @internal
 */
final class PendaftaranPengaturanTest extends CIUnitTestCase
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
        $this->resetVisitor();
        parent::tearDown();
    }

    private function post_(array $overrides = []): array
    {
        return $overrides + [
            'academicYear' => '2028/2029',
            'onlineOpen'   => '0',
            'numberFormat' => 'SPMB/{YY}/{000}',
            'numberScope'  => 'never',
            'fields'       => ['nik' => 'required', 'nisn' => 'optional', 'kk_number' => 'hidden', 'religion' => 'hidden'],
        ];
    }

    public function testPermissions(): void
    {
        $this->get('admin/pengaturan/pendaftaran')->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->makeUser('panitia'))->get('admin/pengaturan/pendaftaran')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($this->makeUser('kepsek'))->post('admin/pengaturan/pendaftaran', $this->post_())->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertSame('2027/2028', setting('Pendaftaran.academicYear'));

        $this->actingAs($this->makeUser('admin'))->get('admin/pengaturan/pendaftaran')->assertSee('PMB-2027-0012');
    }

    public function testAdminSavesSettings(): void
    {
        $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/pendaftaran', $this->post_())
            ->assertRedirectTo(site_url('admin/pengaturan/pendaftaran'));

        $this->assertSame('2028/2029', setting('Pendaftaran.academicYear'));
        $this->assertFalse(setting('Pendaftaran.onlineOpen'));
        $this->assertSame('SPMB/{YY}/{000}', setting('Pendaftaran.numberFormat'));
        $this->assertSame('never', setting('Pendaftaran.numberScope'));
        $this->assertSame(['nik'], setting('Pendaftaran.requiredFields'));
        $this->assertSame(['kk_number', 'religion'], setting('Pendaftaran.hiddenFields'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_id' => 'Pendaftaran']);
    }

    public function testInvalidValuesAreRejected(): void
    {
        $admin = $this->makeUser('admin');

        foreach ([
            ['academicYear' => '2027/2029'],
            ['academicYear' => '27/28'],
            ['numberFormat' => 'PMB-{YEAR}'],
            ['numberFormat' => 'PMB {0000}'],
            ['numberFormat' => '{0000}-{000}'],
            ['numberScope' => 'month'],
        ] as $bad) {
            $this->actingAs($admin)->post('admin/pengaturan/pendaftaran', $this->post_($bad))->assertSessionHas('errors');
            $this->assertSame('2027/2028', setting('Pendaftaran.academicYear'), json_encode($bad));
        }
    }

    public function testOneParentNameStaysOnTheForm(): void
    {
        $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/pendaftaran', $this->post_([
            'fields' => ['father_name' => 'hidden', 'mother_name' => 'hidden', 'guardian_name' => 'hidden'],
        ]));

        $this->assertSame(['father_name', 'guardian_name'], setting('Pendaftaran.hiddenFields'));
    }
}
