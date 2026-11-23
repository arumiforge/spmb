<?php
/**
 * @var list<array<string, mixed>>                                                                    $rows
 * @var CodeIgniter\Pager\Pager                                                                       $pager
 * @var Modules\Riwayat\Libraries\AuditPresenter                                                      $presenter
 * @var array{pengguna: string, kegiatan: string, data: string, id: string, dari: string, sampai: string} $filters
 * @var bool                                                                                          $dateInvalid
 * @var array<int, string>                                                                            $userNames
 * @var array<int, string>                                                                            $userOptions
 * @var list<string>                                                                                  $eventOptions
 * @var list<string>                                                                                  $subjectOptions
 */
$input    = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$label    = 'mb-1 block text-sm font-medium text-gray-900 dark:text-white';
$who      = static fn (array $r): string => $r['user_id'] === null ? lang('Riwayat.system') : ($userNames[(int) $r['user_id']] ?? lang('Riwayat.deletedUser', [$r['user_id']]));
$subject  = static fn (array $r): string => $presenter->subjectName($r['subject_type'], $r['subject_id'], $userNames);
$badge    = static fn (array $r): string => view('partials/status_badge', ['badge' => ['label' => $presenter->eventLabel((string) $r['event']), 'color' => $presenter->eventColor((string) $r['event'])]]);
$filtered = array_filter($filters, static fn (string $v): bool => $v !== '') !== [];
$withoutId = array_filter(['pengguna' => $filters['pengguna'], 'kegiatan' => $filters['kegiatan'], 'data' => $filters['data'], 'dari' => $filters['dari'], 'sampai' => $filters['sampai']], static fn (string $v): bool => $v !== '');
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('riwayat') ?>" class="border-b border-gray-200 p-4 dark:border-gray-700" role="search">
        <button type="button" data-collapse-toggle="riwayat-saring" aria-controls="riwayat-saring" aria-expanded="<?= $filtered ? 'true' : 'false' ?>"
                class="flex w-full items-center justify-between rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 md:hidden dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
            <span class="flex items-center gap-2"><?= icon('search', 'h-4 w-4') ?><?= lang('Riwayat.filterToggle') ?></span><?= icon('chevron-down', 'h-4 w-4') ?>
        </button>
        <div id="riwayat-saring" class="<?= $filtered ? '' : 'hidden' ?> mt-3 space-y-3 md:mt-0 md:grid md:gap-3 md:space-y-0 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-[repeat(5,minmax(0,1fr))_auto]">
            <div>
                <label for="pengguna" class="<?= $label ?>"><?= lang('Riwayat.filterUser') ?></label>
                <select id="pengguna" name="pengguna" class="<?= $input ?>">
                    <option value=""><?= lang('Riwayat.allUsers') ?></option>
                    <?php foreach ($userOptions as $id => $name) : ?>
                        <option value="<?= $id ?>" <?= $filters['pengguna'] === (string) $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach ?>
                    <option value="sistem" <?= $filters['pengguna'] === 'sistem' ? 'selected' : '' ?>><?= lang('Riwayat.system') ?></option>
                </select>
            </div>
            <div>
                <label for="kegiatan" class="<?= $label ?>"><?= lang('Riwayat.filterEvent') ?></label>
                <select id="kegiatan" name="kegiatan" class="<?= $input ?>">
                    <option value=""><?= lang('Riwayat.allEvents') ?></option>
                    <?php foreach ($eventOptions as $event) : ?>
                        <option value="<?= esc($event, 'attr') ?>" <?= $filters['kegiatan'] === $event ? 'selected' : '' ?>><?= esc($presenter->eventLabel($event)) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div>
                <label for="data" class="<?= $label ?>"><?= lang('Riwayat.filterSubject') ?></label>
                <select id="data" name="data" class="<?= $input ?>">
                    <option value=""><?= lang('Riwayat.allSubjects') ?></option>
                    <?php foreach ($subjectOptions as $type) : ?>
                        <option value="<?= esc($type, 'attr') ?>" <?= $filters['data'] === $type ? 'selected' : '' ?>><?= esc($presenter->subjectLabel($type)) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div>
                <label for="dari" class="<?= $label ?>"><?= lang('Riwayat.filterFrom') ?></label>
                <input type="date" id="dari" name="dari" value="<?= esc($filters['dari'], 'attr') ?>" class="<?= $input ?>">
            </div>
            <div>
                <label for="sampai" class="<?= $label ?>"><?= lang('Riwayat.filterTo') ?></label>
                <input type="date" id="sampai" name="sampai" value="<?= esc($filters['sampai'], 'attr') ?>" class="<?= $input ?>">
            </div>
            <div class="flex items-end gap-2 md:col-span-2 lg:col-span-1">
                <?php if ($filters['id'] !== '') : ?><input type="hidden" name="id" value="<?= esc($filters['id'], 'attr') ?>"><?php endif ?>
                <button type="submit" class="flex-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 2xl:flex-none dark:focus:ring-primary-800"><?= lang('Riwayat.filter') ?></button>
                <?php if ($filtered) : ?>
                    <a href="<?= url_to('riwayat') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.reset') ?></a>
                <?php endif ?>
            </div>
        </div>
        <?php if ($filters['id'] !== '') : ?>
            <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <span class="inline-flex items-center gap-2 rounded-full bg-primary-50 py-1 pe-1 ps-3 text-primary-800 dark:bg-gray-700 dark:text-primary-300">
                    <?= esc($presenter->subjectName($filters['data'] ?: null, $filters['id'], $userNames)) ?>
                    <a href="<?= esc(url_to('riwayat') . ($withoutId === [] ? '' : '?' . http_build_query($withoutId)), 'attr') ?>" class="rounded-full p-1 hover:bg-primary-100 dark:hover:bg-gray-600" aria-label="<?= esc(lang('App.close'), 'attr') ?>"><?= icon('x', 'h-3.5 w-3.5') ?></a>
                </span>
            </p>
        <?php endif ?>
        <?php if ($dateInvalid) : ?>
            <p class="mt-3 flex items-center gap-2 text-sm text-yellow-800 dark:text-yellow-300" role="status"><?= icon('warning', 'h-4 w-4') ?><?= lang('Riwayat.dateInvalid') ?></p>
        <?php endif ?>
    </form>

    <?php if ($rows === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => $filtered
                ? ['title' => lang('Riwayat.emptyFilteredTitle'), 'text' => lang('Riwayat.emptyFilteredText'), 'icon' => 'search', 'image' => 'state/kosong-pencarian', 'imageAlt' => lang('App.altNoResults')]
                : ['title' => lang('Riwayat.emptyTitle'), 'text' => lang('Riwayat.emptyText'), 'icon' => 'clock', 'image' => 'state/kosong-riwayat', 'imageAlt' => lang('App.altHistory')]]) ?>
        </div>
    <?php else : ?>
        <!-- Table on md and up -->
        <div class="relative hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('Riwayat.colTime') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Riwayat.colUser') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Riwayat.colEvent') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Riwayat.colSubject') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Riwayat.colSummary') ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= lang('Riwayat.colActions') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r) : ?>
                        <tr class="border-b border-gray-100 align-top last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-4 py-3"><?= esc(tanggal_indo($r['created_at'], true)) ?></td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"><?= esc($who($r)) ?></td>
                            <td class="px-4 py-3"><?= $badge($r) ?></td>
                            <td class="px-4 py-3"><?= esc($subject($r)) ?></td>
                            <td class="max-w-md px-4 py-3"><span class="line-clamp-2"><?= esc($presenter->summary($r)) ?></span></td>
                            <td class="px-4 py-3 text-right">
                                <a href="<?= url_to('riwayat.detail', $r['id']) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('eye', 'h-4 w-4') ?><?= lang('Riwayat.view') ?></a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Stacked cards below md -->
        <ul class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            <?php foreach ($rows as $r) : ?>
                <li>
                    <a href="<?= url_to('riwayat.detail', $r['id']) ?>" class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <?php // Badge above the summary: side by side it squeezed long summaries into half the width of a phone. ?>
                        <div><?= $badge($r) ?></div>
                        <p class="mt-1.5 font-medium text-gray-900 dark:text-white"><?= esc($presenter->summary($r)) ?></p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300"><?= esc($subject($r)) ?></p>
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <?= icon('clock', 'h-3.5 w-3.5') ?><?= esc(tanggal_indo($r['created_at'], true)) ?> · <?= esc($who($r)) ?>
                        </p>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Riwayat.total', [$pager->getTotal()]) ?></p>
            <?= $pager->links() ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
