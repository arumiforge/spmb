<?php

declare(strict_types=1);

use App\Libraries\Locales;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Address prefixes and translated fields of the public website (D-109).
 *
 * @internal
 */
final class LocalesTest extends CIUnitTestCase
{
    public function testSplitPrefix(): void
    {
        $this->assertSame(['en', 'persyaratan'], Locales::splitPrefix('en/persyaratan'));
        $this->assertSame(['ar', ''], Locales::splitPrefix('ar'));
        $this->assertSame(['ar', 'cek-status/data'], Locales::splitPrefix('/ar/cek-status/data'));
        $this->assertSame([null, 'english'], Locales::splitPrefix('english'));
        $this->assertSame([null, 'id/x'], Locales::splitPrefix('id/x'));
    }

    public function testPrefixPathSkipsStaffFilesAndFullAddresses(): void
    {
        $this->assertSame('en/jadwal', Locales::prefixPath('jadwal', 'en'));
        $this->assertSame('ar/', Locales::prefixPath('/', 'ar'));
        $this->assertSame('ar/cek-status?x=1#a', Locales::prefixPath('/cek-status?x=1#a', 'ar'));
        $this->assertSame('jadwal', Locales::prefixPath('jadwal', 'id'));
        $this->assertSame('en/jadwal', Locales::prefixPath('en/jadwal', 'en'));

        foreach (['admin/pendaftar', 'masuk', 'keluar', 'aset/logo', 'build/app.css', 'berkas/contoh/kk', 'popup/gambar'] as $path) {
            $this->assertSame($path, Locales::prefixPath($path, 'en'), $path);
        }

        $this->assertSame('https://example.test/x', Locales::prefixPath('https://example.test/x', 'en'));
        $this->assertSame('mailto:a@example.test', Locales::prefixPath('mailto:a@example.test', 'en'));
    }

    public function testFieldFallsBackToIndonesian(): void
    {
        $row = ['name' => 'Gelombang 1', 'name_en' => 'Round 1', 'name_ar' => '  '];

        $this->assertSame('Round 1', Locales::field($row, 'name', 'en'));
        $this->assertSame('Gelombang 1', Locales::field($row, 'name', 'ar'));
        $this->assertSame('Gelombang 1', Locales::field($row, 'name', 'id'));
        $this->assertSame('', Locales::field([], 'name', 'en'));
    }

    public function testDirectionAndNames(): void
    {
        $this->assertSame('rtl', Locales::direction('ar'));
        $this->assertSame('ltr', Locales::direction('en'));
        $this->assertSame(['id', 'en', 'ar'], Locales::ALL);
        $this->assertSame('العربية', Locales::LABELS['ar']);
    }
}
