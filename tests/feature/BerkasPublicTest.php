<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\DocumentService;
use Modules\Pendaftaran\Models\ApplicantModel;
use Tests\Support\Berkas\BerkasTestHelpers;

/**
 * Applicant side of documents (/cek-status/berkas/...), access through the
 * applicant session grant only (D-008, D-034).
 *
 * @internal
 */
final class BerkasPublicTest extends CIUnitTestCase
{
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

    public function testUploadWithoutGrantGoesBackToStatusCheck(): void
    {
        $kk = $this->type('kk');
        $this->fakeUpload('camera-rotated.jpg');

        $this->post("cek-status/berkas/{$kk['id']}")->assertRedirectTo(site_url('cek-status'));
        $this->assertSame(0, model(ApplicantDocumentModel::class)->countAllResults());
    }

    public function testApplicantUploadsOwnDocument(): void
    {
        $applicant = $this->makeApplicant();
        $kk        = $this->type('kk');
        $this->fakeUpload('camera-rotated.jpg', 'IMG_20270105.jpg');

        $this->withSession($this->grantSession((int) $applicant['id']))->post("cek-status/berkas/{$kk['id']}")
            ->assertRedirectTo(site_url('cek-status/data') . '#berkas-' . $kk['id']);

        $doc = model(ApplicantDocumentModel::class)->currentOf((int) $applicant['id'], (int) $kk['id']);
        $this->assertSame(['pending', null, 'IMG_20270105.jpg'], [$doc['status'], $doc['uploaded_by'], $doc['original_name']]);
        $this->assertSame('message', array_key_first(array_intersect_key($_SESSION, ['message' => 1])));
    }

    public function testUploadErrorIsFriendly(): void
    {
        $applicant = $this->makeApplicant();
        $kk        = $this->type('kk');
        $this->fakeUpload('fake-photo.jpg');

        $this->withSession($this->grantSession((int) $applicant['id']))->post("cek-status/berkas/{$kk['id']}")
            ->assertSessionHas('berkas_error', [(int) $kk['id'] => lang('Berkas.errWrongFormat', ['JPG, PNG atau PDF'])]);
    }

    public function testInactiveTypeIs404(): void
    {
        $applicant = $this->makeApplicant();
        $kk        = $this->type('kk');
        model(DocumentTypeModel::class)->update($kk['id'], ['is_active' => 0]);
        $this->fakeUpload('camera-rotated.jpg');

        $this->withSession($this->grantSession((int) $applicant['id']))->post("cek-status/berkas/{$kk['id']}")->assertStatus(404);
    }

    public function testApplicantSeesOwnFileOnly(): void
    {
        $mine   = $this->makeApplicant();
        $other  = $this->makeApplicant();
        $myDoc  = (new DocumentService())->upload($mine, $this->type('akta'), $this->tmpCopy('sample.pdf'), 'akta.pdf', null);
        $hisDoc = (new DocumentService())->upload($other, $this->type('akta'), $this->tmpCopy('sample.pdf'), 'akta.pdf', null);

        $result = $this->withSession($this->grantSession((int) $mine['id']))->get("cek-status/berkas/{$myDoc['id']}");
        $result->assertOK();
        $result->assertHeader('Content-Type', 'application/pdf');
        $result->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->withSession($this->grantSession((int) $mine['id']))->get("cek-status/berkas/{$hisDoc['id']}")->assertStatus(404);
        $this->withSession([])->get("cek-status/berkas/{$myDoc['id']}")->assertStatus(404);
        $this->withSession(['applicant_access' => ['id' => (int) $mine['id'], 'expires' => time() - 10]])->get("cek-status/berkas/{$myDoc['id']}")->assertStatus(404);
    }

    public function testReplacedFileIsNoLongerServed(): void
    {
        $applicant = $this->makeApplicant();
        $old       = (new DocumentService())->upload($applicant, $this->type('kk'), $this->tmpCopy('sample.pdf'), 'kk.pdf', null);
        (new DocumentService())->upload($applicant, $this->type('kk'), $this->tmpCopy('camera-rotated.jpg'), 'kk.jpg', null);

        $this->withSession($this->grantSession((int) $applicant['id']))->get("cek-status/berkas/{$old['id']}")->assertStatus(404);
    }

    public function testToFollowAndValidDocumentsAreProtected(): void
    {
        $applicant = $this->makeApplicant();
        $rapor     = $this->type('rapor-skl');
        $kk        = $this->type('kk');
        $session   = $this->grantSession((int) $applicant['id']);

        $this->withSession($session)->post("cek-status/berkas/{$rapor['id']}", ['aksi' => 'menyusul'])->assertSessionHas('message');
        $this->assertSame('to_follow', model(ApplicantDocumentModel::class)->currentOf((int) $applicant['id'], (int) $rapor['id'])['status']);

        $this->withSession($session)->post("cek-status/berkas/{$kk['id']}", ['aksi' => 'menyusul'])
            ->assertSessionHas('berkas_error', [(int) $kk['id'] => lang('Berkas.errCannotFollow')]);

        $doc   = (new DocumentService())->upload($applicant, $kk, $this->tmpCopy('camera-rotated.jpg'), 'kk.jpg', null);
        $staff = $this->makeUser('panitia1', 'panitia');
        (new DocumentService())->verify($doc, 'valid', '', (int) $staff->id);

        $this->fakeUpload('camera-rotated.jpg');
        $this->withSession($session)->post("cek-status/berkas/{$kk['id']}")
            ->assertSessionHas('berkas_error', [(int) $kk['id'] => lang('Berkas.errAlreadyValid')]);
        $this->assertSame((string) $doc['id'], (string) model(ApplicantDocumentModel::class)->currentOf((int) $applicant['id'], (int) $kk['id'])['id']);
    }

    public function testCancelledApplicantCannotUpload(): void
    {
        $applicant = $this->makeApplicant(['status' => 'cancelled']);
        $kk        = $this->type('kk');
        $this->fakeUpload('camera-rotated.jpg');

        $this->withSession($this->grantSession((int) $applicant['id']))->post("cek-status/berkas/{$kk['id']}")
            ->assertSessionHas('berkas_error', [(int) $kk['id'] => lang('Berkas.errCancelled')]);
    }

    public function testPublicPanel(): void
    {
        setting('Berkas.deadline', '2027-04-30');
        $applicant = $this->makeApplicant();
        $doc       = (new DocumentService())->upload($applicant, $this->type('kk'), $this->tmpCopy('camera-rotated.jpg'), 'kk.jpg', null);
        $staff     = $this->makeUser('panitia1', 'panitia');
        (new DocumentService())->verify($doc, 'invalid', 'Foto terpotong di bagian bawah', (int) $staff->id);
        $applicant = model(ApplicantModel::class)->find($applicant['id']);

        $this->loadRoutes();
        helper('ui');
        $html = module_panels('applicant_public', ['applicant' => $applicant]);

        $this->assertStringContainsString('id="berkas"', $html);
        $this->assertStringContainsString('Foto terpotong di bagian bawah', $html);
        $this->assertStringContainsString(lang('Berkas.uploadAgain'), $html);
        $this->assertStringContainsString(url_to('berkas.milik-saya', $doc['id']), $html);
        $this->assertStringContainsString('accept="image/*,.jpg,.jpeg,.png,.pdf,application/pdf"', html_entity_decode($html));
        $this->assertStringContainsString(lang('Berkas.toFollowButton'), $html);
        $this->assertStringContainsString('30 April 2027', $html);
        $this->assertStringNotContainsString('admin/berkas', $html);
        $this->assertStringNotContainsString(lang('Berkas.uploadedByStaff'), $html);
    }
}
