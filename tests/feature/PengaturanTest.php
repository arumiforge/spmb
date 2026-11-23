<?php

declare(strict_types=1);

use App\Libraries\BrandingAssets;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * School identity and security settings.
 *
 * @internal
 */
final class PengaturanTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function tearDown(): void
    {
        foreach (['name', 'shortName', 'primaryColor', 'timezoneLabel', 'email', 'motto', 'mapEmbed', 'socialInstagram', 'socialFacebook', 'socialYoutube', 'socialTiktok'] as $key) {
            setting()->forget('School.' . $key);
        }

        foreach (BrandingAssets::HERO_SLIDES as $key) {
            BrandingAssets::remove(BrandingAssets::file($key));
            setting()->forget('School.' . BrandingAssets::ASSETS[$key]['setting']);
        }

        $_FILES = [];
        service('superglobals')->setFilesArray([]);

        setting()->forget('TwoFactor.requiredRoles');
        parent::tearDown();
    }

    private function makeUser(string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'u' . $group, 'email' => $group . '@example.test', 'password' => 'Rahasia-Kuat-2027']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);

        return $user;
    }

    private function validPost(array $overrides = []): array
    {
        return $overrides + [
            'name'          => 'SMP Contoh Nusantara',
            'shortName'     => 'SMP Contoh',
            'tagline'       => 'Penerimaan Murid Baru 2027/2028',
            'primaryColor'  => '#0E7490',
            'timezoneLabel' => 'WIB',
            'email'         => 'info@contoh.sch.id',
        ];
    }

    public function testAdminUpdatesSchoolIdentity(): void
    {
        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost());

        $result->assertRedirectTo(site_url('admin/pengaturan/sekolah'));
        $this->assertSame('SMP Contoh Nusantara', setting('School.name'));
        $this->assertSame('#0e7490', setting('School.primaryColor'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_id' => 'School']);
    }

    public function testBrandColourAppearsInLayout(): void
    {
        setting('School.primaryColor', '#0e7490');

        $body = $this->get('/')->getBody();
        $this->assertStringContainsString('--brand:#0e7490', $body);
        $this->assertStringContainsString('assets/img/hero/hero-beranda.webp', $body);
    }

    public function testInvalidColourIsRejected(): void
    {
        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost(['primaryColor' => 'red;}body{']));

        $result->assertSessionHas('errors');
        $this->assertNotSame('red;}body{', setting('School.primaryColor'));
    }

    public function testStaffWithoutPermissionIsTurnedAway(): void
    {
        $this->actingAs($this->makeUser('panitia'))->get('admin/pengaturan/sekolah')->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testOnlySuperAdminSetsTwoFactorPolicy(): void
    {
        $this->actingAs($this->makeUser('admin'))->get('admin/pengaturan/keamanan')->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testSuperAdminRequiresTwoFactorForRoles(): void
    {
        $super = $this->makeUser('superadmin');
        $this->actingAs($super)->get('admin/pengaturan/keamanan')->assertOK();

        $this->actingAs($super)->post('admin/pengaturan/keamanan', ['roles' => ['admin', 'panitia', 'tidak-ada']])->assertRedirect();

        $this->assertSame(['admin', 'panitia'], setting('TwoFactor.requiredRoles'));
    }

    public function testAdminSavesSloganMapAndSocialLinks(): void
    {
        $iframe = '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!3m3!1m2!1sSMP%20NU%20Al%20Ma\'ruf" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';

        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost([
            'motto'           => 'Beriman, Cerdas, Terampil, Mandiri',
            'mapEmbed'        => $iframe,
            'socialInstagram' => 'https://instagram.com/smpcontoh',
            'socialTiktok'    => 'https://www.tiktok.com/@smpcontoh',
        ]));

        $result->assertRedirectTo(site_url('admin/pengaturan/sekolah'));
        $this->assertSame('Beriman, Cerdas, Terampil, Mandiri', setting('School.motto'));
        // Only the embed URL is kept, never the pasted HTML (D-085).
        $this->assertSame('https://www.google.com/maps/embed?pb=!1m18!1m12!3m3!1m2!1sSMP%20NU%20Al%20Ma%27ruf', setting('School.mapEmbed'));
        $this->assertSame('https://instagram.com/smpcontoh', setting('School.socialInstagram'));
        $this->assertSame('https://www.tiktok.com/@smpcontoh', setting('School.socialTiktok'));
        $this->assertSame('', (string) setting('School.socialFacebook'));
    }

    public function testMapFromAnotherHostIsRejected(): void
    {
        setting('School.mapEmbed', 'https://www.google.com/maps/embed?pb=lama');
        $admin = $this->makeUser('admin');

        foreach (['<iframe src="https://evil.example/maps/embed?pb=1"></iframe>', 'https://maps.google.com.evil.example/embed?pb=1', 'javascript:alert(1)'] as $input) {
            $result = $this->actingAs($admin)->post('admin/pengaturan/sekolah', $this->validPost(['mapEmbed' => $input]));

            $result->assertSessionHas('errors');
            $this->assertSame('https://www.google.com/maps/embed?pb=lama', setting('School.mapEmbed'));
            $_SESSION = [];
        }
    }

    public function testSocialLinkMustBeAWebAddress(): void
    {
        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost(['socialFacebook' => 'javascript:alert(1)']));

        $result->assertSessionHas('errors');
        $this->assertSame('', (string) setting('School.socialFacebook'));
    }

    public function testSlideThatIsNotAPictureIsRejected(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'slide-test');
        copy(SUPPORTPATH . 'Branding/pack/notes.txt', $tmp);
        $_FILES = ['heroSlide1' => ['name' => 'slide.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)]];
        service('superglobals')->setFilesArray($_FILES);

        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost());

        $result->assertSessionHas('errors');
        $this->assertSame('', BrandingAssets::file('heroSlide1'));
        @unlink($tmp);
    }

    public function testAdminRemovesAnUploadedSlide(): void
    {
        $stored = BrandingAssets::storeFile(SUPPORTPATH . 'Branding/pack/share.png', 'heroSlide3');
        setting('School.heroSlide3Path', $stored);
        $this->assertCount(1, BrandingAssets::heroSlides());
        $this->assertStringStartsWith(site_url('aset/slide-3') . '?v=', BrandingAssets::heroSlides()[0]);

        $result = $this->actingAs($this->makeUser('admin'))->post('admin/pengaturan/sekolah', $this->validPost(['remove_heroSlide3' => '1']));

        $result->assertRedirectTo(site_url('admin/pengaturan/sekolah'));
        $this->assertSame('', BrandingAssets::file('heroSlide3'));
        $this->assertFileDoesNotExist(BrandingAssets::DIR . $stored);
        $this->assertSame([], BrandingAssets::heroSlides());
    }
}
