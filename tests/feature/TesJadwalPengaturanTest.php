<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\Tes\TesFixtures;
use Tests\Support\Tes\TesJadwalHelpers;

/**
 * Package A — Pengaturan → Tes.
 *
 * @internal
 */
final class TesJadwalPengaturanTest extends CIUnitTestCase
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
        $this->get('admin/pengaturan/tes')->assertRedirectTo(site_url('masuk'));
        $this->post('admin/pengaturan/tes', ['defaultDurationMinutes' => '60'])->assertRedirectTo(site_url('masuk'));

        $panitia = $this->makeStaff('panitia');
        $this->actingAs($panitia)->get('admin/pengaturan/tes')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->post('admin/pengaturan/tes', ['defaultDurationMinutes' => '60'])->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertSame(120, (int) setting('Tes.defaultDurationMinutes'));

        $page = $this->actingAs($this->makeStaff('admin'))->get('admin/pengaturan/tes');
        $page->assertSee('Tampilkan jadwal tes kepada pendaftar');
        $page->assertSee('Sudah diperiksa');
        $page->assertDontSee('Dibatalkan');
    }

    public function testSaveIsAudited(): void
    {
        $this->actingAs($this->makeStaff('admin'))->post('admin/pengaturan/tes', [
            'scheduleVisible'          => '0',
            'eligibleStatuses'         => ['', 'verified', 'cancelled'],
            'requireCompleteDocuments' => '1',
            'instructions'             => 'Datang jam 7.',
            'defaultDurationMinutes'   => '90',
        ])->assertRedirectTo(site_url('admin/pengaturan/tes'));

        $this->assertFalse(setting('Tes.scheduleVisible'));
        $this->assertSame(['verified'], setting('Tes.eligibleStatuses'), 'cancelled is never eligible');
        $this->assertTrue(setting('Tes.requireCompleteDocuments'));
        $this->assertSame('Datang jam 7.', setting('Tes.instructions'));
        $this->assertSame(90, setting('Tes.defaultDurationMinutes'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'Tes']);
    }

    public function testInvalidValuesAreRefused(): void
    {
        $admin = $this->makeStaff('admin');
        $base  = ['eligibleStatuses' => ['registered'], 'defaultDurationMinutes' => '120', 'instructions' => ''];

        $this->actingAs($admin)->post('admin/pengaturan/tes', ['defaultDurationMinutes' => '20'] + $base)->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/pengaturan/tes', ['defaultDurationMinutes' => '500'] + $base)->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/pengaturan/tes', ['instructions' => str_repeat('a', 501)] + $base)->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/pengaturan/tes', ['eligibleStatuses' => ['']] + $base)->assertSessionHas('errors');
        $this->assertSame(['registered', 'verified'], setting('Tes.eligibleStatuses'));
        $this->dontSeeInDatabase('audit_logs', ['event' => 'settings_updated']);
    }
}
