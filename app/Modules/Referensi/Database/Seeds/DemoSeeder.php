<?php

declare(strict_types=1);

namespace Modules\Referensi\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Modules\Referensi\Libraries\SchoolImporter;

/**
 * About 20 sample previous schools around Kudus for development
 * (php spark spmb:install --demo). Idempotent: upsert by NPSN. The NPSNs start
 * with 99 so they never collide with real ones imported later.
 */
class DemoSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string}> NPSN, name, level, status, district
     */
    public const SCHOOLS = [
        ['99010001', 'SD NEGERI 1 JATI KULON', 'SD', 'negeri', 'Jati'],
        ['99010002', 'SD NEGERI 2 JATI KULON', 'SD', 'negeri', 'Jati'],
        ['99010003', 'SD NEGERI 1 GETAS PEJATEN', 'SD', 'negeri', 'Jati'],
        ['99010004', 'SD NEGERI 1 PLOSO', 'SD', 'negeri', 'Jati'],
        ['99010005', 'SD NEGERI 1 KRAMAT', 'SD', 'negeri', 'Kota Kudus'],
        ['99010006', 'SD NEGERI 2 PURWOSARI', 'SD', 'negeri', 'Kota Kudus'],
        ['99010007', 'SD MUHAMMADIYAH 1 KUDUS', 'SD', 'swasta', 'Kota Kudus'],
        ['99010008', 'SD ISLAM TERPADU AL MA\'RUF', 'SD', 'swasta', 'Kota Kudus'],
        ['99010009', 'SD NEGERI 1 BAE', 'SD', 'negeri', 'Bae'],
        ['99010010', 'SD NEGERI 3 DERSALAM', 'SD', 'negeri', 'Bae'],
        ['99010011', 'SD NEGERI 1 MEJOBO', 'SD', 'negeri', 'Mejobo'],
        ['99010012', 'SD NEGERI 2 GEBOG', 'SD', 'negeri', 'Gebog'],
        ['99010013', 'SD NEGERI 1 KALIWUNGU', 'SD', 'negeri', 'Kaliwungu'],
        ['99010014', 'SD NEGERI 4 DAWE', 'SD', 'negeri', 'Dawe'],
        ['99020001', 'MI NU MIFTAHUL HUDA', 'MI', 'swasta', 'Jati'],
        ['99020002', 'MI NU BANAT', 'MI', 'swasta', 'Kota Kudus'],
        ['99020003', 'MI NEGERI 1 KUDUS', 'MI', 'negeri', 'Kota Kudus'],
        ['99020004', 'MI NU TARBIYATUL WATHON', 'MI', 'swasta', 'Mejobo'],
        ['99020005', 'MI NU NURUL ULUM', 'MI', 'swasta', 'Jekulo'],
        ['99020006', 'MI MUHAMMADIYAH KUDUS', 'MI', 'swasta', 'Bae'],
    ];

    public function run(): void
    {
        $rows = [];

        foreach (self::SCHOOLS as $i => [$npsn, $name, $level, $status, $district]) {
            $rows[$i + 1] = [
                'npsn'      => $npsn,
                'name'      => $name,
                'level'     => $level,
                'ownership' => $status,
                'province'  => 'Jawa Tengah',
                'regency'   => 'Kab. Kudus',
                'district'  => $district,
            ];
        }

        // Two schools outside the configured province, to show the ordering.
        $rows[] = ['npsn' => '99030001', 'name' => 'SD NEGERI 1 JATI', 'level' => 'SD', 'ownership' => 'negeri', 'province' => 'Jawa Timur', 'regency' => 'Kab. Tuban', 'district' => 'Jatirogo'];

        (new SchoolImporter($this->db))->import($rows, 'manual');
    }
}
