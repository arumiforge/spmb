<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\DocumentService;
use Modules\Berkas\Services\UploadRejected;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Pendaftaran\Services\ApplicantService;

/**
 * Development data (php spark spmb:install --demo): three waves for the
 * configured academic year, the first open today, about 30 applicants
 * registered through ApplicantService, and one "complete example" applicant
 * with every field filled in, a guardian, a school from the list and (when the
 * Berkas module is on) every document uploaded and checked, so staff can see
 * what a complete record looks like. Idempotent: waves and the 30 applicants
 * only for a year without any, the example only when it does not exist yet.
 */
class DemoSeeder extends Seeder
{
    /**
     * Name of the complete example applicant (letters, spaces and "-" only, so staff can edit it).
     */
    public const EXAMPLE_NAME = 'Contoh Lengkap - Aisyah Putri Ramadhani';

    private const FIRST_NAMES = ['Ahmad', 'Siti', 'Muhammad', 'Nur', 'Rizky', 'Aisyah', 'Fajar', 'Dewi', 'Bayu', 'Putri', 'Dimas', 'Laila', 'Hafiz', 'Zahra', 'Ilham', 'Nabila', 'Raka', 'Salsabila', 'Yusuf', 'Kirana'];
    private const LAST_NAMES  = ['Pratama', 'Rahmawati', 'Hidayat', 'Lestari', 'Saputra', 'Maharani', 'Firmansyah', 'Anggraini', 'Nugroho', 'Wulandari', 'Ramadhan', 'Fitriani'];
    private const PLACES      = ['Kudus', 'Jepara', 'Pati', 'Demak', 'Semarang'];

    /**
     * Real villages around the first client (Kemendagri codes, D-111), so Statistik has meaningful
     * groups: village code, village, district, regency (display name). Province: Jawa Tengah (33).
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string}>
     */
    public const VILLAGES = [
        ['33.19.03.2006', 'Ploso', 'Jati', 'Kab. Kudus'],
        ['33.19.02.2021', 'Glantengan', 'Kota Kudus', 'Kab. Kudus'],
        ['33.18.10.1005', 'Pati Wetan', 'Pati', 'Kab. Pati'],
        ['33.19.03.2007', 'Jati Kulon', 'Jati', 'Kab. Kudus'],
        ['33.19.07.2001', 'Dersalam', 'Bae', 'Kab. Kudus'],
        ['33.20.06.1004', 'Bapangan', 'Jepara', 'Kab. Jepara'],
        ['33.19.03.2008', 'Getaspejaten', 'Jati', 'Kab. Kudus'],
        ['33.19.02.1001', 'Purwosari', 'Kota Kudus', 'Kab. Kudus'],
        ['33.21.11.1018', 'Bintoro', 'Demak', 'Kab. Demak'],
        ['33.19.01.2014', 'Kaliwungu', 'Kaliwungu', 'Kab. Kudus'],
        ['33.19.05.2009', 'Mejobo', 'Mejobo', 'Kab. Kudus'],
        ['33.18.11.2001', 'Wuwur', 'Gabus', 'Kab. Pati'],
        ['33.19.08.2001', 'Gribig', 'Gebog', 'Kab. Kudus'],
        ['33.19.07.2004', 'Gondangmanis', 'Bae', 'Kab. Kudus'],
        ['33.20.02.2003', 'Gerdu', 'Pecangaan', 'Kab. Jepara'],
        ['33.19.09.2018', 'Colo', 'Dawe', 'Kab. Kudus'],
        ['33.19.02.2012', 'Kramat', 'Kota Kudus', 'Kab. Kudus'],
        ['33.21.05.2001', 'Ploso', 'Karangtengah', 'Kab. Demak'],
        ['33.19.06.2009', 'Jekulo', 'Jekulo', 'Kab. Kudus'],
        ['33.18.10.1007', 'Pati Kidul', 'Pati', 'Kab. Pati'],
        ['33.19.04.2008', 'Undaan Kidul', 'Undaan', 'Kab. Kudus'],
        ['33.20.12.2001', 'Blimbingrejo', 'Nalumsari', 'Kab. Jepara'],
        ['33.21.06.2001', 'Doreng', 'Wonosalam', 'Kab. Demak'],
    ];

    /**
     * Addresses typed by hand before the region list existed (no codes), for Sekolah Asal → Alamat pendaftar.
     *
     * @var list<array{village: string, district: string, regency: string, province: string}>
     */
    private const TYPED_ADDRESSES = [
        ['village' => 'Ds. Getas Pejaten', 'district' => 'Kec. Jati', 'regency' => 'Kudus', 'province' => 'Jateng'],
        ['village' => 'Desa Contoh', 'district' => 'Bae', 'regency' => 'Kudus', 'province' => 'Jawa Tengah'],
        ['village' => 'Kel. Purwosari', 'district' => 'Kota Kudus', 'regency' => 'Kab. Kudus', 'province' => 'Jawa Tengah'],
    ];

    private const SCHOOLS = ['SD Negeri 1 Getas Pejaten', 'SD Negeri 2 Jati Kulon', 'MI NU Miftahul Huda', 'SD Muhammadiyah Kudus', 'SD Negeri 3 Purwosari', 'MI Qudsiyyah'];

    /**
     * The example applicant after run(): row, plain private-link token (only when created now) and whether it was created now.
     *
     * @var array{applicant: array<string, mixed>, token: string|null, created: bool}|null
     */
    public ?array $example = null;

    public function run(): void
    {
        $year  = (string) setting('Pendaftaran.academicYear');
        $waves = model(WaveModel::class);

        if ($waves->forYear($year) === []) {
            $day = static fn (int $offset): string => date('Y-m-d', strtotime(($offset >= 0 ? '+' : '') . $offset . ' days'));
            $waves->insert(['academic_year' => $year, 'name' => 'Gelombang 1', 'starts_on' => $day(-20), 'ends_on' => $day(25), 'quota' => 120, 'is_active' => 1, 'description' => 'Gelombang pertama (data contoh).']);
            $waves->insert(['academic_year' => $year, 'name' => 'Gelombang 2', 'starts_on' => $day(26), 'ends_on' => $day(70), 'quota' => 80, 'is_active' => 1]);
            $waves->insert(['academic_year' => $year, 'name' => 'Gelombang 3', 'starts_on' => $day(71), 'ends_on' => $day(110), 'quota' => null, 'is_active' => 1]);
        }

        if (model(ApplicantModel::class)->withDeleted()->where('academic_year', $year)->countAllResults() === 0) {
            $this->seedApplicants($waves);
        }

        $this->example = $this->seedCompleteExample($year);
        $this->report();
    }

    /**
     * About 30 applicants with a mix of methods and statuses, and two possible duplicates.
     */
    private function seedApplicants(WaveModel $waves): void
    {
        $schoolIds = $this->db->tableExists('ref_sekolah')
            ? array_map('intval', array_column($this->db->table('ref_sekolah')->select('id')->where('is_active', 1)->limit(20)->get()->getResultArray(), 'id'))
            : [];
        $service = new ApplicantService();
        $model   = model(ApplicantModel::class);
        $online  = (bool) setting('Pendaftaran.onlineOpen') && $waves->current() !== null;
        mt_srand(2027);

        for ($i = 0; $i < 30; $i++) {
            $first  = self::FIRST_NAMES[$i % count(self::FIRST_NAMES)];
            $last   = self::LAST_NAMES[($i * 7) % count(self::LAST_NAMES)];
            $gender = in_array($first, ['Siti', 'Nur', 'Aisyah', 'Dewi', 'Putri', 'Laila', 'Zahra', 'Nabila', 'Salsabila', 'Kirana'], true) ? 'P' : 'L';
            $birth  = sprintf('%d-%02d-%02d', (int) date('Y') - 12 + ($i % 2), 1 + ($i * 5) % 12, 1 + ($i * 11) % 28);
            $data   = [
                'full_name'   => $first . ' ' . $last,
                'gender'      => $gender,
                'birth_place' => self::PLACES[$i % count(self::PLACES)],
                'birth_date'  => $birth,
                'nisn'        => sprintf('01%08d', 12345 + $i * 97),
                'nik'         => $i % 3 === 0 ? null : sprintf('331901%010d', 4400000 + $i * 131),
                'religion'    => 'Islam',
                'address'     => 'Jl. Contoh No. ' . ($i + 1) . ', RT 0' . (1 + $i % 5) . '/RW 0' . (1 + $i % 3),
                ...($i % 10 === 9 ? self::TYPED_ADDRESSES[intdiv($i, 10)] : self::region(self::VILLAGES[$i % count(self::VILLAGES)])),
                'father_name'          => 'Bapak ' . $last,
                'mother_name'          => 'Ibu ' . $last,
                'has_guardian'         => '0',
                'parent_phone'         => sprintf('0812%08d', 30000000 + $i * 1717),
                'previous_school_id'   => $schoolIds !== [] && $i % 4 !== 0 ? $schoolIds[$i % count($schoolIds)] : null,
                'previous_school_name' => $schoolIds !== [] && $i % 4 !== 0 ? null : self::SCHOOLS[$i % count(self::SCHOOLS)],
                'graduation_year'      => (int) date('Y') + 1,
            ];

            // Two possible duplicates for the committee to review (same NISN / same name + birth date).
            if ($i === 28) {
                $data['nisn'] = sprintf('01%08d', 12345);
            } elseif ($i === 29) {
                $data['full_name']  = self::FIRST_NAMES[1] . ' ' . self::LAST_NAMES[7];
                $data['birth_date'] = sprintf('%d-%02d-%02d', (int) date('Y') - 11, 6, 12);
                $data['nik']        = null;
                $data['nisn']       = null;
            }

            $method = $online && $i % 3 !== 1 ? 'online' : 'offline';
            $row    = $service->register($data, $method)['applicant'];

            // Spread registration times over the days the first wave has been open.
            $at = strtotime('-' . (int) floor((29 - $i) * 0.6) . ' days ' . (8 + $i % 9) . ':' . sprintf('%02d', ($i * 13) % 60));
            $model->update((int) $row['id'], ['registered_at' => date('Y-m-d H:i:s', min(time(), (int) $at))]);

            if ($i % 5 === 0) {
                $service->changeStatus((int) $row['id'], 'verified', 'Data contoh');
            } elseif ($i === 17) {
                $service->changeStatus((int) $row['id'], 'cancelled', 'Data contoh');
            }
        }
    }

    /**
     * One applicant with every field filled in (all optional fields too), a guardian,
     * a school from the list, verified, with every document uploaded and valid.
     *
     * @return array{applicant: array<string, mixed>, token: string|null, created: bool}
     */
    private function seedCompleteExample(string $year): array
    {
        $model    = model(ApplicantModel::class);
        $existing = $model->withDeleted()->where('academic_year', $year)->where('full_name', self::EXAMPLE_NAME)->first();

        if ($existing !== null) {
            return ['applicant' => $existing, 'token' => null, 'created' => false];
        }

        $pick = static function (string $field, string $preferred, int $fallback): ?string {
            $options = ApplicantForm::options($field) ?? [];

            return in_array($preferred, $options, true) ? $preferred : ($options[min($fallback, count($options) - 1)] ?? null);
        };
        $school = $this->db->tableExists('ref_sekolah')
            ? $this->db->table('ref_sekolah')->select('id')->where('is_active', 1)->whereIn('level', ['SD', 'MI'])
                ->orderBy("CASE WHEN regency LIKE '%Kudus%' THEN 0 ELSE 1 END", '', false)
                ->orderBy("CASE WHEN name = 'MI NU MIFTAHUL HUDA' THEN 0 ELSE 1 END", '', false)->orderBy('id', 'ASC')->get(1)->getRowArray()
            : null;

        $data = [
            'full_name'   => self::EXAMPLE_NAME,
            'gender'      => 'P',
            'birth_place' => 'Kudus',
            'birth_date'  => ((int) date('Y') - 12) . '-03-14',
            'nisn'        => '0129876543',
            'nik'         => '3319015403140002',
            'kk_number'   => '3319010101140009',
            'religion'    => 'Islam',
            'phone'       => '085712345678',
            'address'     => 'Jl. Sunan Muria No. 17, RT 03/RW 05',
            ...self::region(self::VILLAGES[1]),
            'father_name'          => 'Ahmad Fauzan Ramadhan',
            'father_occupation'    => $pick('father_occupation', 'Wiraswasta', 8),
            'father_income'        => $pick('father_income', 'Rp2.000.000 – Rp4.999.999', 4),
            'mother_name'          => 'Siti Nurhaliza',
            'mother_occupation'    => $pick('mother_occupation', 'PNS/TNI/Polri', 4),
            'mother_income'        => $pick('mother_income', 'Rp2.000.000 – Rp4.999.999', 4),
            'has_guardian'         => '1',
            'guardian_name'        => 'Khadijah Umar',
            'guardian_relation'    => 'Nenek',
            'guardian_occupation'  => $pick('guardian_occupation', 'Pensiunan', 11),
            'guardian_income'      => $pick('guardian_income', 'Rp1.000.000 – Rp1.999.999', 3),
            'parent_phone'         => '081234567801',
            'parent_email'         => 'contoh.lengkap@example.com',
            'previous_school_id'   => $school === null ? null : (int) $school['id'],
            'previous_school_name' => $school === null ? 'MI NU Miftahul Huda' : null,
            'graduation_year'      => (int) date('Y') + 1,
            'notes'                => 'Data contoh: semua isian terisi, ada wali, dan semua berkas sudah diperiksa.',
        ];

        $service = new ApplicantService();
        $online  = $service->onlineClosedReason() === null;
        $result  = $service->register($data, $online ? 'online' : 'offline');
        $id      = (int) $result['applicant']['id'];

        // Online registrations carry no staff note; the example shows one anyway.
        $model->update($id, ['notes' => $data['notes']]);
        $this->attachDocuments($model->find($id));
        $service->changeStatus($id, 'verified', 'Data contoh lengkap');

        return ['applicant' => $model->find($id), 'token' => $result['token'], 'created' => true];
    }

    /**
     * Address fields (codes and display names) of a VILLAGES entry.
     *
     * @param array{0: string, 1: string, 2: string, 3: string} $village
     *
     * @return array<string, string>
     */
    public static function region(array $village): array
    {
        [$code, $name, $district, $regency] = $village;

        return [
            'village'       => $name,
            'district'      => $district,
            'regency'       => $regency,
            'province'      => 'Jawa Tengah',
            'province_code' => substr($code, 0, 2),
            'regency_code'  => substr($code, 0, 5),
            'district_code' => substr($code, 0, 8),
            'village_code'  => $code,
        ];
    }

    /**
     * Every active document type uploaded (generated sample images, a portrait for the photo)
     * and marked valid, when the Berkas module is on.
     *
     * @param array<string, mixed> $applicant
     */
    private function attachDocuments(array $applicant): void
    {
        if (! extension_loaded('gd') || ! class_exists(DocumentService::class) || ! service('modules')->isEnabled('Berkas') || ! $this->db->tableExists('applicant_documents')) {
            return;
        }

        if (model(ApplicantDocumentModel::class, false)->withDeleted()->where('applicant_id', $applicant['id'])->countAllResults() > 0) {
            return;
        }

        $staff   = $this->db->table('users')->select('id')->orderBy('id', 'ASC')->get(1)->getRowArray();
        $service = new DocumentService();

        foreach (model(DocumentTypeModel::class, false)->active() as $type) {
            $tmp = $this->sampleDocument((string) $type['name'], (string) $applicant['full_name'], (string) $type['code'] === 'foto');

            try {
                $document = $service->upload($applicant, $type, $tmp, 'contoh-' . $type['code'] . '.jpg', null);

                if ($staff !== null) {
                    $service->verify($document, 'valid', '', (int) $staff['id']);
                }
            } catch (UploadRejected) {
                // A type that does not take JPEG files stays empty.
            } finally {
                @unlink($tmp);
            }
        }
    }

    /**
     * A generated JPEG that is clearly not a real document; a simple portrait for the photo.
     */
    private function sampleDocument(string $title, string $name, bool $portrait): string
    {
        [$width, $height] = $portrait ? [480, 640] : [900, 600];
        $img              = imagecreatetruecolor($width, $height);
        $paper            = $portrait ? (int) imagecolorallocate($img, 214, 228, 240) : (int) imagecolorallocate($img, 245, 247, 250);
        $ink              = (int) imagecolorallocate($img, 31, 41, 55);
        imagefill($img, 0, 0, $paper);

        if ($portrait) {
            $skin = (int) imagecolorallocate($img, 224, 180, 150);
            $veil = (int) imagecolorallocate($img, 22, 101, 52);
            imagefilledellipse($img, 240, 300, 250, 300, $veil);
            imagefilledellipse($img, 240, 270, 150, 180, $skin);
            imagefilledrectangle($img, 90, 450, 390, 640, $veil);
            imagestring($img, 3, 20, 610, 'CONTOH PAS FOTO - BUKAN FOTO ASLI', $ink);
        } else {
            imagerectangle($img, 20, 20, $width - 21, $height - 21, $ink);
            imagestring($img, 5, 50, 60, 'CONTOH - ' . strtoupper($title), $ink);
            imagestring($img, 4, 50, 110, $name, $ink);
            imagestring($img, 3, 50, $height - 60, 'Data demo SPMB, bukan dokumen asli.', $ink);
        }

        $path = (string) tempnam(sys_get_temp_dir(), 'spmb');
        imagejpeg($img, $path, 85);

        return $path;
    }

    /**
     * Prints how to open the example (spark spmb:install --demo).
     */
    private function report(): void
    {
        if ($this->example === null || $this->silent || ! is_cli() || ENVIRONMENT === 'testing') {
            return;
        }

        $row = $this->example['applicant'];
        CLI::write('Complete example applicant: ' . $row['full_name'], 'green');
        CLI::write('  Registration number: ' . $row['registration_number'] . ', date of birth: ' . $row['birth_date'] . ' (for /cek-status)');

        if ($this->example['token'] !== null) {
            CLI::write('  Private link (shown once): ' . url_to('tautan', $this->example['token']));
        }
    }
}
