<?php
/**
 * Applicant list with search and filters (query string, shareable).
 *
 * @var list<array<string, mixed>> $applicants
 * @var CodeIgniter\Pager\Pager    $pager
 * @var array<int|string, string>  $waves     id => name
 * @var array<string, string>      $filters
 * @var bool                       $canCreate
 */

use Modules\Pendaftaran\Models\ApplicantModel;

$input    = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$cols     = lang('Pendaftaran.cols');
$waveName = static fn (array $a): string => $a['wave_id'] === null ? lang('Pendaftaran.noWave') : ($waves[$a['wave_id']] ?? lang('Pendaftaran.noWave'));
$badge    = static fn (string $type, string $code): string => view('partials/status_badge', ['badge' => status_badge_for($type, $code)]);
$dupBadge = view('partials/status_badge', ['badge' => ['label' => lang('Pendaftaran.duplicateBadge'), 'color' => 'yellow']]);
$filtered = array_filter($filters, static fn (string $v): bool => $v !== '') !== [];
// The lists stay folded on phones unless one of them is in use.
$folded = ($filters['gelombang'] ?? '') === '' && ($filters['cara'] ?? '') === '' && ($filters['status'] ?? '') === '' && ($filters['berkas'] ?? '') === '';
$select   = static function (string $name, string $all, array $options) use ($filters, $input): string {
    $html = '<label for="' . $name . '" class="sr-only">' . esc($all) . '</label><select id="' . $name . '" name="' . $name . '" class="' . $input . '"><option value="">' . esc($all) . '</option>';

    foreach ($options as $value => $label) {
        $html .= '<option value="' . esc((string) $value, 'attr') . '"' . ((string) $value === $filters[$name] ? ' selected' : '') . '>' . esc($label) . '</option>';
    }

    return $html . '</select>';
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<?php if ($canCreate) : ?>
    <a href="<?= url_to('pendaftar.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
        <?= icon('plus', 'h-4 w-4') ?><?= lang('Pendaftaran.add') ?>
    </a>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('pendaftar') ?>" class="grid grid-cols-1 gap-3 border-b border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-[minmax(0,2fr)_repeat(4,minmax(0,1fr))] dark:border-gray-700" role="search">
        <div class="relative sm:col-span-2 lg:col-span-4 2xl:col-span-1">
            <label for="q" class="sr-only"><?= lang('Pendaftaran.searchLabel') ?></label>
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="<?= lang('Pendaftaran.searchLabel') ?>" class="<?= $input ?> ps-9">
        </div>
        <?php // Phones: the four lists fold behind one button so the results start higher up the screen. ?>
        <button type="button" data-collapse-toggle="pendaftar-saring" aria-controls="pendaftar-saring" aria-expanded="<?= $folded ? 'false' : 'true' ?>"
                class="flex w-full items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 sm:col-span-2 md:hidden dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
            <span><?= lang('Pendaftaran.filterToggle') ?></span><?= icon('chevron-down', 'h-4 w-4') ?>
        </button>
        <div id="pendaftar-saring" class="<?= $folded ? 'hidden ' : '' ?>sm:col-span-2 md:contents">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:contents">
                <div><?= $select('gelombang', lang('Pendaftaran.filterWave'), $waves + ['tanpa' => lang('Pendaftaran.filterNoWave')]) ?></div>
                <div><?= $select('cara', lang('Pendaftaran.filterMethod'), lang('Status.method')) ?></div>
                <div><?= $select('status', lang('Pendaftaran.filterStatus'), lang('Status.applicant')) ?></div>
                <div><?= $select('berkas', lang('Pendaftaran.filterDocs'), lang('Status.documents')) ?></div>
            </div>
        </div>
        <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-4 2xl:col-span-5">
            <label class="inline-flex flex-1 items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="ganda" value="1" <?= $filters['ganda'] === '1' ? 'checked' : '' ?> class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                <?= lang('Pendaftaran.filterDuplicate') ?>
            </label>
            <?php if ($filtered) : ?>
                <a href="<?= url_to('pendaftar') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.reset') ?></a>
            <?php endif ?>
            <button type="submit" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700"><?= lang('Pendaftaran.filter') ?></button>
        </div>
    </form>

    <?php if ($applicants === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => $filtered
                ? ['title' => lang('Pendaftaran.emptySearchTitle'), 'text' => lang('Pendaftaran.emptySearchText'), 'icon' => 'search', 'image' => 'state/kosong-pencarian', 'imageAlt' => lang('App.altNoResults')]
                : ['title' => lang('Pendaftaran.emptyTitle'), 'text' => lang('Pendaftaran.emptyText'), 'icon' => 'users', 'image' => 'state/kosong-pendaftar', 'fallback' => 'state/kosong-data', 'imageAlt' => lang('App.altNoData'), 'action' => $canCreate ? ['label' => lang('Pendaftaran.add'), 'url' => 'admin/pendaftar/tambah'] : null],
            ]) ?>
        </div>
    <?php else : ?>
        <div class="relative hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= $cols['number'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['name'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['wave'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['method'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['status'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['docs'] ?></th>
                        <th scope="col" class="px-4 py-3"><?= $cols['registered'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applicants as $a) : ?>
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-900 dark:text-white"><?= esc($a['registration_number']) ?></td>
                            <td class="px-4 py-3">
                                <a href="<?= url_to('pendaftar.detail', $a['id']) ?>" class="font-medium text-gray-900 hover:text-primary-700 hover:underline dark:text-white dark:hover:text-primary-400"><?= esc($a['full_name']) ?></a>
                                <p class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    <?php if (! empty($a['nisn'])) : ?><span class="block">NISN <?= esc($a['nisn']) ?></span><?php endif ?>
                                    <?php if (! empty($a['nik'])) : ?><span class="block">NIK <?= esc(ApplicantModel::maskNik($a['nik'])) ?></span><?php endif ?>
                                </p>
                                <?php if ((int) $a['possible_duplicate'] === 1) : ?><div class="mt-1"><?= $dupBadge ?></div><?php endif ?>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3"><?= esc($waveName($a)) ?></td>
                            <td class="px-4 py-3"><?= $badge('method', $a['method']) ?></td>
                            <td class="px-4 py-3"><?= $badge('applicant', $a['status']) ?></td>
                            <td class="px-4 py-3"><?= $badge('documents', $a['document_status']) ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= esc(tanggal_indo($a['registered_at'])) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <?php // Cards in two columns from sm until the table takes over at lg, so tablets have no empty right half. ?>
        <ul class="grid grid-cols-1 sm:grid-cols-2 lg:hidden">
            <?php foreach ($applicants as $a) : ?>
                <li class="border-b border-gray-100 last:border-b-0 sm:odd:border-r sm:[&:nth-last-child(2):nth-child(odd)]:border-b-0 dark:border-gray-700">
                    <a href="<?= url_to('pendaftar.detail', $a['id']) ?>" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-gray-900 dark:text-white"><?= esc($a['full_name']) ?></p>
                                <p class="font-mono text-sm text-gray-600 dark:text-gray-300"><?= esc($a['registration_number']) ?></p>
                            </div>
                            <?= $badge('applicant', $a['status']) ?>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= esc($waveName($a)) ?> · <?= esc(tanggal_indo($a['registered_at'])) ?></p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <?= $badge('method', $a['method']) ?>
                            <?= $badge('documents', $a['document_status']) ?>
                            <?php if ((int) $a['possible_duplicate'] === 1) : ?><?= $dupBadge ?><?php endif ?>
                        </div>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pendaftaran.total', [number_format($pager->getTotal(), 0, ',', '.')]) ?></p>
            <?= $pager->links() ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
