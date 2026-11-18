<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Pendaftaran\Services\ApplicantService;

/**
 * Development data (php spark spmb:install --demo): three waves for the
 * configured academic year, the first open today, and about 30 applicants
 * registered through ApplicantService. Idempotent: does nothing when the year
 * already has waves or applicants.
 */
class DemoSeeder extends Seeder
{
    private const FIRST_NAMES = ['Ahmad', 'Siti', 'Muhammad', 'Nur', 'Rizky', 'Aisyah', 'Fajar', 'Dewi', 'Bayu', 'Putri', 'Dimas', 'Laila', 'Hafiz', 'Zahra', 'Ilham', 'Nabila', 'Raka', 'Salsabila', 'Yusuf', 'Kirana'];
    private const LAST_NAMES  = ['Pratama', 'Rahmawati', 'Hidayat', 'Lestari', 'Saputra', 'Maharani', 'Firmansyah', 'Anggraini', 'Nugroho', 'Wulandari', 'Ramadhan', 'Fitriani'];
    private const PLACES      = ['Kudus', 'Jepara', 'Pati', 'Demak', 'Semarang'];
    private const DISTRICTS   = ['Jati', 'Kota Kudus', 'Bae', 'Kaliwungu', 'Mejobo', 'Gebog', 'Dawe'];
    private const SCHOOLS     = ['SD Negeri 1 Getas Pejaten', 'SD Negeri 2 Jati Kulon', 'MI NU Miftahul Huda', 'SD Muhammadiyah Kudus', 'SD Negeri 3 Purwosari', 'MI Qudsiyyah'];

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

        if (model(ApplicantModel::class)->withDeleted()->where('academic_year', $year)->countAllResults() > 0) {
            return;
        }

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
                'full_name'            => $first . ' ' . $last,
                'gender'               => $gender,
                'birth_place'          => self::PLACES[$i % count(self::PLACES)],
                'birth_date'           => $birth,
                'nisn'                 => sprintf('01%08d', 12345 + $i * 97),
                'nik'                  => $i % 3 === 0 ? null : sprintf('331901%010d', 4400000 + $i * 131),
                'religion'             => 'Islam',
                'address'              => 'Jl. Contoh No. ' . ($i + 1) . ', RT 0' . (1 + $i % 5) . '/RW 0' . (1 + $i % 3),
                'village'              => 'Desa Contoh ' . (1 + $i % 6),
                'district'             => self::DISTRICTS[$i % count(self::DISTRICTS)],
                'regency'              => 'Kudus',
                'province'             => 'Jawa Tengah',
                'father_name'          => 'Bapak ' . $last,
                'mother_name'          => 'Ibu ' . $last,
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
}
