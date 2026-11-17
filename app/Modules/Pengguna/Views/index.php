<?php
/**
 * @var list<CodeIgniter\Shield\Entities\User>                      $users
 * @var CodeIgniter\Pager\Pager                                      $pager
 * @var array<string, array{title: string, description: string}>    $groups
 * @var list<int>                                                    $twoFactorIds
 * @var array{q: string, peran: string, status: string}              $filters
 * @var bool                                                         $canCreate
 * @var bool                                                         $canEdit
 */
$input = 'block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400';
$roleTitle = static fn (string $g): string => $groups[$g]['title'] ?? $g;
$me = (int) auth()->id();
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('actions') ?>
<?php if ($canCreate) : ?>
    <a href="<?= url_to('pengguna.tambah') ?>" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800">
        <?= icon('plus', 'h-4 w-4') ?><?= lang('Pengguna.add') ?>
    </a>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <form method="get" action="<?= url_to('pengguna') ?>" class="grid grid-cols-1 gap-3 border-b border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_14rem_12rem_auto] dark:border-gray-700" role="search">
        <div class="relative sm:col-span-2 lg:col-span-1">
            <label for="q" class="sr-only"><?= lang('Pengguna.searchLabel') ?></label>
            <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-500 dark:text-gray-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" id="q" name="q" value="<?= esc($filters['q'], 'attr') ?>" placeholder="<?= lang('Pengguna.searchLabel') ?>" class="<?= $input ?> ps-9">
        </div>
        <div>
            <label for="peran" class="sr-only"><?= lang('Pengguna.roleFilter') ?></label>
            <select id="peran" name="peran" class="<?= $input ?>">
                <option value=""><?= lang('Pengguna.allRoles') ?></option>
                <?php foreach ($groups as $key => $group) : ?>
                    <option value="<?= esc($key, 'attr') ?>" <?= $filters['peran'] === $key ? 'selected' : '' ?>><?= esc($group['title']) ?></option>
                <?php endforeach ?>
            </select>
        </div>
        <div>
            <label for="status" class="sr-only"><?= lang('Pengguna.statusFilter') ?></label>
            <select id="status" name="status" class="<?= $input ?>">
                <option value=""><?= lang('Pengguna.allStatus') ?></option>
                <option value="aktif" <?= $filters['status'] === 'aktif' ? 'selected' : '' ?>><?= lang('Pengguna.active') ?></option>
                <option value="nonaktif" <?= $filters['status'] === 'nonaktif' ? 'selected' : '' ?>><?= lang('Pengguna.inactive') ?></option>
            </select>
        </div>
        <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
            <button type="submit" class="flex-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-700 lg:flex-none"><?= lang('Pengguna.filter') ?></button>
            <?php if (array_filter($filters) !== []) : ?>
                <a href="<?= url_to('pengguna') ?>" class="rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.reset') ?></a>
            <?php endif ?>
        </div>
    </form>

    <?php if ($users === []) : ?>
        <div class="p-4">
            <?= view('partials/empty_state', ['empty' => ['title' => lang('Pengguna.emptyTitle'), 'text' => lang('Pengguna.emptyText'), 'icon' => 'users']]) ?>
        </div>
    <?php else : ?>
        <!-- Table on md and up -->
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                    <tr>
                        <th scope="col" class="px-4 py-3"><?= lang('Pengguna.colName') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Pengguna.colRoles') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Pengguna.colStatus') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Pengguna.colTwoFactor') ?></th>
                        <th scope="col" class="px-4 py-3"><?= lang('Pengguna.colLastActive') ?></th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only"><?= lang('Pengguna.colActions') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u) : ?>
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-gray-700 dark:text-primary-300"><?= esc(initials($u->full_name ?: $u->username)) ?></span>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-gray-900 dark:text-white"><?= esc($u->full_name ?: $u->username) ?><?php if ((int) $u->id === $me) : ?> <span class="text-xs font-normal text-gray-500">(<?= lang('Pengguna.you') ?>)</span><?php endif ?></p>
                                        <p class="truncate text-gray-500 dark:text-gray-400"><?= esc($u->email) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3"><div class="flex flex-wrap gap-1"><?php foreach ($u->getGroups() ?? [] as $g) : ?><?= view('partials/status_badge', ['badge' => ['label' => $roleTitle($g), 'color' => 'primary']]) ?><?php endforeach ?></div></td>
                            <td class="px-4 py-3"><?= view('partials/status_badge', ['badge' => $u->isBanned() ? ['label' => lang('Pengguna.inactive'), 'color' => 'red'] : ['label' => lang('Pengguna.active'), 'color' => 'green']]) ?></td>
                            <td class="px-4 py-3"><?= view('partials/status_badge', ['badge' => in_array((int) $u->id, $twoFactorIds, true) ? ['label' => lang('Pengguna.on'), 'color' => 'green'] : ['label' => lang('Pengguna.off'), 'color' => 'gray']]) ?></td>
                            <td class="whitespace-nowrap px-4 py-3"><?= esc(tanggal_indo($u->last_active)) ?></td>
                            <td class="px-4 py-3 text-right">
                                <?php if ($canEdit) : ?>
                                    <a href="<?= url_to('pengguna.ubah', $u->id) ?>" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Pengguna.edit') ?></a>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Stacked cards below md -->
        <ul class="divide-y divide-gray-100 md:hidden dark:divide-gray-700">
            <?php foreach ($users as $u) : ?>
                <li class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900 dark:text-white"><?= esc($u->full_name ?: $u->username) ?><?php if ((int) $u->id === $me) : ?> <span class="text-xs font-normal text-gray-500">(<?= lang('Pengguna.you') ?>)</span><?php endif ?></p>
                            <p class="truncate text-sm text-gray-500 dark:text-gray-400"><?= esc($u->email) ?></p>
                        </div>
                        <?= view('partials/status_badge', ['badge' => $u->isBanned() ? ['label' => lang('Pengguna.inactive'), 'color' => 'red'] : ['label' => lang('Pengguna.active'), 'color' => 'green']]) ?>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-1"><?php foreach ($u->getGroups() ?? [] as $g) : ?><?= view('partials/status_badge', ['badge' => ['label' => $roleTitle($g), 'color' => 'primary']]) ?><?php endforeach ?></div>
                    <?php if ($canEdit) : ?>
                        <a href="<?= url_to('pengguna.ubah', $u->id) ?>" class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Pengguna.edit') ?></a>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 p-4 sm:flex-row dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengguna.total', [$pager->getTotal()]) ?></p>
            <?= $pager->links() ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
