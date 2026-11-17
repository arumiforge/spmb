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
}
