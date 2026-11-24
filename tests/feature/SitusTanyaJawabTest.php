<?php

declare(strict_types=1);

use App\Commands\Branding;
use App\Libraries\ModuleRegistry;
use App\Models\UserModel;
use CodeIgniter\CLI\Commands;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\StreamFilterTrait;
use Config\Services;
use Modules\Situs\Libraries\AnswerRenderer;
use Modules\Situs\Models\SiteQuestionModel;

/**
 * Tanya Jawab (D-072): admin screen /admin/tanya-jawab, public /tanya-jawab,
 * the public menu link and the starter questions in the branding pack.
 *
 * @internal
 */
final class SitusTanyaJawabTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use StreamFilterTrait;

    private const CLIENT_FILE = ROOTPATH . 'clients/smp-nu-al-maruf-kudus/tanya-jawab.json';

    protected $namespace;
    protected $refresh = true;
    private SiteQuestionModel $questions;
    private ?string $tmpDir = null;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        Services::resetSingle('auth');
        Services::resetSingle('audit');
        service('routes')->loadRoutes();
        $this->questions = model(SiteQuestionModel::class, false);
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir !== null) {
            foreach ([$this->tmpDir . '/branding/branding.json', $this->tmpDir . '/tanya-jawab.json'] as $file) {
                @unlink($file);
            }

            @rmdir($this->tmpDir . '/branding');
            @rmdir($this->tmpDir);
        }

        Services::resetSingle('modules');
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

    /**
     * @return array<string, int> question => id
     */
    private function seed(): array
    {
        $rows = [
            ['topic' => 'Pendaftaran', 'question' => 'Bagaimana cara mendaftar?', 'answer' => "Isi [formulir](/daftar).\n\n- Siapkan berkas.", 'sort_order' => 10, 'is_published' => 1],
            ['topic' => 'Pendaftaran', 'question' => 'Perlu membuat akun?', 'answer' => 'Tidak perlu.', 'sort_order' => 20, 'is_published' => 1],
            ['topic' => 'Biaya', 'question' => 'Berapa biayanya?', 'answer' => 'Hubungi panitia.', 'sort_order' => 30, 'is_published' => 1],
            ['topic' => 'Biaya', 'question' => 'Ada beasiswa?', 'answer' => '[ISI SEKOLAH: jenis beasiswa]', 'sort_order' => 40, 'is_published' => 0],
        ];
        $ids = [];

        foreach ($rows as $row) {
            $ids[$row['question']] = (int) $this->questions->insert($row);
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function orderedQuestions(): array
    {
        return array_column($this->questions->ordered(), 'question');
    }

    public function testPermissions(): void
    {
        $id = $this->seed()['Berapa biayanya?'];

        $this->get('admin/tanya-jawab')->assertRedirectTo(site_url('masuk'));
        $this->post('admin/tanya-jawab/tambah', ['topic' => 'X'])->assertRedirectTo(site_url('masuk'));

        $panitia = $this->makeUser('panitia1', 'panitia');
        $this->actingAs($panitia)->get('admin/tanya-jawab')->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->get("admin/tanya-jawab/{$id}/ubah")->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->post("admin/tanya-jawab/{$id}/hapus")->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->actingAs($panitia)->post("admin/tanya-jawab/{$id}/tampilkan", ['tampil' => '0'])->assertRedirectTo(site_url('admin/akses-ditolak'));
        $this->seeInDatabase('site_questions', ['id' => $id, 'deleted_at' => null, 'is_published' => 1]);

        $page = $this->actingAs($this->makeUser('admin1', 'admin'))->get('admin/tanya-jawab');
        $page->assertOK();
        $page->assertSee('Tanya jawab');
        $page->assertSee('aria-label="Breadcrumb"');
        $page->assertSee('Berapa biayanya?');
        $page->assertSee('Perlu dilengkapi');
        $page->assertSee('1 pertanyaan masih punya bagian [ISI SEKOLAH]');
        $this->assertLessThan(strpos($page->getBody(), 'Biaya</h2>'), strpos($page->getBody(), 'Pendaftaran</h2>'));
    }

    public function testAdminEmptyState(): void
    {
        $page = $this->actingAs($this->makeUser('admin1', 'admin'))->get('admin/tanya-jawab');
        $page->assertOK();
        $page->assertSee('Belum ada pertanyaan');
    }

    public function testAdminAddsAQuestionIntoItsTopicAndItIsAudited(): void
    {
        $this->seed();
        $admin = $this->makeUser('admin1', 'admin');

        $this->actingAs($admin)->get('admin/tanya-jawab/tambah?topik=Biaya')->assertSee('value="Biaya"', null, false);
        $this->actingAs($admin)->post('admin/tanya-jawab/tambah', [
            'topic' => '  pendaftaran ', 'question' => 'Bisa lewat HP?', 'answer' => 'Bisa.', 'is_published' => '1',
        ])->assertRedirectTo(site_url('admin/tanya-jawab'));

        $this->assertSame(['Bagaimana cara mendaftar?', 'Perlu membuat akun?', 'Bisa lewat HP?', 'Berapa biayanya?', 'Ada beasiswa?'], $this->orderedQuestions());
        $row = $this->questions->where('question', 'Bisa lewat HP?')->first();
        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'site_questions', 'subject_id' => (string) $row['id']]);
    }

    public function testValidationAndPlaceholderGuard(): void
    {
        $ids   = $this->seed();
        $admin = $this->makeUser('admin1', 'admin');

        $this->actingAs($admin)->post('admin/tanya-jawab/tambah', ['topic' => '', 'question' => '', 'answer' => '', 'is_published' => '1']);
        $errors = session('errors');
        $this->assertSame('Isi topik pertanyaan.', $errors['topic']);
        $this->assertSame('Isi pertanyaannya.', $errors['question']);
        $this->assertSame('Isi jawabannya.', $errors['answer']);

        $id = $ids['Ada beasiswa?'];
        $this->actingAs($admin)->post("admin/tanya-jawab/{$id}/ubah", ['topic' => 'Biaya', 'question' => 'Ada beasiswa?', 'answer' => '[ISI SEKOLAH: jenis beasiswa]', 'is_published' => '1']);
        $this->assertStringContainsString('[ISI SEKOLAH]', session('errors')['answer']);
        $this->seeInDatabase('site_questions', ['id' => $id, 'is_published' => 0]);

        $this->actingAs($admin)->post("admin/tanya-jawab/{$id}/tampilkan", ['tampil' => '1'])->assertRedirectTo(site_url('admin/tanya-jawab'));
        $this->seeInDatabase('site_questions', ['id' => $id, 'is_published' => 0]);

        $this->actingAs($admin)->post("admin/tanya-jawab/{$id}/ubah", ['topic' => 'Biaya', 'question' => 'Ada beasiswa?', 'answer' => 'Ada, untuk murid berprestasi.', 'is_published' => '1'])
            ->assertRedirectTo(site_url('admin/tanya-jawab'));
        $this->seeInDatabase('site_questions', ['id' => $id, 'is_published' => 1, 'answer' => 'Ada, untuk murid berprestasi.']);
        $this->seeInDatabase('audit_logs', ['event' => 'updated', 'subject_type' => 'site_questions', 'subject_id' => (string) $id]);
    }

    public function testHideShowAndDelete(): void
    {
        $ids   = $this->seed();
        $admin = $this->makeUser('admin1', 'admin');
        $id    = $ids['Berapa biayanya?'];

        $this->actingAs($admin)->post("admin/tanya-jawab/{$id}/tampilkan", ['tampil' => '0'])->assertRedirectTo(site_url('admin/tanya-jawab'));
        $this->seeInDatabase('site_questions', ['id' => $id, 'is_published' => 0]);
        $this->get('tanya-jawab')->assertDontSee('Berapa biayanya?');

        $this->actingAs($admin)->post("admin/tanya-jawab/{$id}/tampilkan", ['tampil' => '1']);
        $this->seeInDatabase('site_questions', ['id' => $id, 'is_published' => 1]);

        $this->actingAs($admin)->post("admin/tanya-jawab/{$id}/hapus")->assertRedirectTo(site_url('admin/tanya-jawab'));
        $this->assertNull($this->questions->find($id));
        $this->seeInDatabase('audit_logs', ['event' => 'deleted', 'subject_type' => 'site_questions', 'subject_id' => (string) $id]);

        $this->actingAs($admin)->get('admin/tanya-jawab/999/ubah')->assertRedirectTo(site_url('admin/tanya-jawab'));
        $this->assertSame('Pertanyaan tidak ditemukan. Mungkin sudah dihapus.', session('error'));
    }

    public function testReorderQuestionsAndTopics(): void
    {
        $ids   = $this->seed();
        $admin = $this->makeUser('admin1', 'admin');

        // Inside a topic.
        $this->actingAs($admin)->post('admin/tanya-jawab/' . $ids['Perlu membuat akun?'] . '/pindah', ['arah' => 'naik']);
        $this->assertSame(['Perlu membuat akun?', 'Bagaimana cara mendaftar?', 'Berapa biayanya?', 'Ada beasiswa?'], $this->orderedQuestions());

        // Never across topics.
        $this->actingAs($admin)->post('admin/tanya-jawab/' . $ids['Bagaimana cara mendaftar?'] . '/pindah', ['arah' => 'turun']);
        $this->assertSame(['Perlu membuat akun?', 'Bagaimana cara mendaftar?', 'Berapa biayanya?', 'Ada beasiswa?'], $this->orderedQuestions());

        // Whole topic.
        $this->actingAs($admin)->post('admin/tanya-jawab/topik/pindah', ['topik' => 'biaya', 'arah' => 'naik'])->assertRedirectTo(site_url('admin/tanya-jawab'));
        $this->assertSame(['Berapa biayanya?', 'Ada beasiswa?', 'Perlu membuat akun?', 'Bagaimana cara mendaftar?'], $this->orderedQuestions());
        $this->assertSame([10, 20, 30, 40], array_map('intval', array_column($this->questions->ordered(), 'sort_order')));
    }

    public function testPublicPageGroupsTopicsAsAccordion(): void
    {
        $this->seed();

        $page = $this->get('tanya-jawab');
        $page->assertOK();
        $page->assertSee('<h1', null, false);
        $page->assertSee('Tanya Jawab');
        $page->assertSee('aria-label="Breadcrumb"');
        $page->assertSee('<details id="pertanyaan-', null, false);
        $page->assertSee('<summary', null, false);
        $page->assertSee('href="' . site_url('daftar') . '"', null, false);
        $page->assertSee('<li>Siapkan berkas.</li>', null, false);
        $page->assertSee('href="#biaya"', null, false);
        $page->assertSee('Pertanyaan Anda belum terjawab?');
        $page->assertDontSee('Ada beasiswa?');
        $this->assertLessThan(strpos($page->getBody(), 'id="biaya"'), strpos($page->getBody(), 'id="pendaftaran"'));
        // Public menu link while something is published, each link once.
        $page->assertSee('href="' . site_url('tanya-jawab') . '"', null, false);
        // Inside the page content the requirements page is linked once (contact block); the layout adds its
        // own links (header menu, drawer, bottom bar, footer).
        $body = $page->getBody();
        $main = substr($body, (int) strpos($body, '<main'), (int) strpos($body, '</main>') - (int) strpos($body, '<main'));
        $this->assertSame(1, substr_count($main, 'href="' . site_url('persyaratan') . '"'));
    }

    public function testTopicsFormAContentsThatHighlightsTheTopicInView(): void
    {
        $this->seed();

        $body = $this->get('tanya-jawab')->getBody();

        // The topic chips are a contents list (D-088): ui.js marks the topic in view.
        $this->assertMatchesRegularExpression('/<nav class="daftar-isi sticky[^"]*" aria-label="Topik" data-daftar-isi>/', $body);
        $nav = substr($body, (int) strpos($body, 'data-daftar-isi>'));
        $nav = substr($nav, 0, (int) strpos($nav, '</nav>'));
        $this->assertStringContainsString('href="#pendaftaran"', $nav);
        $this->assertStringContainsString('href="#biaya"', $nav);
        // Headings leave room for the sticky header and contents.
        $this->assertStringContainsString('id="biaya" class="mb-4 scroll-mt-56', $body);
    }

    public function testPublicPageHasASearchBox(): void
    {
        $this->seed();

        $body = $this->get('tanya-jawab')->getBody();

        // D-097: a search box that filters the questions while typing (ui.js); hidden until the script runs.
        $this->assertMatchesRegularExpression('/<div class="mb-8" role="search" data-cari-halaman data-cari-wadah="#daftar-tanya-jawab" data-cari-jenis="butir"[^>]* hidden>/', $body);
        $this->assertStringContainsString('<div id="daftar-tanya-jawab"', $body);
        $this->assertStringContainsString(lang('Situs.qaSearchLabel'), $body);
        $this->assertStringContainsString('<input type="search" id="cari-tanya-jawab"', $body);

        // No questions published: no search box.
        $this->questions->where('id >', 0)->delete();
        $this->assertStringNotContainsString('data-cari-halaman', $this->get('tanya-jawab')->getBody());
    }

    public function testPublicPageWithoutQuestions(): void
    {
        $this->questions->insert(['topic' => 'Biaya', 'question' => 'Tersembunyi?', 'answer' => 'Ya.', 'is_published' => 0]);

        $page = $this->get('tanya-jawab');
        $page->assertOK();
        $page->assertSee('Belum ada tanya jawab');
        $page->assertDontSee('Tersembunyi?');
        // No link to the page; the hreflang alternates of the page itself (D-109) do not count.
        $withoutAlternates = static fn (string $html): string => (string) preg_replace('#<link rel="alternate"[^>]*>#', '', $html);
        $this->assertStringNotContainsString('href="' . site_url('tanya-jawab') . '"', $withoutAlternates((string) $page->getBody()));

        $this->get('/')->assertDontSee('href="' . site_url('tanya-jawab') . '"', null, false);
    }

    public function testMenuLinkHiddenWhenModuleIsOff(): void
    {
        $this->seed();
        Services::injectMock('modules', new ModuleRegistry(APPPATH . 'Modules', config('Features')->core, ['Situs']));

        // Routes of switched-off modules are covered generically in FiturTest.
        $this->get('/')->assertDontSee('href="' . site_url('tanya-jawab') . '"', null, false);
    }

    public function testAnswersAreRenderedSafely(): void
    {
        setting('School.name', 'SMP Contoh');
        $html = (new AnswerRenderer())->render("Halo <script>alert(1)</script> {nama_sekolah}.\n\n[jahat](javascript:alert(1)) [luar](https://example.org) [cek](/cek-status?a=1&b=2)");
        setting()->forget('School.name');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('SMP Contoh', $html);
        $this->assertStringContainsString('href="https://example.org"', $html);
        $this->assertStringContainsString('href="' . esc(site_url('cek-status?a=1&b=2'), 'attr') . '"', $html);
    }

    /**
     * Runs spmb:branding directly: command() would re-discover every command
     * class, which clashes with classes other tests in the suite already loaded.
     */
    private function runBranding(string $pack): void
    {
        (new Branding(service('logger'), $this->createMock(Commands::class)))->run([$pack]);
    }

    private function makePack(string $json): string
    {
        $this->tmpDir = sys_get_temp_dir() . '/spmb-qa-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir . '/branding', 0o777, true);
        file_put_contents($this->tmpDir . '/branding/branding.json', '{"settings": {}}');
        file_put_contents($this->tmpDir . '/tanya-jawab.json', $json);

        return $this->tmpDir . '/branding';
    }

    public function testBrandingPackLoadsTheClientQuestionsOnce(): void
    {
        $pack = $this->makePack((string) file_get_contents(self::CLIENT_FILE));

        $this->runBranding($pack);
        $this->assertStringContainsString('Tanya jawab loaded', $this->getStreamFilterBuffer());

        $all    = $this->questions->ordered();
        $source = json_decode((string) file_get_contents(self::CLIENT_FILE), true)['questions'];
        $this->assertCount(count($source), $all);
        $this->assertSame(array_column($source, 'question'), array_column($all, 'question'));

        foreach ($all as $row) {
            // Anything only the school can answer stays hidden.
            $this->assertSame(SiteQuestionModel::needsSchoolInput($row) ? 0 : 1, (int) $row['is_published'], $row['question']);
        }

        $this->seeInDatabase('audit_logs', ['event' => 'created', 'subject_type' => 'site_questions']);

        // Running again keeps the school's edits.
        $first = $all[0];
        $this->questions->update((int) $first['id'], ['answer' => 'Diubah sekolah.']);
        $this->runBranding($pack);
        $this->assertStringContainsString('already has questions', $this->getStreamFilterBuffer());
        $this->assertCount(count($source), $this->questions->ordered());
        $this->seeInDatabase('site_questions', ['id' => $first['id'], 'answer' => 'Diubah sekolah.']);

        // The public page renders every published answer.
        $page = $this->get('tanya-jawab');
        $page->assertOK();
        $page->assertSee('Bagaimana cara mendaftar?');
        $page->assertDontSee('ISI SEKOLAH');
    }

    public function testBrandingPackRejectsBrokenQuestions(): void
    {
        $pack = $this->makePack('{"questions": [{"topic": "Biaya", "question": "", "answer": "x"}]}');

        $this->runBranding($pack);
        $this->assertStringContainsString('question 1 needs', $this->getStreamFilterBuffer());
        $this->assertSame(0, $this->questions->countAllResults());
    }

    public function testClientQuestionsLinkOnlyToExistingPages(): void
    {
        $data = json_decode((string) file_get_contents(self::CLIENT_FILE), true);
        $this->assertIsArray($data);
        $allowed = ['/daftar', '/persyaratan', '/jadwal', '/cek-status', '/tanya-jawab'];

        foreach ($data['questions'] as $item) {
            $this->assertNotSame('', trim($item['topic']));
            $this->assertLessThanOrEqual(200, mb_strlen($item['question']));
            preg_match_all('#\]\((/[^)\s]*)\)#', $item['answer'], $links);

            foreach ($links[1] as $link) {
                $this->assertContains(strtok($link, '?#'), $allowed, $item['question']);
            }
        }
    }
}
