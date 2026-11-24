<?php

declare(strict_types=1);

use App\Libraries\Locales;
use App\Models\UserModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\School;
use Config\Services;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Situs\Models\SiteNewsModel;
use Modules\Situs\Models\SiteQuestionModel;
use Tests\Support\Pendaftaran\PendaftaranFixtures;

/**
 * Public website in Indonesian, English (/en/…) and Arabic (/ar/…) (D-109): address prefixes,
 * lang/dir, hreflang and switcher links, staff and file addresses, forms under a prefix, fallbacks,
 * the school's translations, switching a language off, dates and the remembered language.
 *
 * @internal
 */
final class BahasaWebsiteTest extends CIUnitTestCase
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
        $_SESSION = [];
        $this->resetVisitor();
        service('throttler')->remove('daftar-' . md5('0.0.0.0'));
        Time::setTestNow('2027-01-10 09:00:00', 'Asia/Jakarta');
        service('routes')->loadRoutes();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        $this->forgetPendaftaranSettings();

        foreach (['School.languageEn', 'School.languageAr', 'School.motto', 'School.motto_en', 'School.tagline', 'School.tagline_ar', 'Situs.newsEnabled'] as $key) {
            setting()->forget($key);
        }

        service('superglobals')->setCookieArray([]);
        service('superglobals')->setServer('HTTP_REFERER', '');
        $this->resetVisitor();
        parent::tearDown();
    }

    public function testPrefixesRouteTheSamePagesInEnglishAndArabic(): void
    {
        $en = $this->get('en/persyaratan');
        $en->assertOK();
        $this->assertStringContainsString('<html lang="en" dir="ltr"', (string) $en->getBody());
        $en->assertSee('Admission requirements');
        $this->assertStringContainsString(base_url('en/jadwal'), (string) $en->getBody());

        $ar = $this->get('ar/jadwal');
        $ar->assertOK();
        $this->assertStringContainsString('<html lang="ar" dir="rtl"', (string) $ar->getBody());
        $ar->assertSee('جدول التسجيل');
        // The Arabic font is preloaded only on Arabic pages.
        $this->assertStringNotContainsString('noto-sans-arabic', $en->getBody());

        $id = $this->get('persyaratan');
        $this->assertStringContainsString('<html lang="id" dir="ltr"', (string) $id->getBody());
        $id->assertSee('Persyaratan Pendaftaran');
    }

    public function testHomeHasHreflangLinksAndTheSwitcher(): void
    {
        $result = $this->get('en/');

        $result->assertOK();
        $this->assertStringContainsString('<link rel="alternate" hreflang="id" href="' . base_url('/') . '">', (string) $result->getBody());
        $this->assertStringContainsString('<link rel="alternate" hreflang="en" href="' . base_url('en/') . '">', (string) $result->getBody());
        $this->assertStringContainsString('<link rel="alternate" hreflang="ar" href="' . base_url('ar/') . '">', (string) $result->getBody());
        $this->assertStringContainsString('<link rel="alternate" hreflang="x-default" href="' . base_url('/') . '">', (string) $result->getBody());
        $this->assertStringContainsString('<meta property="og:locale" content="en_US">', (string) $result->getBody());
        // Switcher: links to the same page in the other languages, the current one marked.
        $this->assertStringContainsString('data-bahasa="ar"', (string) $result->getBody());
        $this->assertStringContainsString('aria-current="true" lang="en"', (string) $result->getBody());
        $result->assertSee('New Student Admission System (SPMB)');
        // Module panels in the footer follow the language too (Analitik visitor line).
        if ((bool) setting('Analitik.showInFooter') && service('modules')->isEnabled('Analitik')) {
            $result->assertSee('Visitors today');
        }
    }

    public function testSwitcherKeepsThePathAndQuery(): void
    {
        $result = $this->get('ar/tanya-jawab?q=biaya');

        $body = html_entity_decode((string) $result->getBody(), ENT_QUOTES | ENT_HTML5);
        $this->assertStringContainsString('href="' . base_url('en/tanya-jawab') . '?q=biaya"', $body);
        $this->assertStringContainsString('href="' . base_url('tanya-jawab') . '?q=biaya"', $body);
    }

    public function testStaffAndFileAddressesNeverCarryAPrefix(): void
    {
        $this->get('en/admin')->assertRedirectTo(base_url('admin'));
        $this->get('ar/masuk')->assertRedirectTo(base_url('masuk'));
        $this->get('en/aset/logo')->assertRedirectTo(base_url('aset/logo'));

        $page = $this->get('en/');
        $body = $page->getBody();

        // Built assets and the sign-in link stay unprefixed; page links get the prefix.
        $this->assertStringContainsString(base_url('build/app.css'), $body);
        $this->assertStringNotContainsString('/en/build/', $body);
        $this->assertStringContainsString(base_url('masuk'), $body);
        $this->assertStringNotContainsString('/en/masuk', $body);
        $this->assertStringContainsString(base_url('en/cek-status'), $body);
    }

    public function testSwitchedOffLanguageRedirectsAndHides(): void
    {
        setting('School.languageAr', false);

        $this->get('ar/jadwal')->assertRedirectTo(base_url('jadwal'));

        $result = $this->get('en/jadwal');
        $result->assertOK();
        $this->assertStringNotContainsString('hreflang="ar"', (string) $result->getBody());
        $this->assertStringNotContainsString('data-bahasa="ar"', (string) $result->getBody());

        setting('School.languageEn', false);
        $id = $this->get('jadwal');
        // Only Indonesian left: no switcher and no alternates.
        $this->assertStringNotContainsString('data-bahasa-pilih', (string) $id->getBody());
        $this->assertStringNotContainsString('hreflang=', (string) $id->getBody());
    }

    public function testRegistrationFormWorksUnderAPrefix(): void
    {
        $this->makeWave(['starts_on' => '2026-01-01', 'ends_on' => '2027-12-31']);

        $form = $this->get('en/daftar');
        $form->assertOK();
        $form->assertSee('New student registration form');
        $this->assertStringContainsString('action="' . base_url('en/daftar') . '"', (string) $form->getBody());
        $form->assertSee('Check and send');

        $this->post('en/daftar', $this->applicantInput())->assertRedirectTo(base_url('en/daftar/selesai'));
        $this->assertSame(1, model(ApplicantModel::class)->countAllResults());

        $done = $this->withSession($_SESSION)->get('en/daftar/selesai');
        $done->assertSee('Registration complete');
        // The private link opens in the same language.
        $this->assertStringContainsString(base_url('en/tautan/'), (string) $done->getBody());
    }

    public function testValidationMessagesFollowTheLanguage(): void
    {
        $this->makeWave(['starts_on' => '2026-01-01', 'ends_on' => '2027-12-31']);

        $result = $this->post('ar/daftar', $this->applicantInput(['nisn' => '12', 'agree' => '0']));
        $result->assertSessionHas('errors');

        $errors = $_SESSION['errors'];
        $this->assertSame('يجب أن يتكوّن الرقم الوطني للطالب (NISN) من 10 رقمًا.', $errors['nisn']);
        $this->assertSame(lang('Pendaftaran.agreeRequired', [], 'ar'), $errors['agree']);
    }

    public function testMissingTranslationsFallBackToIndonesian(): void
    {
        // A staff string that has no English version.
        $this->assertSame('Tanya jawab', lang('Situs.qaAdminTitle', [], 'en'));
        // A list translated in part keeps the Indonesian entries it lacks.
        $alts = lang('App.altStates', [], 'en');
        $this->assertSame('An empty question box.', $alts['state/kosong-tanya-jawab']);
        $this->assertSame('Keranjang berisi tali kartu petugas.', $alts['state/kosong-pengguna']);
        // English system messages still come from the framework.
        $this->assertStringContainsString('{field}', lang('Validation.required', [], 'en'));
    }

    public function testSchoolTranslationsShowAndEmptyOnesFallBack(): void
    {
        $this->makeWave(['name' => 'Gelombang 1', 'name_en' => 'Round 1', 'name_ar' => '', 'starts_on' => '2026-01-01', 'ends_on' => '2027-12-31']);
        setting('School.motto', 'Berakhlak dan berilmu');
        setting('School.motto_en', 'Good character and knowledge');

        $en = $this->get('en/jadwal');
        $en->assertSee('Round 1');
        $en->assertSee('Good character and knowledge');

        $ar = $this->get('ar/jadwal');
        $ar->assertSee('Gelombang 1');
        $ar->assertSee('Berakhlak dan berilmu');

        model(SiteQuestionModel::class)->insert(['topic' => 'Biaya', 'question' => 'Apakah gratis?', 'answer' => 'Ya, gratis.', 'question_ar' => 'هل التسجيل مجاني؟', 'answer_ar' => 'نعم، مجاني.', 'topic_ar' => 'الرسوم', 'sort_order' => 1, 'is_published' => 1]);
        $qa = $this->get('ar/tanya-jawab');
        $qa->assertSee('هل التسجيل مجاني؟');
        $qa->assertSee('الرسوم');
        $this->get('en/tanya-jawab')->assertSee('Apakah gratis?');

        setting('Situs.newsEnabled', true);
        model(SiteNewsModel::class)->insert(['text' => 'Tes bulan Januari', 'text_en' => 'Test in January', 'url' => '', 'is_active' => 1, 'sort_order' => 1]);
        $this->get('en/')->assertSee('Test in January');
        $this->get('/')->assertSee('Tes bulan Januari');

        model(DocumentTypeModel::class)->insert(['code' => 'kk-uji', 'name' => 'Kartu Keluarga', 'name_en' => 'English name of the document', 'is_required' => 1, 'may_follow' => 0, 'max_size_kb' => 1024, 'extensions' => 'pdf', 'sort_order' => 1, 'is_active' => 1]);
        $this->get('en/persyaratan')->assertSee('English name of the document');
    }

    public function testDefaultSloganIsTranslated(): void
    {
        setting('School.tagline', (new School())->tagline);

        $this->get('ar/')->assertSee('نظام قبول الطلاب الجدد (SPMB)');

        setting('School.tagline_ar', 'شعار المدرسة');
        $this->get('ar/')->assertSee('شعار المدرسة');
    }

    public function testDatesFollowTheLanguageWithWesternDigits(): void
    {
        $request = service('incomingrequest', null, false);
        Services::injectMock('request', $request);
        Locales::apply($request, 'id');

        $this->assertSame('10 Januari 2027', tanggal_indo('2027-01-10'));
        $this->assertSame('10 January 2027', Locales::withLocale('en', static fn (): string => tanggal_indo('2027-01-10')));
        $this->assertSame('10 يناير 2027', Locales::withLocale('ar', static fn (): string => tanggal_indo('2027-01-10')));
        // Intl dates keep Western digits too (registration state compares them as strings).
        $this->assertSame('2027-01-10', Locales::withLocale('ar', static fn (): string => Time::today()->toDateString()));
        $this->assertSame('id', locale_current());
    }

    public function testNotFoundPageUsesTheLanguage(): void
    {
        $result = $this->get('en/halaman-yang-tidak-ada');

        $result->assertStatus(404);
        $result->assertSee('Page not found');
        $this->assertStringContainsString('<html lang="en" dir="ltr"', (string) $result->getBody());
    }

    public function testParentGuideIsTranslated(): void
    {
        $result = $this->get('en/panduan/calon-murid');

        $result->assertOK();
        $result->assertSee('Registering online');
        $this->assertStringContainsString('href="' . base_url('en/daftar') . '"', (string) $result->getBody());

        $this->get('ar/panduan/calon-murid')->assertSee('التسجيل عبر الإنترنت');
        $this->get('panduan/calon-murid')->assertSee('Mendaftar online');
    }

    public function testLanguageIsRememberedAndOfferedOnIndonesianEntryPages(): void
    {
        $this->get('en/jadwal')->assertCookie(Locales::COOKIE, 'en');

        service('superglobals')->setCookie(Locales::COOKIE, 'en');
        $entry = $this->get('jadwal');
        $entry->assertOK();
        $this->assertStringContainsString('data-bahasa-saran', (string) $entry->getBody());
        $entry->assertSee('Continue in English');
        // No redirect: the Indonesian address keeps showing Indonesian.
        $entry->assertSee('Jadwal Pendaftaran');

        // Moving inside the site (e.g. pressing "ID") changes the remembered language.
        service('superglobals')->setServer('HTTP_REFERER', base_url('en/jadwal'));
        $inside = $this->withHeaders(['Referer' => base_url('en/jadwal')])->get('jadwal');
        $this->assertStringNotContainsString('data-bahasa-saran', (string) $inside->getBody());
        $inside->assertCookie(Locales::COOKIE, 'id');
    }

    public function testAdminSavesTranslationsOfSchoolTexts(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('admin/tanya-jawab/tambah', [
            'topic'        => 'Biaya',
            'question'     => 'Apakah gratis?',
            'answer'       => 'Ya.',
            'is_published' => '1',
            'question_en'  => 'Is it free?',
            'question_ar'  => '',
            'answer_en'    => 'Yes.',
        ])->assertRedirect();

        $row = model(SiteQuestionModel::class)->where('question', 'Apakah gratis?')->first();
        $this->assertSame('Is it free?', $row['question_en']);
        $this->assertNull($row['question_ar']);

        $form = $this->actingAs($admin)->get('admin/tanya-jawab/' . $row['id'] . '/ubah');
        $form->assertSee('Terjemahan (tidak wajib)');
        $this->assertStringContainsString('name="question_ar"', (string) $form->getBody());
    }

    public function testAdminSwitchesALanguageOff(): void
    {
        $admin = $this->makeAdmin();
        $form  = $this->actingAs($admin)->get('admin/pengaturan/sekolah');
        $form->assertSee('Bahasa website');

        $this->actingAs($admin)->post('admin/pengaturan/sekolah', [
            'name'          => 'SMP Contoh',
            'shortName'     => 'SMP Contoh',
            'primaryColor'  => '#2563eb',
            'timezoneLabel' => 'WIB',
            'languageEn'    => '1',
            'languageAr'    => '0',
            'motto_en'      => 'Good character',
        ])->assertRedirect();

        $this->assertTrue((bool) setting('School.languageEn'));
        $this->assertFalse((bool) setting('School.languageAr'));
        $this->assertSame('Good character', setting('School.motto_en'));
        $this->assertSame(['id', 'en'], Locales::enabled());
    }

    private function makeAdmin(): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => 'adminbahasa', 'email' => 'adminbahasa@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => 'Admin Bahasa']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup('admin');
        $user->activate();

        return $user;
    }
}
