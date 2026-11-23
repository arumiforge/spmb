<?php

declare(strict_types=1);

use App\Libraries\BrandingAssets;
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

        foreach (BrandingAssets::HERO_SLIDES as $key) {
            BrandingAssets::remove(BrandingAssets::file($key));
            setting()->forget('School.' . BrandingAssets::ASSETS[$key]['setting']);
        }

        foreach (['motto', 'mapEmbed', 'address', 'socialInstagram', 'socialFacebook', 'socialYoutube', 'socialTiktok', 'whatsapp'] as $key) {
            setting()->forget('School.' . $key);
        }

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

    public function testHomeShowsTheSlogan(): void
    {
        setting('School.motto', 'Beriman, Cerdas, Terampil');

        $result = $this->get('/');

        $result->assertOK();
        $result->assertSee('Beriman, Cerdas, Terampil');
    }

    public function testMapIsShownOnlyForAGoogleMapsEmbedUrl(): void
    {
        setting('School.mapEmbed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961');

        $body = $this->get('/')->getBody();
        $this->assertStringContainsString('<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961"', $body);
        $this->assertStringContainsString('referrerpolicy="strict-origin-when-cross-origin"', $body);

        // A value that slipped into the settings some other way is never shown.
        setting('School.mapEmbed', 'https://evil.example/maps/embed?pb=1');
        $body = $this->get('/')->getBody();
        $this->assertStringNotContainsString('<iframe', $body);
        $this->assertStringNotContainsString('evil.example', $body);
    }

    public function testSocialLinksOnlyWhenSet(): void
    {
        $label = 'aria-label="' . lang('App.socialLabel') . '"';
        setting('School.whatsapp', '');
        $this->assertStringNotContainsString($label, $this->get('/')->getBody());

        setting('School.socialInstagram', 'https://instagram.com/smpcontoh');
        setting('School.socialYoutube', 'javascript:alert(1)');
        $body = $this->get('/')->getBody();
        $this->assertStringContainsString($label, $body);
        $this->assertStringContainsString('href="https://instagram.com/smpcontoh"', $body);
        $this->assertStringNotContainsString('javascript:alert(1)', $body);
        $this->assertStringNotContainsString('<span class="sr-only">Facebook</span>', $body);
        $this->assertStringNotContainsString('<span class="sr-only">YouTube</span>', $body);
    }

    public function testUploadedSlidesReplaceTheDefaultPaintings(): void
    {
        $default = $this->get('/')->getBody();
        $this->assertStringContainsString('data-slide-hero', $default);
        $this->assertStringContainsString('hero/hero-beranda', $default);

        setting('School.heroSlide2Path', BrandingAssets::storeFile(SUPPORTPATH . 'Branding/pack/share.png', 'heroSlide2'));

        $body = $this->get('/')->getBody();
        $this->assertStringContainsString('data-slide-hero', $body);
        $this->assertStringContainsString(site_url('aset/slide-2'), $body);
        $this->assertStringNotContainsString('/hero/hero-', $body);
        $this->assertSame(1, substr_count($body, 'class="slide-hero-item'));
    }

    public function testAddressWithoutEmbedStillShowsAMapPreview(): void
    {
        setting('School.mapEmbed', '');
        setting('School.address', "Jl. Contoh No. 1\nKudus");

        $body = $this->get('/')->getBody();

        // D-091: a preview map of the address, not only the directions link.
        $this->assertStringContainsString('<iframe src="https://maps.google.com/maps?q=', $body);
        $this->assertStringContainsString('Jl.%20Contoh%20No.%201%20Kudus', $body);
        $this->assertStringContainsString('output=embed', $body);
        $this->assertStringContainsString(lang('Situs.homeDirections'), $body);
    }

    public function testFooterLinksAreOnlyStatusQuestionsAndGuide(): void
    {
        $body = $this->get('/')->getBody();

        $this->assertSame(1, preg_match('#<h2[^>]*>' . preg_quote(lang('App.footerLinks'), '#') . '</h2>\s*<ul[^>]*>(.*?)</ul>#s', $body, $list));
        preg_match_all('#<a [^>]*>([^<]+)</a>#', $list[1], $labels);
        // Tanya Jawab joins only when the school has published questions (none in this test).
        $this->assertSame(['Cek Status', 'Panduan'], array_map('trim', $labels[1]));
    }

    public function testPublicPagesUseTheOfficialNameAndCompactLayout(): void
    {
        $body = $this->get('/')->getBody();

        // D-093: the default tagline is the official name.
        $this->assertStringContainsString('Sistem Penerimaan Murid Baru (SPMB)', $body);
        // D-092: the green top bar is for larger screens only.
        $this->assertStringContainsString('class="bar-atas hidden', $body);
        // D-094: the HTML is sent without indentation.
        $this->assertDoesNotMatchRegularExpression('/\n[ \t]+</', $body);
    }
}
