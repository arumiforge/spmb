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

    public function testIconIsALucideStrokeIcon(): void
    {
        $svg = icon('school', 'h-6 w-6 text-primary-600');

        $this->assertStringStartsWith('<svg class="h-6 w-6 text-primary-600" aria-hidden="true"', $svg);
        $this->assertStringContainsString('fill="none"', $svg);
        $this->assertStringContainsString('stroke="currentColor"', $svg);
        $this->assertStringContainsString('stroke-width="2"', $svg);
        $this->assertStringEndsWith('</svg>', $svg);
    }

    public function testIconAliasesMapToLucideNames(): void
    {
        $this->assertSame(icon('circle-check'), icon('check'));
        $this->assertSame(icon('circle-alert'), icon('warning'));
        $this->assertSame(icon('log-out'), icon('logout'));
        $this->assertSame(icon('settings'), icon('cog'));
        $this->assertSame(icon('chart-pie'), icon('chart'));
        $this->assertSame(icon('layout-grid'), icon('grid'));
        $this->assertSame(icon('clipboard-list'), icon('clipboard'));
        $this->assertSame(icon('house'), icon('home'));
    }

    public function testUnknownIconFallsBackToInfo(): void
    {
        $this->assertFalse(icon_exists('tidak-ada'));
        $this->assertSame(icon('info'), icon('tidak-ada'));
        $this->assertStringContainsString('class="a&quot;b"', icon('info', 'a"b'));
    }

    public function testEveryIconNameUsedInTheAppExists(): void
    {
        $names = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APPPATH, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php' || str_ends_with($file->getPathname(), 'lucide_icons.php')) {
                continue;
            }

            $code = (string) file_get_contents($file->getPathname());
            preg_match_all("/icon\\(\\s*'([a-z0-9-]+)'/", $code, $direct);
            preg_match_all("/'icon'\\s*=>\\s*'([a-z0-9-]+)'/", $code, $listed);

            foreach ([...$direct[1], ...$listed[1]] as $name) {
                $names[$name] = $file->getFilename();
            }
        }

        $this->assertGreaterThan(20, count($names));

        foreach ($names as $name => $where) {
            $this->assertTrue(icon_exists($name), "Icon '{$name}' (used in {$where}) is not in resources/icons.json.");
        }

        foreach (['camera', 'printer', 'undo-2', 'upload', 'download', 'message-circle-question'] as $name) {
            $this->assertTrue(icon_exists($name), $name);
        }
    }

    public function testGeneratedIconFileMatchesTheManifest(): void
    {
        $manifest = json_decode((string) file_get_contents(ROOTPATH . 'resources/icons.json'), true);
        $set      = require APPPATH . 'Helpers/lucide_icons.php';

        $this->assertSame(array_values(array_unique($manifest['icons'])), array_values(array_intersect($manifest['icons'], array_keys($set['icons']))));
        $this->assertCount(count(array_unique($manifest['icons'])), $set['icons']);
        $this->assertEqualsCanonicalizing($manifest['aliases'], $set['aliases']);
    }
}
