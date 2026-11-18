<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Kartu\Services\CardAvailability;
use Modules\Kartu\Services\CardPdf;
use Modules\Kartu\Services\CardTokenService;
use Tests\Support\Kartu\KartuFixtures;

/**
 * Card tokens, availability rule and the PDF card (FR-CARD-01…04).
 *
 * @internal
 */
final class KartuServicesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use KartuFixtures;

    protected $namespace;
    protected $refresh = true;

    protected function tearDown(): void
    {
        setting()->forget('Kartu.availableWhen');
        parent::tearDown();
    }

    public function testTokensAreRandomBase64Url(): void
    {
        $a = CardTokenService::generate();

        $this->assertMatchesRegularExpression('/\A[A-Za-z0-9_-]{43}\z/', $a);
        $this->assertNotSame($a, CardTokenService::generate());
    }

    public function testTokenForIssuesOnceAndReusesIt(): void
    {
        $applicant = $this->makeApplicant();
        $service   = new CardTokenService();

        $first  = $service->tokenFor((int) $applicant['id']);
        $second = $service->tokenFor((int) $applicant['id']);

        $this->assertSame($first, $second);
        $this->assertSame(1, db_connect()->table('card_tokens')->countAllResults());
        $this->assertSame(1, db_connect()->table('audit_logs')->where('event', 'card_issued')->where('subject_id', (string) $applicant['id'])->countAllResults());
        // The token itself never goes to the audit log; no generic "created" entry either.
        $this->dontSeeInDatabase('audit_logs', ['subject_type' => 'card_tokens']);
        $this->assertStringNotContainsString($first, json_encode(db_connect()->table('audit_logs')->get()->getResultArray()));
    }

    public function testRenewRevokesTheOldToken(): void
    {
        $applicant = $this->makeApplicant();
        $staff     = $this->makeUser('panitia');
        $service   = new CardTokenService();
        $old       = $service->tokenFor((int) $applicant['id'], (int) $staff->id);

        $new = $service->renew((int) $applicant['id'], (int) $staff->id);

        $this->assertNotSame($old, $new);
        $this->assertNull($service->resolve($old));
        $this->assertTrue($service->isRevoked($old));
        $this->assertSame((int) $applicant['id'], (int) $service->resolve($new)['id']);
        $this->assertSame($new, $service->tokenFor((int) $applicant['id']));
        $this->seeInDatabase('audit_logs', ['event' => 'card_revoked', 'subject_id' => (string) $applicant['id'], 'user_id' => $staff->id]);
        $this->assertSame(2, db_connect()->table('audit_logs')->where('event', 'card_issued')->countAllResults());
    }

    public function testRenewWithoutCardJustIssuesOne(): void
    {
        $applicant = $this->makeApplicant();
        $staff     = $this->makeUser('panitia');

        (new CardTokenService())->renew((int) $applicant['id'], (int) $staff->id);

        $this->dontSeeInDatabase('audit_logs', ['event' => 'card_revoked']);
        $this->seeInDatabase('card_tokens', ['applicant_id' => $applicant['id'], 'revoked_at' => null, 'created_by' => $staff->id]);
    }

    public function testResolveRecordsScanAndRejectsUnknownOrDeleted(): void
    {
        $applicant = $this->makeApplicant();
        $service   = new CardTokenService();
        $token     = $service->tokenFor((int) $applicant['id']);

        $this->assertNull($service->resolve('tidak-ada'));
        $this->assertNull($service->resolve(''));
        $this->assertSame($applicant['registration_number'], $service->resolve($token)['registration_number']);
        $this->assertNotNull(db_connect()->table('card_tokens')->get()->getRowArray()['last_seen_at']);

        model(Modules\Pendaftaran\Models\ApplicantModel::class)->delete($applicant['id']);
        $this->assertNull($service->resolve($token));
    }

    public function testAvailabilityRule(): void
    {
        $registered = ['status' => 'registered'];
        $verified   = ['status' => 'verified'];
        $cancelled  = ['status' => 'cancelled'];

        $this->assertSame('immediately', CardAvailability::rule());
        $this->assertTrue(CardAvailability::allows($registered));
        $this->assertTrue(CardAvailability::allows($verified));
        $this->assertFalse(CardAvailability::allows($cancelled));
        $this->assertFalse(CardAvailability::allows(['status' => 'registered', 'deleted_at' => '2027-01-01 00:00:00']));

        setting('Kartu.availableWhen', 'verified');
        $this->assertFalse(CardAvailability::allows($registered));
        $this->assertTrue(CardAvailability::allows($verified));
        $this->assertFalse(CardAvailability::allows($cancelled));

        setting('Kartu.availableWhen', 'unknown');
        $this->assertSame('immediately', CardAvailability::rule());
    }

    public function testPdfCardRendersWithoutWritingData(): void
    {
        $applicant = $this->makeApplicant(['full_name' => 'Zoë Nur Āisyah 李', 'previous_school_id' => null, 'previous_school_name' => 'MI Ma’ahid Kudus']);
        $token     = (new CardTokenService())->tokenFor((int) $applicant['id']);
        $logs      = db_connect()->table('audit_logs')->countAllResults();

        $pdf = (new CardPdf())->render($applicant, $token);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame($logs, db_connect()->table('audit_logs')->countAllResults());
        $this->assertSame(1, db_connect()->table('card_tokens')->countAllResults());
    }

    public function testCardDetails(): void
    {
        $withList = $this->makeApplicant(['method' => 'offline']);
        $freeText = $this->makeApplicant(['previous_school_id' => null, 'previous_school_name' => 'MI Contoh', 'wave_id' => null]);

        $details = CardPdf::details($withList);
        $this->assertSame($withList['registration_number'], $details['number']);
        $this->assertStringStartsWith('SD Negeri', $details['school']);
        $this->assertStringStartsWith('Gelombang Uji', $details['wave']);
        $this->assertSame('Di sekolah', $details['method']);
        $this->assertSame('Belum dijadwalkan', $details['testStatus']);

        $details = CardPdf::details($freeText);
        $this->assertSame('MI Contoh', $details['school']);
        $this->assertSame('-', $details['wave']);
    }
}
