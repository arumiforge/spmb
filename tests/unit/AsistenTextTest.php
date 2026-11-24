<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use Modules\Asisten\Libraries\AiGateway;
use Modules\Asisten\Libraries\QaMatcher;
use Modules\Asisten\Libraries\TextNormaliser;

/**
 * Tanya Asisten text handling (D-110): normalising words, hiding personal data and matching typed
 * questions against Tanya Jawab entries.
 *
 * @internal
 */
final class AsistenTextTest extends CIUnitTestCase
{
    /**
     * @return list<array<string, mixed>>
     */
    private function rows(): array
    {
        return [
            ['id' => 1, 'topic' => 'Cara mendaftar', 'question' => 'Bagaimana cara mendaftar?', 'answer' => 'Buka halaman [Daftar](/daftar) lalu isi formulir.'],
            ['id' => 2, 'topic' => 'Cara mendaftar', 'question' => 'Bisakah saya mendaftar lewat HP?', 'answer' => 'Bisa, formulir bisa diisi dari HP.'],
            ['id' => 3, 'topic' => 'Biaya', 'question' => 'Berapa biaya pendaftaran?', 'answer' => 'Pendaftaran tidak dipungut biaya.'],
            ['id' => 4, 'topic' => 'Biaya', 'question' => 'Apakah ada keringanan biaya atau beasiswa?', 'answer' => 'Ada beasiswa untuk anak yatim.'],
            ['id' => 5, 'topic' => 'Gelombang', 'question' => 'Kapan pendaftaran dibuka?', 'answer' => 'Lihat halaman jadwal.'],
            ['id' => 6, 'topic' => 'Tes', 'question' => 'Kapan dan di mana tes dilaksanakan?', 'answer' => 'Di sekolah, sesuai kartu.'],
            ['id' => 7, 'topic' => 'Kartu dan status', 'question' => 'Kartu pendaftaran hilang. Apa yang harus saya lakukan?', 'answer' => 'Unduh lagi lewat Cek Status.'],
        ];
    }

    public function testWordsDropCommonWordsAndMapSynonyms(): void
    {
        $this->assertSame(['biaya'], TextNormaliser::words('Berapa biayanya, Bu?'));
        $this->assertSame(['jadwal', 'daftar', 'jadwal'], TextNormaliser::words('Kapan pendaftaran dibuka?'));
        $this->assertSame(['biaya'], TextNormaliser::words('How much is the FEE?'));
        $this->assertSame(['biaya'], TextNormaliser::words('Apakah gratis?'));
        $this->assertSame(['jadwal', 'jadwal', 'daftar'], TextNormaliser::words('متى يبدأ التسجيل؟'));
        $this->assertSame(['syarat', 'berkas'], TextNormaliser::words('Syarat dan dokumen'));
        $this->assertSame(['ecole'], TextNormaliser::words('École!'));
        $this->assertSame([], TextNormaliser::words('Halo, assalamualaikum'));
    }

    public function testKeyGroupsTheSameQuestionInOtherWords(): void
    {
        $this->assertSame(TextNormaliser::key('Kapan pendaftaran dibuka?'), TextNormaliser::key('pendaftaran dibuka kapan ya'));
        $this->assertSame('asrama', TextNormaliser::key('Apakah ada asrama?'));
    }

    public function testPersonalDataIsMasked(): void
    {
        [$text, $hidden] = TextNormaliser::maskPersonalData('NIK anak saya 3319 0123 4567 8901, HP 0812-3456-7890, email ibu@contoh.id');

        $this->assertTrue($hidden);
        $this->assertStringNotContainsString('3319', $text);
        $this->assertStringNotContainsString('0812', $text);
        $this->assertStringNotContainsString('ibu@contoh.id', $text);
        $this->assertStringContainsString('[####]', $text);
        $this->assertStringContainsString('[email]', $text);

        [$text, $hidden] = TextNormaliser::maskPersonalData('Kapan tes tanggal 12 Januari 2027?');
        $this->assertFalse($hidden);
        $this->assertSame('Kapan tes tanggal 12 Januari 2027?', $text);
    }

    public function testMatcherFindsTheBestEntry(): void
    {
        $matcher = new QaMatcher($this->rows(), 0.6);

        $this->assertSame(3, $matcher->match('berapa biayanya?')['row']['id'], 'the shorter, closer question wins a tie');
        $this->assertSame(1, $matcher->match('cara daftar gimana')['row']['id']);
        $this->assertSame(5, $matcher->match('When does registration open?')['row']['id']);
        $this->assertSame(6, $matcher->match('jadwal tes kapan')['row']['id']);
        $this->assertSame(7, $matcher->match('kartu saya hilang')['row']['id']);

        $hit = $matcher->match('biaya pendaftaran');
        $this->assertSame(3, $hit['row']['id']);
        $this->assertContains(4, array_column($hit['related'], 'id'), 'other close entries are offered');
    }

    public function testMatcherMissesUnrelatedQuestionsAndRespectsTheThreshold(): void
    {
        $matcher = new QaMatcher($this->rows(), 0.6);

        $this->assertNull($matcher->match('Apakah ada asrama untuk santri putri?'));
        $this->assertNull($matcher->match('halo'));
        $this->assertNull($matcher->match('Apakah ada antar jemput dari rumah?'));

        // "biaya" is known, "asrama" (a rare, unknown word) is not: about a third of the weight is covered.
        $this->assertNull($matcher->match('biaya asrama'));
        $this->assertNotNull((new QaMatcher($this->rows(), 0.25))->match('biaya asrama'));
    }

    public function testMatcherNeedsTheQuestionOrTopicNotOnlyTheAnswer(): void
    {
        // "yatim" only appears in an answer.
        $this->assertNull((new QaMatcher($this->rows(), 0.1))->match('yatim'));
    }

    public function testKeysAreScrubbedFromErrorTexts(): void
    {
        $this->assertSame('Header Bearer •••• was refused for ••••', AiGateway::scrub('Header Bearer sk-secret-123 was refused for sk-secret-123', 'sk-secret-123'));
    }
}
