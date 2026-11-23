<?php
/**
 * @var array<string, array{title: string, description: string}> $groups
 * @var list<string>                                              $required
 * @var array<string, array{0: int, 1: int}>                      $counts
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= url_to('pengaturan.keamanan') ?>" class="rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
    <?= csrf_field() ?>
    <fieldset>
        <legend class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('Pengaturan.requiredRoles') ?></legend>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Pengaturan.requiredHelp') ?></p>
        <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
            <?php foreach ($groups as $key => $group) : ?>
                <li class="flex items-start gap-3 py-3">
                    <input id="role-<?= esc($key, 'attr') ?>" type="checkbox" name="roles[]" value="<?= esc($key, 'attr') ?>" <?= in_array($key, $required, true) ? 'checked' : '' ?>
                           class="mt-1 h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700">
                    <label for="role-<?= esc($key, 'attr') ?>" class="min-w-0 flex-1">
                        <span class="block text-sm font-medium text-gray-900 dark:text-white"><?= esc($group['title']) ?></span>
                        <span class="block text-sm text-gray-500 dark:text-gray-400"><?= esc($group['description']) ?></span>
                    </label>
                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400"><?= lang('Pengaturan.enabledCount', $counts[$key] ?? [0, 0]) ?></span>
                </li>
            <?php endforeach ?>
        </ul>
    </fieldset>
    <div class="mt-4 flex justify-end">
        <button type="submit" class="w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto"><?= lang('App.save') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
