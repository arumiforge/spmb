<?php

declare(strict_types=1);

use App\Libraries\BrandingAssets;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\StreamFilterTrait;
use Config\School;

/**
 * School brand images (D-054): branding pack command, public asset routes,
 * link-preview tags, wide logo and parent organisation in the layouts.
 *
 * @internal
 */
final class BrandingTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StreamFilterTrait;

    private const PACK = SUPPORTPATH . 'Branding/pack';

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        foreach (BrandingAssets::ASSETS as $asset) {
            BrandingAssets::remove((string) setting('School.' . $asset['setting']));
        }

        foreach (array_keys(get_class_vars(School::class)) as $key) {
            setting()->forget('School.' . $key);
        }

        parent::tearDown();
    }

    private function loadPack(string $dir = self::PACK): void
    {
        command('spmb:branding ' . $dir);
    }

    public function testCommandLoadsSettingsAndImages(): void
    {
        $this->loadPack();

        $this->assertSame('SMP Contoh Kudus', setting('School.name'));
        $this->assertSame('#005c2f', setting('School.primaryColor'));
        $this->assertSame('Lembaga Contoh', setting('School.partnerName'));

        foreach (['logo', 'logoWide', 'logoWideDark', 'partnerLogo', 'shareImage', 'favicon'] as $key) {
            $this->assertFileExists(BrandingAssets::DIR . BrandingAssets::file($key), $key);
        }

        // Too large images are scaled down to the asset's size.
        [$width, $height] = getimagesize(BrandingAssets::DIR . BrandingAssets::file('shareImage'));
        $this->assertSame([1200, 630], [$width, $height]);

        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_id' => 'School']);
    }

    public function testCommandIsSafeToRunAgain(): void
    {
        $this->loadPack();
        $first = BrandingAssets::file('logo');
        $this->loadPack();

        $this->assertNotSame($first, BrandingAssets::file('logo'));
        $this->assertFileDoesNotExist(BrandingAssets::DIR . $first);
    }

    public function testCommandRejectsMissingPack(): void
    {
        $this->loadPack(SUPPORTPATH . 'Branding/tidak-ada');

        $this->assertStringContainsString('branding.json not found', $this->getStreamFilterBuffer());
        $this->assertSame('Nama Sekolah', setting('School.name'));
    }

    public function testStoreRejectsNonImages(): void
    {
        $this->expectException(RuntimeException::class);
        BrandingAssets::storeFile(self::PACK . '/notes.txt', 'logo', 'png');
    }

    public function testAssetsAreServedByTheirSlug(): void
    {
        $this->loadPack();

        $result = $this->get('aset/logo-lebar');
        $result->assertOK();
        $result->assertHeader('Content-Type', 'image/png');
        $result->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get('aset/gambar-bagikan')->assertOK();
        $this->get('aset/ikon')->assertStatus(404);        // not in the pack
        $this->get('aset/tidak-ada')->assertStatus(404);
    }

    public function testAssetAnswersNotModifiedForAMatchingEtag(): void
    {
        $this->loadPack();

        $first = $this->get('aset/logo');
        $first->assertOK();
        $etag = $first->response()->getHeaderLine('ETag');
        $this->assertMatchesRegularExpression('/\A"[0-9a-f]{16}"\z/', $etag);
        $this->assertStringContainsString('max-age=31536000', $first->response()->getHeaderLine('Cache-Control'));

        $again = $this->withHeaders(['If-None-Match' => $etag])->get('aset/logo');
        $again->assertStatus(304);
        $this->assertSame('', trim((string) $again->getBody()));
        $this->assertSame($etag, $again->response()->getHeaderLine('ETag'));

        // Another tag (an older file) gets the whole picture again.
        $other = $this->withHeaders(['If-None-Match' => '"0000000000000000"'])->get('aset/logo');
        $other->assertOK();
        $this->assertNotSame('', (string) $other->getBody());
    }

    public function testLayoutsShowBrandImagesAndPreviewTags(): void
    {
        $this->loadPack();

        $page = $this->get('panduan');
        $page->assertOK();
        $body = $page->getBody();
        $this->assertStringContainsString('property="og:image" content="' . site_url('aset/gambar-bagikan'), $body);
        $this->assertStringContainsString('<meta name="theme-color" content="#005c2f">', $body);
        $page->assertSee('summary_large_image');
        $page->assertSee('aset/logo-lebar?v=');
        $page->assertSee('aset/logo-lebar-gelap?v=');
        $page->assertSee('Di bawah naungan');
        $page->assertSee('Lembaga Contoh');
        $page->assertSee('aset/logo-naungan?v=');

        $this->get('masuk')->assertSee('aset/logo-lebar?v=');
    }

    public function testWithoutBrandImagesTheDefaultsStay(): void
    {
        $page = $this->get('panduan');

        // No share image uploaded: the generic painting (brief 035) is the link preview.
        $page->assertSee('assets/img/share/bagikan-umum');
        $page->assertDontSee('aset/gambar-bagikan');
        $page->assertDontSee('Di bawah naungan');
        $page->assertDontSee('aset/logo-lebar');
    }

    public function testAdminUploadsAndRemovesAnImage(): void
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'adminbrand', 'email' => 'adminbrand@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $admin = $users->findById($users->getInsertID());
        $admin->addGroup('admin');

        $this->loadPack();
        $stored = BrandingAssets::file('partnerLogo');

        $post = [
            'name'        => 'SMP Contoh Kudus', 'shortName' => 'SMP Contoh', 'primaryColor' => '#005c2f', 'timezoneLabel' => 'WIB',
            'partnerName' => '', 'illustrationAccent' => 'hijau', 'remove_partnerLogo' => '1',
        ];
        $this->actingAs($admin)->post('admin/pengaturan/sekolah', $post)->assertRedirectTo(site_url('admin/pengaturan/sekolah'));

        $this->assertSame('', BrandingAssets::file('partnerLogo'));
        $this->assertFileDoesNotExist(BrandingAssets::DIR . $stored);
        $this->assertSame('hijau', setting('School.illustrationAccent'));

        $this->actingAs($admin)->post('admin/pengaturan/sekolah', ['illustrationAccent' => 'ungu'] + $post)->assertSessionHas('errors');
    }

    public function testIllustrationAccentFallsBackToTheDefault(): void
    {
        $default = illustration('hero/hero-beranda', 'x');
        setting('School.illustrationAccent', 'hijau');

        // No green version installed yet: the default painting is used.
        $this->assertSame($default, illustration('hero/hero-beranda', 'x'));
        $this->assertStringNotContainsString('-hijau', $default);
    }
}
