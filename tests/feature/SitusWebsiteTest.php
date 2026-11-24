<?php

declare(strict_types=1);

use App\Libraries\ModuleRegistry;
use App\Models\UserModel;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Modules\Situs\Libraries\FooterLinks;
use Modules\Situs\Libraries\PopupFiles;
use Modules\Situs\Libraries\SiteLink;
use Modules\Situs\Models\SiteNewsModel;
use Modules\Situs\Models\SiteQuestionModel;

/**
 * Website extras of the Situs module: news ticker (admin/berita-singkat), home page
 * popup (admin/popup) and the editable footer links (admin/tautan-footer), plus the
 * social media icons in the public footer.
 *
 * @internal
 */
final class SitusWebsiteTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const SITUS_KEYS = ['newsEnabled', 'newsInterval', 'popupEnabled', 'popupTitle', 'popupText', 'popupImage', 'popupAttachment', 'popupAttachmentName',
        'popupButtonLabel', 'popupButtonUrl', 'popupStartsOn', 'popupEndsOn', 'popupFrequency', 'popupVersion', 'footerLinks'];

    protected $namespace;
    protected $refresh = true;
    private SiteNewsModel $news;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        Time::setTestNow('2027-01-10 09:00:00', 'Asia/Jakarta');
        Services::resetSingle('auth');
        Services::resetSingle('audit');
        service('routes')->loadRoutes();
        $this->news = model(SiteNewsModel::class, false);
    }

    protected function tearDown(): void
    {
        PopupFiles::remove((string) setting('Situs.popupImage'));
        PopupFiles::remove((string) setting('Situs.popupAttachment'));

        foreach (self::SITUS_KEYS as $key) {
            setting()->forget('Situs.' . $key);
        }

        foreach (['socialInstagram', 'socialFacebook', 'socialYoutube', 'socialTiktok', 'whatsapp'] as $key) {
            setting()->forget('School.' . $key);
        }

        $_FILES = [];
        service('superglobals')->setFilesArray([]);
        Time::setTestNow();
        // A fresh route collection needs a fresh router too (the shared router keeps its collection).
        Services::resetSingle('modules');
        Services::resetSingle('routes');
        Services::resetSingle('router');
        parent::tearDown();
    }

    private function makeUser(string $username, string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => $username, 'email' => $username . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => ucfirst($username) . ' Test']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);
        $user->activate();

        return $user;
    }

    private function admin(): User
    {
        return $this->makeUser('admin1', 'admin');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function addNews(array $row): int
    {
        return (int) $this->news->insert($row + ['url' => '', 'is_active' => 1, 'sort_order' => $this->news->nextSortOrder()]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function popupPost(array $overrides = []): array
    {
        return $overrides + [
            'enabled'     => '1',
            'title'       => 'Pendaftaran sudah dibuka',
            'text'        => "Baris pertama\nBaris kedua",
            'buttonLabel' => 'Daftar sekarang',
            'buttonUrl'   => '/daftar',
            'startsOn'    => '',
            'endsOn'      => '',
            'frequency'   => 'once',
        ];
    }

    private function fakeUpload(string $field, string $source, string $name, string $type): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'situs-test');
        copy($source, $tmp);
        $_FILES[$field] = ['name' => $name, 'type' => $type, 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => filesize($tmp)];
        service('superglobals')->setFilesArray($_FILES);
    }

    // ---------- Access ----------

    public function testAdminPagesNeedThePermission(): void
    {
        foreach (['admin/berita-singkat', 'admin/popup', 'admin/tautan-footer'] as $url) {
            $this->get($url)->assertRedirectTo(site_url('masuk'));
        }

        $panitia = $this->makeUser('panitia1', 'panitia');

        foreach (['admin/berita-singkat', 'admin/berita-singkat/tambah', 'admin/popup', 'admin/tautan-footer', 'admin/tautan-footer/tambah'] as $url) {
            $this->actingAs($panitia)->get($url)->assertRedirectTo(site_url('admin/akses-ditolak'));
        }

        $this->actingAs($panitia)->post('admin/popup', $this->popupPost())->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->post('admin/tautan-footer/kembalikan')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->assertFalse((bool) setting('Situs.popupEnabled'));

        $admin = $this->admin();

        foreach (['admin/berita-singkat' => 'Berita singkat', 'admin/popup' => 'Popup beranda', 'admin/tautan-footer' => 'Tautan bawah website'] as $url => $title) {
            $page = $this->actingAs($admin)->get($url);
            $page->assertOK();
            $page->assertSee($title);
            $page->assertSee('aria-label="Breadcrumb"');
        }

        // The sidebar lists the three pages in the Website group.
        $page = $this->actingAs($admin)->get('admin/tanya-jawab');
        $page->assertSee(site_url('admin/berita-singkat'));
        $page->assertSee(site_url('admin/popup'));
        $page->assertSee(site_url('admin/tautan-footer'));
    }

    // ---------- News ticker ----------

    public function testTickerIsOffByDefault(): void
    {
        $this->addNews(['text' => 'Pendaftaran dibuka']);

        $result = $this->get('jadwal');

        $result->assertOK();
        $result->assertDontSee('data-berita');
        $result->assertDontSee('Pendaftaran dibuka');
    }

    public function testTickerIsHiddenWithoutActiveItems(): void
    {
        setting('Situs.newsEnabled', true);
        $this->addNews(['text' => 'Berita mati', 'is_active' => 0]);
        $this->addNews(['text' => 'Berita lewat', 'ends_on' => '2027-01-09']);

        $result = $this->get('jadwal');

        $result->assertDontSee('data-berita');
        $result->assertDontSee('Berita mati');
    }

    public function testTickerShowsActiveItemsInsideTheirDatesInOrder(): void
    {
        setting('Situs.newsEnabled', true);
        setting('Situs.newsInterval', 7);
        $first  = $this->addNews(['text' => 'Berita pertama', 'url' => '/jadwal']);
        $second = $this->addNews(['text' => 'Berita kedua', 'starts_on' => '2027-01-10', 'ends_on' => '2027-01-10']);
        $this->addNews(['text' => 'Berita nanti', 'starts_on' => '2027-01-11']);
        $this->addNews(['text' => 'Berita lalu', 'ends_on' => '2027-01-09']);
        $this->addNews(['text' => 'Berita disembunyikan', 'is_active' => 0]);
        $this->addNews(['text' => 'Berita luar', 'url' => 'https://example.org/brosur']);

        $result = $this->get('persyaratan');

        $result->assertSee('data-berita-jeda="7000"');
        $result->assertSee('Berita pertama');
        $result->assertSee('Berita kedua');
        $result->assertSee('Berita luar');
        $result->assertDontSee('Berita nanti');
        $result->assertDontSee('Berita lalu');
        $result->assertDontSee('Berita disembunyikan');
        $result->assertSee('href="' . site_url('jadwal') . '"');
        $result->assertSee('href="https://example.org/brosur" target="_blank"');
        $result->assertSee('aria-live="off"');
        $result->assertSee('data-berita-jeda-tombol');

        $body = (string) $result->getBody();
        $this->assertLessThan(strpos($body, 'Berita kedua'), strpos($body, 'Berita pertama'));

        // Moving the second item up changes the public order.
        $this->actingAs($this->admin())->post("admin/berita-singkat/{$second}/pindah", ['arah' => 'naik'])->assertRedirect();
        $this->assertSame([$second, $first], array_slice(array_map('intval', array_column($this->news->ordered(), 'id')), 0, 2));

        $body = (string) $this->get('persyaratan')->getBody();
        $this->assertLessThan(strpos($body, 'Berita pertama'), strpos($body, 'Berita kedua'));
    }

    public function testTickerWithOneItemHasNoControls(): void
    {
        setting('Situs.newsEnabled', true);
        $this->addNews(['text' => 'Satu-satunya berita']);

        $result = $this->get('/');

        $result->assertSee('Satu-satunya berita');
        $result->assertDontSee('data-berita-kontrol');
    }

    public function testAdminManagesNewsItems(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('admin/berita-singkat/tambah', ['text' => 'Tes seleksi tanggal 7 Februari', 'url' => 'jadwal', 'starts_on' => '2027-01-05', 'ends_on' => '2027-02-07', 'is_active' => '1'])
            ->assertRedirectTo(site_url('admin/berita-singkat'));
        $row = $this->news->where('text', 'Tes seleksi tanggal 7 Februari')->first();
        $this->assertNotNull($row);
        $this->assertSame('/jadwal', $row['url']);
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'site_news', 'subject_id' => (string) $row['id']]);

        // Unsafe links, text over 160 characters and reversed dates are refused.
        $this->actingAs($admin)->post('admin/berita-singkat/tambah', ['text' => 'Klik', 'url' => 'javascript:alert(1)', 'is_active' => '1'])->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/berita-singkat/tambah', ['text' => str_repeat('a', 161), 'is_active' => '1'])->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/berita-singkat/tambah', ['text' => 'Tanggal terbalik', 'starts_on' => '2027-02-01', 'ends_on' => '2027-01-01', 'is_active' => '1'])->assertSessionHas('errors');
        $this->assertSame(1, $this->news->countAllResults());

        $id = (int) $row['id'];
        $this->actingAs($admin)->post("admin/berita-singkat/{$id}/ubah", ['text' => 'Tes seleksi diundur', 'url' => '', 'starts_on' => '', 'ends_on' => '', 'is_active' => '1'])->assertRedirect();
        $this->seeInDatabase('site_news', ['id' => $id, 'text' => 'Tes seleksi diundur', 'url' => '', 'starts_on' => null]);

        $this->actingAs($admin)->post("admin/berita-singkat/{$id}/aktifkan", ['aktif' => '0'])->assertRedirect();
        $this->seeInDatabase('site_news', ['id' => $id, 'is_active' => 0]);

        $this->actingAs($admin)->post('admin/berita-singkat/pengaturan', ['enabled' => '1', 'interval' => '8'])->assertRedirectTo(site_url('admin/berita-singkat'));
        $this->assertTrue((bool) setting('Situs.newsEnabled'));
        $this->assertSame(8, (int) setting('Situs.newsInterval'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'News']);
        $this->actingAs($admin)->post('admin/berita-singkat/pengaturan', ['enabled' => '1', 'interval' => '99'])->assertSessionHas('errors');
        $this->assertSame(8, (int) setting('Situs.newsInterval'));

        $this->actingAs($admin)->post("admin/berita-singkat/{$id}/hapus")->assertRedirect();
        $this->assertNull($this->news->find($id));
        $this->seeInDatabase('audit_logs', ['event' => 'deleted', 'subject_type' => 'site_news', 'subject_id' => (string) $id]);
    }

    // ---------- Popup ----------

    public function testPopupShowsOnlyOnTheHomePage(): void
    {
        setting('Situs.popupEnabled', true);
        setting('Situs.popupTitle', 'Pengumuman penting');
        setting('Situs.popupText', "Baris satu\nBaris dua");
        setting('Situs.popupVersion', 3);
        setting('Situs.popupFrequency', 'daily');

        $home = $this->get('/');
        $home->assertOK();
        $home->assertSee('data-popup-versi="3"');
        $home->assertSee('data-popup-frekuensi="daily"');
        $home->assertSee('Pengumuman penting');
        $home->assertSee('Baris satu<br>');

        foreach (['tanya-jawab', 'jadwal', 'persyaratan'] as $url) {
            $page = $this->get($url);
            $page->assertOK();
            $page->assertDontSee('data-popup');
            $page->assertDontSee('Pengumuman penting');
        }
    }

    public function testPopupIsOffByDefaultAndRespectsItsDates(): void
    {
        $this->get('/')->assertDontSee('data-popup');

        setting('Situs.popupEnabled', true);
        setting('Situs.popupTitle', 'Pengumuman penting');
        setting('Situs.popupStartsOn', '2027-01-11');
        $this->get('/')->assertDontSee('data-popup');

        setting('Situs.popupStartsOn', '2027-01-01');
        setting('Situs.popupEndsOn', '2027-01-09');
        $this->get('/')->assertDontSee('data-popup');

        setting('Situs.popupEndsOn', '2027-01-10');
        $this->get('/')->assertSee('data-popup');
    }

    public function testAdminPreviewShowsThePopupWhileItIsOff(): void
    {
        setting('Situs.popupTitle', 'Belum dinyalakan');

        $this->get('/?popup=pratinjau')->assertDontSee('data-popup');

        $result = $this->actingAs($this->admin())->get('/?popup=pratinjau');
        $result->assertSee('data-popup-pratinjau');
        $result->assertSee('Belum dinyalakan');
    }

    public function testEverySaveRaisesTheVersion(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('admin/popup', $this->popupPost())->assertRedirectTo(site_url('admin/popup'));
        $this->assertSame(1, (int) setting('Situs.popupVersion'));
        $this->assertTrue((bool) setting('Situs.popupEnabled'));
        $this->assertSame('/daftar', setting('Situs.popupButtonUrl'));

        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['title' => 'Judul baru', 'frequency' => 'always']))->assertRedirect();
        $this->assertSame(2, (int) setting('Situs.popupVersion'));
        $this->assertSame('always', setting('Situs.popupFrequency'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'Popup']);

        $home = $this->get('/');
        $home->assertSee('data-popup-versi="2"');
        $home->assertSee('Judul baru');
        $home->assertSee('href="' . site_url('daftar') . '"');
    }

    public function testPopupFormChecks(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['title' => '']))->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['buttonUrl' => 'javascript:alert(1)']))->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['buttonUrl' => '']))->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['startsOn' => '2027-02-01', 'endsOn' => '2027-01-01']))->assertSessionHas('errors');
        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['frequency' => 'sometimes']))->assertSessionHas('errors');
        $this->assertSame(0, (int) setting('Situs.popupVersion'));

        // Switched off, the title may stay empty.
        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['enabled' => '0', 'title' => '', 'buttonLabel' => '', 'buttonUrl' => '']))->assertRedirectTo(site_url('admin/popup'));
        $this->assertSame(1, (int) setting('Situs.popupVersion'));
    }

    public function testPopupImageAndAttachmentAreStoredOutsidePublicAndServed(): void
    {
        $admin = $this->admin();
        $this->fakeUpload('image', SUPPORTPATH . 'Branding/pack/share.png', 'poster.png', 'image/png');
        $this->fakeUpload('attachment', SUPPORTPATH . 'Berkas/sample.pdf', 'Brosur PPDB.pdf', 'application/pdf');

        $this->actingAs($admin)->post('admin/popup', $this->popupPost())->assertRedirectTo(site_url('admin/popup'));

        $image      = (string) setting('Situs.popupImage');
        $attachment = (string) setting('Situs.popupAttachment');
        $this->assertNotNull(PopupFiles::path($image));
        $this->assertNotNull(PopupFiles::path($attachment));
        $this->assertStringStartsWith(WRITEPATH, (string) PopupFiles::path($image));
        $this->assertSame('Brosur PPDB.pdf', setting('Situs.popupAttachmentName'));

        $_FILES = [];
        service('superglobals')->setFilesArray([]);
        $_SESSION = [];
        Services::resetSingle('auth');

        $home = $this->get('/');
        $home->assertSee(site_url('popup/gambar'));
        $home->assertSee('download="Brosur PPDB.pdf"');

        $served = $this->get('popup/gambar');
        $served->assertOK();
        $served->assertHeader('Content-Type', 'image/png');
        $download = $this->get('popup/lampiran')->response();
        $this->assertInstanceOf(DownloadResponse::class, $download);
        $this->assertSame(200, $download->getStatusCode());

        // Off: visitors cannot fetch the files, admins still can (preview).
        setting('Situs.popupEnabled', false);
        $this->get('popup/gambar')->assertStatus(404);
        $this->actingAs($admin)->get('popup/gambar')->assertOK();

        // Removing the image deletes the file.
        $this->actingAs($admin)->post('admin/popup', $this->popupPost(['remove_image' => '1']))->assertRedirect();
        $this->assertSame('', setting('Situs.popupImage'));
        $this->assertNull(PopupFiles::path($image));
    }

    public function testPopupRefusesAFileThatIsNotAPicture(): void
    {
        $this->fakeUpload('image', SUPPORTPATH . 'Branding/pack/notes.txt', 'poster.png', 'image/png');

        $this->actingAs($this->admin())->post('admin/popup', $this->popupPost())->assertSessionHas('errors');
        $this->assertSame('', (string) setting('Situs.popupImage'));
        $this->assertSame(0, (int) setting('Situs.popupVersion'));
    }

    // ---------- Footer ----------

    public function testFooterShowsOnlyTheFilledSocialLinks(): void
    {
        setting('School.socialInstagram', 'https://instagram.com/sekolahcontoh');
        setting('School.socialTiktok', 'javascript:alert(1)');
        setting('School.whatsapp', '0812 3456 7890');

        $footer = $this->footer($this->get('jadwal')->getBody());

        $this->assertStringContainsString('href="https://instagram.com/sekolahcontoh"', $footer);
        $this->assertStringContainsString('aria-label="Instagram"', $footer);
        $this->assertStringContainsString('aria-label="WhatsApp"', $footer);
        $this->assertStringContainsString('https://wa.me/6281234567890', $footer);
        $this->assertStringNotContainsString('aria-label="Facebook"', $footer);
        $this->assertStringNotContainsString('aria-label="YouTube"', $footer);
        $this->assertStringNotContainsString('aria-label="TikTok"', $footer);
    }

    public function testFooterKeepsTheCredit(): void
    {
        $body = (string) $this->get('jadwal')->getBody();

        $this->assertStringContainsString('Developed by : Mr. F', $this->footer($body));
    }

    public function testDefaultFooterLinksFollowModulesAndContent(): void
    {
        $links = $this->footerLinkLabels($this->get('jadwal')->getBody());
        $this->assertSame(['Cek Status', 'Panduan'], $links);

        // The admin list says which link is not shown yet and why.
        $admin = $this->admin();
        $this->actingAs($admin)->get('admin/tautan-footer')->assertSee('Belum tampil');

        model(SiteQuestionModel::class, false)->insert(['topic' => 'Umum', 'question' => 'Kapan dibuka?', 'answer' => 'Januari.', 'sort_order' => 10, 'is_published' => 1]);
        $this->assertSame(['Cek Status', 'Tanya Jawab', 'Panduan'], $this->footerLinkLabels($this->get('jadwal')->getBody()));
        $this->actingAs($admin)->get('admin/tautan-footer')->assertDontSee('Belum tampil');
    }

    public function testAdminEditsFooterLinksAndRestoresTheDefault(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('admin/tautan-footer/tambah', ['label' => 'Website yayasan', 'url' => 'https://example.org', 'active' => '1'])->assertRedirectTo(site_url('admin/tautan-footer'));
        $this->actingAs($admin)->post('admin/tautan-footer/tambah', ['label' => 'Jadwal', 'url' => 'jadwal', 'active' => '1'])->assertRedirect();
        $this->actingAs($admin)->post('admin/tautan-footer/tambah', ['label' => 'Bahaya', 'url' => 'javascript:alert(1)', 'active' => '1'])->assertSessionHas('errors');
        $this->assertTrue(FooterLinks::isCustom());
        $this->assertSame(['/cek-status', '/tanya-jawab', '/panduan', 'https://example.org', '/jadwal'], array_column(FooterLinks::all(), 'url'));
        $this->seeInDatabase('audit_logs', ['event' => 'settings_updated', 'subject_type' => 'settings', 'subject_id' => 'FooterLinks']);

        // Hide Panduan, move Jadwal to the top, rename Cek Status.
        $this->actingAs($admin)->post('admin/tautan-footer/3/tampilkan', ['tampil' => '0'])->assertRedirect();

        foreach ([5, 4, 3, 2] as $position) {
            $this->actingAs($admin)->post("admin/tautan-footer/{$position}/pindah", ['arah' => 'naik'])->assertRedirect();
        }
        $this->actingAs($admin)->post('admin/tautan-footer/2/ubah', ['label' => 'Cek status pendaftaran', 'url' => '/cek-status', 'active' => '1'])->assertRedirect();

        $body = $this->get('persyaratan')->getBody();
        $this->assertSame(['Jadwal', 'Cek status pendaftaran', 'Website yayasan'], $this->footerLinkLabels($body));
        $this->assertStringContainsString('href="https://example.org" target="_blank"', $this->footer($body));

        $this->actingAs($admin)->post('admin/tautan-footer/1/hapus')->assertRedirect();
        $this->assertSame('Cek status pendaftaran', FooterLinks::all()[0]['label']);

        $this->actingAs($admin)->post('admin/tautan-footer/kembalikan')->assertRedirectTo(site_url('admin/tautan-footer'));
        $this->assertFalse(FooterLinks::isCustom());
        $this->assertSame(['Cek Status', 'Panduan'], $this->footerLinkLabels($this->get('persyaratan')->getBody()));
    }

    public function testFooterHidesInternalLinksToSwitchedOffModules(): void
    {
        setting('Situs.footerLinks', json_encode([
            ['label' => 'Daftar online', 'url' => '/daftar', 'active' => true],
            ['label' => 'Cek Status', 'url' => '/cek-status', 'active' => true],
            ['label' => 'Halaman hilang', 'url' => '/tidak-ada', 'active' => true],
            ['label' => 'Panduan', 'url' => '/panduan', 'active' => true],
            ['label' => 'Yayasan', 'url' => 'https://example.org', 'active' => true],
        ]));

        $this->assertSame(['Daftar online', 'Cek Status', 'Panduan', 'Yayasan'], $this->footerLinkLabels($this->get('jadwal')->getBody()));

        Services::injectMock('modules', new ModuleRegistry(APPPATH . 'Modules', config('Features')->core, ['Pendaftaran']));
        Services::resetSingle('routes');
        Services::resetSingle('router');

        $this->assertSame(['Panduan', 'Yayasan'], $this->footerLinkLabels($this->get('/')->getBody()));
    }

    public function testSiteLinkNormalisesAndRefuses(): void
    {
        $this->assertSame('', SiteLink::normalise('  '));
        $this->assertSame('/jadwal', SiteLink::normalise('jadwal'));
        $this->assertSame('/tanya-jawab#biaya', SiteLink::normalise('/tanya-jawab#biaya'));
        $this->assertSame('https://example.org/a?b=1', SiteLink::normalise('https://example.org/a?b=1'));
        $this->assertNull(SiteLink::normalise('javascript:alert(1)'));
        $this->assertNull(SiteLink::normalise('//evil.example'));
        $this->assertNull(SiteLink::normalise('/../../etc'));
        $this->assertNull(SiteLink::normalise('data:text/html,hi'));
        $this->assertSame('cek-status', SiteLink::path('/cek-status?x=1'));
    }

    private function footer(string $body): string
    {
        $start = strpos($body, '<footer');
        $this->assertNotFalse($start);

        return substr($body, (int) $start, (int) strpos($body, '</footer>', (int) $start) - (int) $start);
    }

    /**
     * Labels of the footer's "Tautan" column, in order.
     *
     * @return list<string>
     */
    private function footerLinkLabels(string $body): array
    {
        $footer = $this->footer($body);
        $start  = strpos($footer, '>Tautan</h2>');

        if ($start === false) {
            return [];
        }

        $list = substr($footer, $start, (int) strpos($footer, '</ul>', $start) - $start);
        preg_match_all('#<a [^>]*>\s*([^<]+?)\s*(?:<|$)#', $list, $m);

        return array_map(static fn (string $label): string => html_entity_decode($label), $m[1]);
    }
}
