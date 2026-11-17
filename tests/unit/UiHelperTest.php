<?php

declare(strict_types=1);

use App\Libraries\Menu;
use App\Libraries\ModuleRegistry;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UiHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('ui');
    }

    public function testHexColoursAreNormalised(): void
    {
        $this->assertSame('#1d4ed8', valid_hex_color('#1D4ED8'));
        $this->assertSame('#aabbcc', valid_hex_color('abc'));
        $this->assertNull(valid_hex_color('red'));
        $this->assertNull(valid_hex_color('#12345g'));
        $this->assertNull(valid_hex_color('#123456;}'));
    }

    public function testIllustrationIncludesDarkVariantWhenPresent(): void
    {
        $html = illustration('state/kosong-data', 'Belum ada data.', 'h-32');

        $this->assertStringContainsString('assets/img/state/kosong-data.webp', $html);
        $this->assertStringContainsString('assets/img/state/kosong-data-dark.webp', $html);
        $this->assertStringContainsString('alt="Belum ada data."', $html);
        $this->assertStringContainsString('class="h-32 dark:hidden"', $html);
        $this->assertStringContainsString('class="h-32 hidden dark:block"', $html);
        $this->assertMatchesRegularExpression('/width="\d+" height="\d+"/', $html);
    }

    public function testIllustrationWithoutDarkVariantIsOneImage(): void
    {
        $html = illustration('hero/hero-beranda', 'Hero');

        $this->assertSame(1, substr_count($html, '<img'));
        $this->assertStringNotContainsString('dark:hidden', $html);
    }

    public function testIllustrationRejectsMissingOrUnsafePaths(): void
    {
        $this->assertSame('', illustration('state/tidak-ada', 'x'));
        $this->assertSame('', illustration('../app/Config', 'x'));
    }

    public function testEveryInstalledIllustrationExists(): void
    {
        foreach (['hero/hero-beranda', 'auth/masuk-dasbor', 'state/kosong-data', 'state/kosong-pencarian', 'state/tidak-ditemukan', 'state/gangguan-sistem', 'state/kosong-riwayat', 'state/fitur-tambahan', 'guide/panduan-petugas', 'guide/panduan-calon-murid'] as $path) {
            $this->assertFileExists(FCPATH . 'assets/img/' . $path . '.webp');
        }
    }

    public function testInitials(): void
    {
        $this->assertSame('SA', initials('Siti Aminah Rahma'));
        $this->assertSame('B', initials('budi'));
        $this->assertSame('?', initials('  '));
    }

    public function testIndonesianDate(): void
    {
        $this->assertSame('17 Agustus 2027', tanggal_indo('2027-08-17 08:00:00'));
        $this->assertSame('-', tanggal_indo(null));
    }

    public function testRegistryFindsModulesAndHonoursDisabledList(): void
    {
        $registry = new ModuleRegistry(APPPATH . 'Modules', ['Pengguna'], ['Panduan', 'Pengguna']);

        $this->assertContains('Pengguna', $registry->all());
        $this->assertTrue($registry->isEnabled('Pengguna'), 'core modules cannot be disabled');
        $this->assertFalse($registry->isEnabled('Panduan'));
        $this->assertNotContains('Panduan', $registry->enabled());
        $this->assertNull($registry->file('Panduan', 'Config/Routes.php'));
    }

    public function testMenuGroupsAndOrder(): void
    {
        $menu  = new Menu(new ModuleRegistry(APPPATH . 'Modules', [], []), [['label' => 'Dasbor', 'url' => 'admin', 'order' => 0]]);
        $items = $menu->items();

        $this->assertSame('Dasbor', $items[0]['label']);
        $this->assertContains('Pengguna', array_column($items, 'label'));
    }

    public function testStatusBadgeForSharedCodes(): void
    {
        $this->assertSame(['label' => 'Lengkap', 'color' => 'green'], status_badge_for('documents', 'complete'));
        $this->assertSame(['label' => 'Di sekolah', 'color' => 'gray'], status_badge_for('method', 'offline'));
        $this->assertSame(['label' => 'aneh', 'color' => 'gray'], status_badge_for('applicant', 'aneh'));
    }
}
