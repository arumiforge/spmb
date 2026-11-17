<?php

declare(strict_types=1);

namespace Modules\Riwayat\Controllers;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;
use Modules\Riwayat\Libraries\AuditPresenter;

/**
 * Audit log viewer (audit.view): filterable, paginated, read-only.
 *
 * Filters live in the query string so a filtered view can be shared:
 * pengguna (user id or "sistem"), kegiatan (event), data (subject type),
 * id (subject id), dari / sampai (YYYY-MM-DD).
 */
class Riwayat extends BaseController
{
    private const PER_PAGE = 25;

    private bool $dateInvalid = false;

    public function index(): string
    {
        $model     = model(AuditLogModel::class);
        $presenter = AuditPresenter::fromConfig();
        $filters   = $this->filters();

        $builder = $model->select('audit_logs.*');

        if ($filters['pengguna'] === 'sistem') {
            $builder->where('audit_logs.user_id IS NULL');
        } elseif ($filters['pengguna'] !== '') {
            $builder->where('audit_logs.user_id', (int) $filters['pengguna']);
        }

        if ($filters['kegiatan'] !== '') {
            $builder->where('audit_logs.event', $filters['kegiatan']);
        }

        if ($filters['data'] !== '') {
            $builder->where('audit_logs.subject_type', $filters['data']);
        }

        if ($filters['id'] !== '') {
            $builder->where('audit_logs.subject_id', $filters['id']);
        }

        if ($filters['dari'] !== '') {
            $builder->where('audit_logs.created_at >=', $filters['dari'] . ' 00:00:00');
        }

        if ($filters['sampai'] !== '') {
            $builder->where('audit_logs.created_at <=', $filters['sampai'] . ' 23:59:59');
        }

        $rows = $builder->orderBy('audit_logs.id', 'DESC')->paginate(self::PER_PAGE);

        return view('Modules\Riwayat\Views\index', [
            'title'       => lang('Riwayat.title'),
            'subtitle'    => lang('Riwayat.intro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Riwayat Perubahan']],
            'rows'        => $rows,
            'pager'       => $model->pager,
            'presenter'   => $presenter,
            'filters'     => $filters,
            'dateInvalid' => $this->dateInvalid,
            'userNames'   => $this->userNames([
                ...array_column($rows, 'user_id'),
                ...array_column(array_filter($rows, static fn (array $r): bool => $r['subject_type'] === 'users'), 'subject_id'),
                ...($filters['data'] === 'users' ? [$filters['id']] : []),
            ]),
            'userOptions'    => $this->userOptions(),
            'eventOptions'   => $this->distinct('event'),
            'subjectOptions' => $this->distinct('subject_type'),
        ]);
    }

    public function show(int $id): string
    {
        $row = model(AuditLogModel::class)->find($id);

        if ($row === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $presenter = AuditPresenter::fromConfig();
        $ids       = [$row['user_id']];

        if ($row['subject_type'] === 'users') {
            $ids[] = $row['subject_id'];
        }

        return view('Modules\Riwayat\Views\show', [
            'title'       => lang('Riwayat.detailTitle'),
            'subtitle'    => $presenter->summary($row),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Riwayat Perubahan', 'url' => 'admin/riwayat'], ['label' => 'Detail']],
            'row'         => $row,
            'presenter'   => $presenter,
            'changes'     => $presenter->changes($row),
            'userNames'   => $this->userNames($ids),
        ]);
    }

    /**
     * @return array{pengguna: string, kegiatan: string, data: string, id: string, dari: string, sampai: string}
     */
    private function filters(): array
    {
        $get  = fn (string $key, int $max): string => mb_substr(trim((string) $this->request->getGet($key)), 0, $max);
        $user = $get('pengguna', 20);

        $filters = [
            'pengguna' => $user === 'sistem' || ctype_digit($user) ? $user : '',
            'kegiatan' => $get('kegiatan', 50),
            'data'     => $get('data', 100),
            'id'       => $get('id', 64),
            'dari'     => $this->date($get('dari', 10)),
            'sampai'   => $this->date($get('sampai', 10)),
        ];

        // A reversed range is almost always a slip: swap rather than show nothing.
        if ($filters['dari'] !== '' && $filters['sampai'] !== '' && $filters['dari'] > $filters['sampai']) {
            [$filters['dari'], $filters['sampai']] = [$filters['sampai'], $filters['dari']];
        }

        return $filters;
    }

    private function date(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            $this->dateInvalid = true;

            return '';
        }

        return $value;
    }

    /**
     * @param list<mixed> $ids
     *
     * @return array<int, string> user id => display name
     */
    private function userNames(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return [];
        }

        $names = [];

        foreach (db_connect()->table('users')->select('id, username, full_name')->whereIn('id', $ids)->get()->getResultArray() as $user) {
            $names[(int) $user['id']] = (string) ($user['full_name'] ?: $user['username']);
        }

        return $names;
    }

    /**
     * Staff who appear in the log, for the user filter.
     *
     * @return array<int, string>
     */
    private function userOptions(): array
    {
        $rows = db_connect()->table('users')
            ->select('users.id, users.username, users.full_name')
            ->whereIn('users.id', static fn ($sub) => $sub->select('user_id')->distinct()->from('audit_logs')->where('user_id IS NOT NULL'))
            ->orderBy('users.full_name', 'ASC')
            ->get()->getResultArray();

        $options = [];

        foreach ($rows as $user) {
            $options[(int) $user['id']] = (string) ($user['full_name'] ?: $user['username']);
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private function distinct(string $column): array
    {
        $rows = db_connect()->table('audit_logs')->select($column)->distinct()->where($column . ' IS NOT NULL')->orderBy($column, 'ASC')->get()->getResultArray();

        return array_map('strval', array_column($rows, $column));
    }
}
