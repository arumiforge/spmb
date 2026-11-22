<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="flex flex-col items-center rounded-lg border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-800">
    <?= icon('lock', 'mb-4 h-12 w-12 text-yellow-600 dark:text-yellow-400') ?>
    <p class="mb-6 max-w-md text-gray-600 dark:text-gray-300"><?= lang('App.deniedText') ?></p>
    <a href="<?= site_url('admin') ?>" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300"><?= lang('App.deniedAction') ?></a>
</div>
<?= $this->endSection() ?>
