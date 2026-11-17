<?php
/**
 * @var list<string> $codes
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<section class="max-w-2xl rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800">
    <?= view('auth/alert', ['alert' => ['type' => 'warning', 'text' => lang('Akun.tfCodesWarning')]]) ?>
    <ul class="grid grid-cols-1 gap-2 sm:grid-cols-2" aria-label="<?= lang('Akun.tfCodesTitle') ?>">
        <?php foreach ($codes as $code) : ?>
            <li class="rounded-lg bg-gray-100 px-4 py-2.5 text-center font-mono text-lg tracking-wider text-gray-900 dark:bg-gray-700 dark:text-white"><?= esc($code) ?></li>
        <?php endforeach ?>
    </ul>
    <div class="mt-6 flex flex-wrap gap-3" data-no-print>
        <button type="button" onclick="window.print()" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('Akun.tfCodesPrint') ?></button>
        <button type="button" data-copy="<?= esc(implode("\n", $codes), 'attr') ?>" data-copied-label="<?= lang('App.copied') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.copy') ?></button>
        <a href="<?= url_to('profil.dua-langkah') ?>" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300"><?= lang('Akun.tfCodesDone') ?></a>
    </div>
</section>
<?= $this->endSection() ?>
