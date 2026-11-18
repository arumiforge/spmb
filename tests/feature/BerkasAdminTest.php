<?php

declare(strict_types=1);

use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Services\DocumentService;
use Modules\Pendaftaran\Models\ApplicantModel;
use Tests\Support\Berkas\BerkasTestHelpers;

/**
 * Staff side of documents: queue, review, view, verify, upload on behalf, panel.
 *
 * @internal
 */
final class BerkasAdminTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use BerkasTestHelpers;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->resetServices();
        $this->seedTypes();
    }

    protected function tearDown(): void
    {
        $this->removeStoredFiles();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $applicant
     *
     * @return array<string, mixed>
     */
    private function uploaded(array $applicant, string $code = 'kk', string $fixture = 'camera-rotated.jpg'): array
    {
        return (new DocumentService())->upload($applicant, $this->type($code), $this->tmpCopy($fixture), $fixture, null);
    }

    private function documentStatus(int $applicantId): string
    {
        return (string) model(ApplicantModel::class, false)->find($applicantId)['document_status'];
    }

    public function testQueuePermissionsAndOrder(): void
    {
        $older = $this->makeApplicant(['full_name' => 'Budi Lama', 'registered_at' => '2027-01-01 08:00:00']);
        $newer = $this->makeApplicant(['full_name' => 'Citra Baru', 'registered_at' => '2027-01-05 08:00:00']);
        $this->uploaded($older);
        $this->uploaded($newer);

        $this->get('admin/berkas')->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->makeUser('kepsek1', 'kepsek'))->get('admin/berkas')->assertRedirectTo(site_url('admin/akses-ditolak'));

        $page = $this->actingAs($this->makeUser('panitia1', 'panitia'))->get('admin/berkas');
        $page->assertOK();
        $body = (string) $page->response()->getBody();
        $this->assertLessThan(strpos($body, 'Budi Lama'), strpos($body, 'Citra Baru'), 'newest applicants first');

        $search = $this->actingAs($this->makeUser('panitia2', 'panitia'))->get('admin/berkas?q=Citra');
        $search->assertSee('Citra Baru');
        $search->assertDontSee('Budi Lama');
    }

    public function testQueueEmptyState(): void
    {
        $this->actingAs($this->makeUser('panitia1', 'panitia'))->get('admin/berkas')->assertSee(lang('Berkas.queueEmptyTitle'));
    }

    public function testViewFileSendsPrivateHeaders(): void
    {
        $applicant = $this->makeApplicant();
        $doc       = $this->uploaded($applicant);

        $this->get("admin/berkas/{$doc['id']}/lihat")->assertRedirectTo(site_url('masuk'));
        $this->actingAs($this->makeUser('seragam1', 'petugas-seragam'))->get("admin/berkas/{$doc['id']}/lihat")->assertOK();

        $result = $this->actingAs($this->makeUser('kepsek1', 'kepsek'))->get("admin/berkas/{$doc['id']}/lihat");
        $result->assertOK();
        $result->assertHeader('Content-Type', 'image/jpeg');
        $result->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private, no-store', $result->response()->getHeaderLine('Cache-Control'));
        $this->assertStringStartsWith('inline; filename="kk-PMB-', $result->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringStartsWith("\xFF\xD8", (string) $result->response()->getBody());

        $this->actingAs($this->makeUser('kepsek2', 'kepsek'))->get('admin/berkas/99999/lihat')->assertStatus(404);
    }

    public function testReviewScreenAndVerifyFromQueue(): void
    {
        $first  = $this->makeApplicant(['full_name' => 'Dewi Pertama', 'registered_at' => '2027-01-05 08:00:00']);
        $second = $this->makeApplicant(['full_name' => 'Eko Kedua', 'registered_at' => '2027-01-01 08:00:00']);
        $doc1   = $this->uploaded($first);
        $doc2   = $this->uploaded($second, 'akta', 'sample.pdf');
        $staff  = $this->makeUser('panitia1', 'panitia');

        $this->actingAs($this->makeUser('kepsek1', 'kepsek'))->get("admin/berkas/{$doc1['id']}/periksa")->assertRedirectTo(site_url('admin/akses-ditolak'));

        $page = $this->actingAs($staff)->get("admin/berkas/{$doc1['id']}/periksa");
        $page->assertOK();
        $page->assertSee('Dewi Pertama');
        $page->assertSee($first['registration_number']);
        $page->assertSee(url_to('berkas.lihat', $doc1['id']));

        $this->actingAs($staff)->get("admin/berkas/{$doc2['id']}/periksa")->assertSee('application/pdf');

        // Invalid without a note is refused.
        $this->actingAs($staff)->post("admin/berkas/{$doc1['id']}/periksa", ['status' => 'invalid', 'note' => '', 'kembali' => 'antrean'])->assertSessionHas('errors');
        $this->assertSame('pending', model(ApplicantDocumentModel::class)->find($doc1['id'])['status']);

        // Valid → next waiting document opens.
        $this->actingAs($staff)->post("admin/berkas/{$doc1['id']}/periksa", ['status' => 'valid', 'kembali' => 'antrean'])
            ->assertRedirectTo(site_url("admin/berkas/{$doc2['id']}/periksa"));
        $row = model(ApplicantDocumentModel::class)->find($doc1['id']);
        $this->assertSame(['valid', (string) $staff->id], [$row['status'], (string) $row['verified_by']]);
        $this->assertNotNull($row['verified_at']);
        $this->seeInDatabase('audit_logs', ['event' => 'document_verified', 'subject_id' => (string) $doc1['id']]);

        // Last one → back to the queue.
        $this->actingAs($staff)->post("admin/berkas/{$doc2['id']}/periksa", ['status' => 'invalid', 'note' => 'Buram', 'kembali' => 'antrean'])
            ->assertRedirectTo(site_url('admin/berkas'));
        $this->assertSame('Buram', model(ApplicantDocumentModel::class)->find($doc2['id'])['note']);
        $this->assertSame('incomplete', $this->documentStatus((int) $second['id']));
    }

    public function testVerifyFromApplicantPageReturnsThere(): void
    {
        $applicant = $this->makeApplicant();
        $doc       = $this->uploaded($applicant);

        $this->actingAs($this->makeUser('panitia1', 'panitia'))->post("admin/berkas/{$doc['id']}/periksa", ['status' => 'valid', 'kembali' => 'pendaftar'])
            ->assertRedirectTo(site_url("admin/pendaftar/{$applicant['id']}") . '#berkas-' . $doc['document_type_id']);
        $this->actingAs($this->makeUser('admin1', 'admin'))->post("admin/berkas/{$doc['id']}/periksa", ['status' => 'valid'])->assertRedirect();
        $this->actingAs($this->makeUser('tes1', 'petugas-tes'))->post("admin/berkas/{$doc['id']}/periksa", ['status' => 'valid'])->assertRedirectTo(site_url('admin/akses-ditolak'));
    }

    public function testStaffUploadsOnBehalfAndReplaces(): void
    {
        $applicant = $this->makeApplicant();
        $foto      = $this->type('foto');
        $staff     = $this->makeUser('panitia1', 'panitia');

        $this->fakeUpload('camera-rotated.jpg', 'foto.jpg');
        $this->post("admin/pendaftar/{$applicant['id']}/berkas/{$foto['id']}")->assertRedirectTo(site_url('masuk'));

        $this->fakeUpload('camera-rotated.jpg', 'foto.jpg');
        $this->actingAs($this->makeUser('kepsek1', 'kepsek'))->post("admin/pendaftar/{$applicant['id']}/berkas/{$foto['id']}")->assertRedirectTo(site_url('admin/akses-ditolak'));

        $this->fakeUpload('camera-rotated.jpg', 'foto.jpg');
        $this->actingAs($staff)->post("admin/pendaftar/{$applicant['id']}/berkas/{$foto['id']}")
            ->assertRedirectTo(site_url("admin/pendaftar/{$applicant['id']}") . '#berkas-' . $foto['id']);

        $first = model(ApplicantDocumentModel::class)->currentOf((int) $applicant['id'], (int) $foto['id']);
        $this->assertSame((string) $staff->id, (string) $first['uploaded_by']);
        $this->assertFileExists(WRITEPATH . 'uploads/berkas/' . $first['file_path']);
        $this->seeInDatabase('audit_logs', ['event' => 'document_uploaded', 'subject_id' => (string) $first['id']]);

        $this->fakeUpload('transparent.png', 'foto2.png');
        $this->actingAs($staff)->post("admin/pendaftar/{$applicant['id']}/berkas/{$foto['id']}")->assertSessionHas('message');

        $second = model(ApplicantDocumentModel::class)->currentOf((int) $applicant['id'], (int) $foto['id']);
        $this->assertNotSame($first['id'], $second['id']);
        $this->assertNotNull(model(ApplicantDocumentModel::class)->onlyDeleted()->find($first['id']));
        $this->seeInDatabase('audit_logs', ['event' => 'document_replaced', 'subject_id' => (string) $first['id']]);
    }

    public function testStaffUploadErrorsAreShownPerDocument(): void
    {
        $applicant = $this->makeApplicant();
        $foto      = $this->type('foto');
        $staff     = $this->makeUser('panitia1', 'panitia');

        $this->fakeUpload('sample.pdf', 'foto.pdf');
        $this->actingAs($staff)->post("admin/pendaftar/{$applicant['id']}/berkas/{$foto['id']}")
            ->assertSessionHas('berkas_error', [(int) $foto['id'] => lang('Berkas.errWrongFormat', ['JPG atau PNG'])]);

        $this->clearUpload();
        $this->actingAs($staff)->post("admin/pendaftar/{$applicant['id']}/berkas/{$foto['id']}")
            ->assertSessionHas('berkas_error', [(int) $foto['id'] => lang('Berkas.errNoFile')]);

        $this->fakeUpload('camera-rotated.jpg', 'foto.jpg', UPLOAD_ERR_INI_SIZE);
        $result = $this->actingAs($staff)->post("admin/pendaftar/{$applicant['id']}/berkas/{$foto['id']}");
        $this->assertStringStartsWith('Ukuran file terlalu besar. Maksimal', $_SESSION['berkas_error'][(int) $foto['id']]);
        $result->assertRedirect();
    }

    public function testDocumentStatusFollowsTheDocuments(): void
    {
        $applicant = $this->makeApplicant();
        $id        = (int) $applicant['id'];
        $service   = new DocumentService();
        $staff     = (int) $this->makeUser('panitia1', 'panitia')->id;
        $docs      = [];

        foreach (['kk', 'akta', 'foto'] as $code) {
            $docs[$code] = $this->uploaded($applicant, $code);
            $this->assertSame('incomplete', $this->documentStatus($id));
        }

        $service->markToFollow($applicant, $this->type('rapor-skl'), null);
        $this->assertSame('pending', $this->documentStatus($id));

        foreach ($docs as $doc) {
            $service->verify($doc, 'valid', '', $staff);
        }

        $this->assertSame('to_follow', $this->documentStatus($id));

        $rapor = $this->uploaded($applicant, 'rapor-skl', 'sample.pdf');
        $this->assertSame('pending', $this->documentStatus($id));

        $service->verify($rapor, 'valid', '', $staff);
        $this->assertSame('complete', $this->documentStatus($id));

        $service->verify(model(ApplicantDocumentModel::class)->find($docs['kk']['id']), 'invalid', 'Terpotong', $staff);
        $this->assertSame('incomplete', $this->documentStatus($id));
    }

    public function testStaffMarksToFollowOnlyWhenAllowed(): void
    {
        $applicant = $this->makeApplicant();
        $staff     = $this->makeUser('panitia1', 'panitia');
        $rapor     = $this->type('rapor-skl');
        $kk        = $this->type('kk');

        $this->actingAs($staff)->post("admin/pendaftar/{$applicant['id']}/berkas/{$rapor['id']}", ['aksi' => 'menyusul'])->assertSessionHas('message');
        $this->assertSame('to_follow', model(ApplicantDocumentModel::class)->currentOf((int) $applicant['id'], (int) $rapor['id'])['status']);

        $this->actingAs($staff)->post("admin/pendaftar/{$applicant['id']}/berkas/{$kk['id']}", ['aksi' => 'menyusul'])
            ->assertSessionHas('berkas_error', [(int) $kk['id'] => lang('Berkas.errCannotFollow')]);
    }

    public function testAdminPanelRendersThroughModulePanels(): void
    {
        $applicant = $this->makeApplicant();
        $doc       = $this->uploaded($applicant);
        $applicant = model(ApplicantModel::class)->find($applicant['id']);

        $this->actingAs($this->makeUser('panitia1', 'panitia'));
        $this->loadRoutes();
        helper('ui');
        $html = module_panels('applicant_admin', ['applicant' => $applicant]);

        $this->assertStringContainsString('id="berkas"', $html);
        $this->assertStringContainsString('Kartu Keluarga', $html);
        $this->assertStringContainsString(url_to('berkas.lihat', $doc['id']), $html);
        $this->assertStringContainsString(url_to('berkas.periksa', $doc['id']), $html);
        $this->assertStringContainsString(url_to('berkas.unggah-petugas', $applicant['id'], $this->type('akta')['id']), $html);
        $this->assertStringContainsString(lang('Status.documents.incomplete'), $html);

        $this->assertStringNotContainsString('<section', view('Modules\Berkas\Views\panels\applicant_admin', ['applicant' => null], ['saveData' => false]));
    }

    public function testKepsekPanelHasNoUploadOrVerifyControls(): void
    {
        $applicant = $this->makeApplicant();
        $doc       = $this->uploaded($applicant);

        $this->actingAs($this->makeUser('kepsek1', 'kepsek'));
        $this->loadRoutes();
        $html = view('Modules\Berkas\Views\panels\applicant_admin', ['applicant' => $applicant], ['saveData' => false]);

        $this->assertStringContainsString(url_to('berkas.lihat', $doc['id']), $html);
        $this->assertStringNotContainsString('name="status"', $html);
        $this->assertStringNotContainsString('type="file"', $html);
    }
}
