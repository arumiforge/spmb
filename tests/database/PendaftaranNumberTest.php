<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Services\RegistrationNumber;

/**
 * Registration numbers (FR-REG-03, D-031).
 *
 * @internal
 */
final class PendaftaranNumberTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace;
    protected $refresh = true;

    protected function tearDown(): void
    {
        setting()->forget('Pendaftaran.numberFormat');
        setting()->forget('Pendaftaran.numberScope');
        parent::tearDown();
    }

    public function testFormatTokens(): void
    {
        $this->assertSame('PMB-2027-0001', RegistrationNumber::format('PMB-{YEAR}-{0000}', '2027/2028', 1));
        $this->assertSame('SPMB/27/012', RegistrationNumber::format('SPMB/{YY}/{000}', '2027/2028', 12));
        $this->assertSame('A12345', RegistrationNumber::format('A{000}', '2027/2028', 12345));
        $this->assertSame('2028.7', RegistrationNumber::format('{YEAR}.{0}', '2028/2029', 7));
    }

    public function testFormatValidation(): void
    {
        $this->assertTrue(RegistrationNumber::isValidFormat('PMB-{YEAR}-{0000}'));
        $this->assertTrue(RegistrationNumber::isValidFormat('{0000}'));
        $this->assertFalse(RegistrationNumber::isValidFormat('PMB-{YEAR}'));
        $this->assertFalse(RegistrationNumber::isValidFormat('PMB {0000}'));
        $this->assertFalse(RegistrationNumber::isValidFormat('{0000}{00}'));
        $this->assertFalse(RegistrationNumber::isValidFormat('<b>{0000}'));
        $this->assertFalse(RegistrationNumber::isValidFormat('{MONTH}-{0000}'));
        $this->assertFalse(RegistrationNumber::isValidFormat(str_repeat('A', 27) . '{0000}'));
    }

    public function testSequencePerAcademicYear(): void
    {
        $numbers = new RegistrationNumber();

        $this->assertSame('PMB-2027-0001', $numbers->next('2027/2028'));
        $this->assertSame('PMB-2027-0002', $numbers->next('2027/2028'));
        $this->assertSame('PMB-2028-0001', $numbers->next('2028/2029'));
        $this->assertSame('PMB-2027-0003', $numbers->next('2027/2028'));
        $this->seeInDatabase('registration_counters', ['scope' => '2027/2028', 'last_number' => 3]);
    }

    public function testOneEndlessSequence(): void
    {
        setting('Pendaftaran.numberScope', 'never');
        setting('Pendaftaran.numberFormat', 'NO-{0000}');
        $numbers = new RegistrationNumber();

        $this->assertSame('NO-0001', $numbers->next('2027/2028'));
        $this->assertSame('NO-0002', $numbers->next('2028/2029'));
        $this->seeInDatabase('registration_counters', ['scope' => 'all', 'last_number' => 2]);
    }

    public function testNumberIsNeverReused(): void
    {
        $numbers = new RegistrationNumber();
        $model   = model(ApplicantModel::class);
        $first   = $numbers->next('2027/2028');
        $id      = $model->insert([
            'registration_number' => $first, 'academic_year' => '2027/2028', 'method' => 'online',
            'full_name'           => 'Ahmad', 'name_key' => 'ahmad', 'gender' => 'L', 'birth_place' => 'Kudus',
            'birth_date'          => '2015-01-01', 'parent_phone' => '081234567890', 'registered_at' => date('Y-m-d H:i:s'),
        ]);
        $model->delete($id);

        $this->assertSame('PMB-2027-0002', $numbers->next('2027/2028'));

        // Even when the counter is reset by hand, existing (also deleted) numbers are skipped.
        db_connect()->table('registration_counters')->update(['last_number' => 0]);
        $this->assertSame('PMB-2027-0002', $numbers->next('2027/2028'));
        $model->insert([
            'registration_number' => 'PMB-2027-0003', 'academic_year' => '2027/2028', 'method' => 'online',
            'full_name'           => 'Budi', 'name_key' => 'budi', 'gender' => 'L', 'birth_place' => 'Kudus',
            'birth_date'          => '2015-01-01', 'parent_phone' => '081234567890', 'registered_at' => date('Y-m-d H:i:s'),
        ]);
        $this->assertSame('PMB-2027-0004', $numbers->next('2027/2028'));
    }

    public function testInvalidStoredFormatFallsBackToDefault(): void
    {
        setting('Pendaftaran.numberFormat', 'tanpa-urutan');

        $this->assertSame('PMB-2027-0001', (new RegistrationNumber())->next('2027/2028'));
    }

    public function testRollbackOfOuterTransactionReleasesNothing(): void
    {
        $db = db_connect();
        $db->transBegin();
        (new RegistrationNumber())->next('2027/2028');
        $db->transRollback();

        $this->dontSeeInDatabase('registration_counters', ['scope' => '2027/2028', 'last_number' => 1]);
        $this->assertSame('PMB-2027-0001', (new RegistrationNumber())->next('2027/2028'));
    }

    public function testRandomSuffix(): void
    {
        $fixed = static fn (int $n): string => substr('ABCDE', 0, $n);

        $this->assertSame('PMB-2027-0012-ABC', RegistrationNumber::format('PMB-{YEAR}-{0000}-{ACAK:3}', '2027/2028', 12, $fixed));
        $this->assertSame('PMB-2027-0012-ABCD', RegistrationNumber::format('PMB-{YEAR}-{0000}-{ACAK}', '2027/2028', 12, $fixed));
        $this->assertTrue(RegistrationNumber::isValidFormat('PMB-{YEAR}-{0000}-{ACAK:5}'));
        $this->assertTrue(RegistrationNumber::isValidFormat('PMB-{YEAR}-{0000}'));
        $this->assertFalse(RegistrationNumber::isValidFormat('PMB-{YEAR}-{0000}-{ACAK:6}'));
        $this->assertFalse(RegistrationNumber::isValidFormat('{ACAK:2}-{0000}-{ACAK:2}'));

        foreach ([1, 3, 5] as $n) {
            $this->assertMatchesRegularExpression('/\A[ABCDEFGHJKMNPQRSTUVWXYZ2-9]{' . $n . '}\z/', RegistrationNumber::random($n));
        }

        setting('Pendaftaran.numberFormat', 'PMB-{YEAR}-{0000}-{ACAK:5}');
        $number = (new RegistrationNumber())->next('2027/2028');
        $this->assertMatchesRegularExpression('/\APMB-2027-0001-[A-Z2-9]{5}\z/', $number);
        $this->assertDoesNotMatchRegularExpression('/[OIL01]\z/', substr($number, -5));
    }
}
