<?php

declare(strict_types=1);

namespace Modules\Asisten\Libraries;

use Normalizer;

/**
 * Turns a question into comparable words for the Tanya Jawab search (D-110), and hides personal data
 * before a question is stored or sent anywhere.
 *
 * words(): lower case, accents and Arabic vowel marks removed, punctuation dropped, common words
 * ("apa", "bagaimana", "the", "ما") removed, light suffix trimming ("biayanya" → "biaya") and a small
 * list of synonyms mapped to one word ("bayar", "gratis", "fee", "رسوم" → "biaya"). Indonesian,
 * English and Arabic are handled together, so a visitor may type in any of them on any page.
 */
final class TextNormaliser
{
    /**
     * Words that carry no meaning for the search.
     */
    public const STOPWORDS = [
        // Indonesian
        'apa', 'apakah', 'bagaimana', 'gimana', 'gmn', 'berapa', 'dimana', 'mana', 'siapa', 'kenapa', 'mengapa',
        'yang', 'dan', 'atau', 'di', 'ke', 'dari', 'untuk', 'utk', 'buat', 'dengan', 'dgn', 'pada', 'dalam', 'oleh',
        'ini', 'itu', 'tersebut', 'saya', 'aku', 'kami', 'kita', 'anda', 'kamu', 'dia', 'mereka', 'anak', 'ibu', 'bapak',
        'pak', 'bu', 'adik', 'kak', 'min', 'admin', 'ada', 'adakah', 'bisa', 'bisakah', 'boleh', 'bolehkah', 'mau', 'ingin',
        'tanya', 'bertanya', 'mohon', 'tolong', 'info', 'informasi', 'minta', 'harus', 'perlu', 'sudah', 'belum', 'akan',
        'sedang', 'masih', 'juga', 'saja', 'aja', 'sih', 'dong', 'ya', 'yah', 'nya', 'kah', 'lah', 'pun', 'tidak', 'tdk',
        'gak', 'ga', 'nggak', 'enggak', 'bukan', 'jika', 'kalau', 'kalo', 'agar', 'supaya', 'sebagai', 'secara', 'para',
        'seperti', 'karena', 'tentang', 'mengenai', 'lagi', 'sama', 'lebih', 'sangat', 'banyak', 'semua', 'setiap', 'tiap',
        'cara', 'bagaimanakah', 'halo', 'hai', 'assalamualaikum', 'wr', 'wb', 'terima', 'kasih', 'makasih', 'selamat',
        'pagi', 'siang', 'sore', 'malam', 'sekolah', 'smp', 'mts',
        // English
        'a', 'an', 'the', 'is', 'are', 'was', 'were', 'be', 'been', 'am', 'do', 'does', 'did', 'can', 'could', 'will', 'would',
        'should', 'may', 'might', 'must', 'have', 'has', 'had', 'i', 'me', 'my', 'we', 'our', 'you', 'your', 'he', 'she', 'it',
        'they', 'them', 'their', 'this', 'that', 'these', 'those', 'what', 'which', 'who', 'whom', 'how', 'why', 'where',
        'of', 'to', 'in', 'on', 'at', 'for', 'from', 'with', 'by', 'about', 'and', 'or', 'not', 'no', 'yes', 'if', 'so',
        'please', 'hello', 'hi', 'thanks', 'thank', 'there', 'any', 'much', 'many', 'some', 'want', 'need', 'ask', 'school', 'child',
        // Arabic
        'ما', 'ماذا', 'هل', 'كيف', 'كم', 'متى', 'أين', 'اين', 'من', 'في', 'على', 'الى', 'إلى', 'عن', 'مع', 'هذا', 'هذه',
        'ذلك', 'التي', 'الذي', 'و', 'او', 'أو', 'ان', 'أن', 'إن', 'انا', 'أنا', 'نحن', 'هو', 'هي', 'لا', 'نعم', 'يا', 'لو',
        'السلام', 'عليكم', 'شكرا', 'مرحبا', 'المدرسة', 'مدرسة',
    ];

    /**
     * Word → the one word it stands for in the search.
     */
    public const SYNONYMS = [
        // registration
        'daftar'     => 'daftar', 'mendaftar' => 'daftar', 'pendaftaran' => 'daftar', 'didaftarkan' => 'daftar', 'mendaftarkan' => 'daftar',
        'registrasi' => 'daftar', 'register' => 'daftar', 'registration' => 'daftar', 'enrol' => 'daftar', 'enroll' => 'daftar',
        'enrolment'  => 'daftar', 'enrollment' => 'daftar', 'apply' => 'daftar', 'application' => 'daftar', 'admission' => 'daftar',
        'spmb'       => 'daftar', 'ppdb' => 'daftar', 'تسجيل' => 'daftar', 'التسجيل' => 'daftar', 'قبول' => 'daftar', 'القبول' => 'daftar',
        // cost
        'biaya' => 'biaya', 'bayar' => 'biaya', 'membayar' => 'biaya', 'pembayaran' => 'biaya', 'dibayar' => 'biaya', 'gratis' => 'biaya',
        'harga' => 'biaya', 'uang' => 'biaya', 'spp' => 'biaya', 'iuran' => 'biaya', 'infaq' => 'biaya', 'infak' => 'biaya',
        'tarif' => 'biaya', 'murah' => 'biaya', 'mahal' => 'biaya', 'fee' => 'biaya', 'fees' => 'biaya', 'cost' => 'biaya',
        'costs' => 'biaya', 'pay' => 'biaya', 'payment' => 'biaya', 'price' => 'biaya', 'free' => 'biaya', 'tuition' => 'biaya',
        'رسوم'  => 'biaya', 'الرسوم' => 'biaya', 'تكلفة' => 'biaya', 'التكلفة' => 'biaya', 'دفع' => 'biaya', 'مجاني' => 'biaya',
        // requirements and documents
        'syarat' => 'syarat', 'persyaratan' => 'syarat', 'ketentuan' => 'syarat', 'requirement' => 'syarat', 'requirements' => 'syarat',
        'شروط'   => 'syarat', 'الشروط' => 'syarat', 'متطلبات' => 'syarat', 'المتطلبات' => 'syarat',
        'berkas' => 'berkas', 'dokumen' => 'berkas', 'document' => 'berkas', 'documents' => 'berkas', 'file' => 'berkas',
        'unggah' => 'berkas', 'upload' => 'berkas', 'وثائق' => 'berkas', 'الوثائق' => 'berkas', 'مستندات' => 'berkas',
        'kk'     => 'kk', 'kartu' => 'kartu', 'card' => 'kartu', 'akta' => 'akta', 'akte' => 'akta', 'ijazah' => 'ijazah',
        // time
        'jadwal'    => 'jadwal', 'kapan' => 'jadwal', 'tanggal' => 'jadwal', 'waktu' => 'jadwal', 'dibuka' => 'jadwal', 'buka' => 'jadwal', 'ditutup' => 'jadwal',
        'tutup'     => 'jadwal', 'schedule' => 'jadwal', 'date' => 'jadwal', 'dates' => 'jadwal', 'when' => 'jadwal', 'open' => 'jadwal',
        'deadline'  => 'jadwal', 'start' => 'jadwal', 'begin' => 'jadwal', 'mulai' => 'jadwal', 'dimulai' => 'jadwal',
        'يبدأ'      => 'jadwal', 'بداية' => 'jadwal', 'يفتح' => 'jadwal', 'فتح' => 'jadwal', 'جدول' => 'jadwal', 'موعد' => 'jadwal', 'تاريخ' => 'jadwal', 'متى' => 'jadwal',
        'gelombang' => 'gelombang', 'wave' => 'gelombang', 'round' => 'gelombang', 'دفعة' => 'gelombang', 'الدفعة' => 'gelombang',
        'kuota'     => 'kuota', 'quota' => 'kuota', 'kursi' => 'kuota', 'daya' => 'kuota', 'tampung' => 'kuota', 'seats' => 'kuota',
        // other topics
        'seragam'  => 'seragam', 'uniform' => 'seragam', 'زي' => 'seragam',
        'tes'      => 'tes', 'test' => 'tes', 'ujian' => 'tes', 'seleksi' => 'tes', 'exam' => 'tes', 'اختبار' => 'tes', 'امتحان' => 'tes',
        'asrama'   => 'asrama', 'pondok' => 'asrama', 'pesantren' => 'asrama', 'boarding' => 'asrama', 'dormitory' => 'asrama', 'سكن' => 'asrama',
        'status'   => 'status', 'hasil' => 'status', 'pengumuman' => 'status', 'result' => 'status',
        'lokasi'   => 'alamat', 'alamat' => 'alamat', 'letak' => 'alamat', 'address' => 'alamat', 'location' => 'alamat', 'عنوان' => 'alamat',
        'kontak'   => 'kontak', 'hubungi' => 'kontak', 'telepon' => 'kontak', 'wa' => 'kontak', 'whatsapp' => 'kontak', 'contact' => 'kontak',
        'phone'    => 'kontak', 'اتصال' => 'kontak', 'panitia' => 'panitia', 'committee' => 'panitia', 'لجنة' => 'panitia',
        'online'   => 'online', 'daring' => 'online', 'offline' => 'offline', 'luring' => 'offline', 'langsung' => 'offline',
        'umur'     => 'usia', 'usia' => 'usia', 'age' => 'usia', 'عمر' => 'usia',
        'beasiswa' => 'beasiswa', 'scholarship' => 'beasiswa', 'منحة' => 'beasiswa',
    ];

    /**
     * Indonesian particles and possessive endings trimmed from a word that is not known as it is.
     */
    private const SUFFIXES = ['nya', 'kah', 'lah', 'pun', 'ku', 'mu'];

    /**
     * Comparable words of a text, in order, duplicates kept.
     *
     * @return list<string>
     */
    public static function words(string $text): array
    {
        $words = [];

        foreach (self::rawWords($text) as $word) {
            $word = self::canonical($word);

            if ($word !== '' && ! in_array($word, self::STOPWORDS, true)) {
                $words[] = $word;
            }
        }

        return $words;
    }

    /**
     * Sorted unique comparable words joined by spaces, at most 191 characters: groups the same
     * question asked in other words in the question log.
     */
    public static function key(string $text): string
    {
        $words = array_values(array_unique(self::words($text)));
        sort($words);
        $key = implode(' ', $words);

        return mb_substr($key !== '' ? $key : mb_strtolower(trim(self::basic($text))), 0, 191);
    }

    /**
     * Hides numbers of 8 or more digits (NIK, NISN, KK, phone numbers; spaces, dots and dashes between
     * the digits included) and e-mail addresses. Returns [masked text, whether something was hidden].
     *
     * @return array{0: string, 1: bool}
     */
    public static function maskPersonalData(string $text): array
    {
        $count  = 0;
        $masked = (string) preg_replace('/[^\s@]+@[^\s@]+\.[^\s@]+/u', '[email]', $text, -1, $emails);
        $masked = (string) preg_replace_callback(
            '/\+?\d(?:[\s.\-]?\d){7,}/u',
            static fn (array $m): string => '[' . str_repeat('#', 4) . ']',
            $masked,
            -1,
            $count,
        );

        return [$masked, ($count + $emails) > 0];
    }

    /**
     * Lower case, accents and Arabic marks removed, Arabic letter forms unified, everything but letters
     * and digits turned into spaces.
     */
    public static function basic(string $text): string
    {
        $text = mb_strtolower($text);

        if (class_exists(Normalizer::class)) {
            $text = (string) Normalizer::normalize($text, Normalizer::FORM_KD);
        }

        // Combining marks: Latin accents and Arabic harakat; tatweel.
        $text = (string) preg_replace('/[\p{Mn}\x{0640}]+/u', '', $text);
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ؤ' => 'و', 'ئ' => 'ي']);

        return trim((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text));
    }

    /**
     * @return list<string>
     */
    private static function rawWords(string $text): array
    {
        $basic = self::basic($text);

        return $basic === '' ? [] : explode(' ', $basic);
    }

    private static function canonical(string $word): string
    {
        static $synonyms = null;
        static $stop     = null;

        if ($synonyms === null) {
            $synonyms = [];

            foreach (self::SYNONYMS as $from => $to) {
                $synonyms[self::basic($from)] = $to;
            }

            $stop = array_flip(array_map(self::basic(...), self::STOPWORDS));
        }

        if (isset($synonyms[$word])) {
            return $synonyms[$word];
        }

        if (isset($stop[$word])) {
            return '';
        }

        // "biayanya" → "biaya", "daftarnya" → "daftar"; Arabic "الرسوم" → "رسوم".
        foreach (self::SUFFIXES as $suffix) {
            if (mb_strlen($word) > mb_strlen($suffix) + 2 && str_ends_with($word, $suffix)) {
                $stem = mb_substr($word, 0, -mb_strlen($suffix));

                if (isset($synonyms[$stem])) {
                    return $synonyms[$stem];
                }
            }
        }

        if (mb_strlen($word) > 4 && str_starts_with($word, 'ال')) {
            $stem = mb_substr($word, 2);

            return $synonyms[$stem] ?? $stem;
        }

        // English plurals: "documents" → "document" when that is known.
        if (strlen($word) > 3 && str_ends_with($word, 's') && isset($synonyms[substr($word, 0, -1)])) {
            return $synonyms[substr($word, 0, -1)];
        }

        return $word;
    }
}
