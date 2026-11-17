<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="flex flex-col items-center rounded-lg border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-800">
    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-yellow-100 text-yellow-700 dark:bg-gray-700 dark:text-yellow-300"><?= icon('lock', 'h-7 w-7') ?></span>
    <p class="mb-6 max-w-md text-gray-600 dark:text-gray-300"><?= lang('App.deniedText') ?></p>
    <a href="<?= site_url('admin') ?>" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300"><?= lang('App.deniedAction') ?></a>
</div>
<?= $this->endSection() ?>
