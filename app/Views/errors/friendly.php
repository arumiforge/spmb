<?php
/**
 * Friendly error page (404/500) in the admin layout for signed-in staff,
 * otherwise in the public layout. Rendered by App\Controllers\Errors.
 *
 * @var bool                                                                                  $staff
 * @var array{code: int, title: string, text: string, icon: string, whatsapp: string}         $error
 */
$primary   = 'inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800';
$secondary = 'inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-5 py-3 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
?>
<?= $this->extend($staff ? 'layouts/admin' : 'layouts/public') ?>

<?= $this->section('content') ?>
<div class="<?= $staff ? '' : 'px-4 py-10 sm:py-16' ?>">
    <div class="mx-auto flex max-w-xl flex-col items-center rounded-lg border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-800">
        <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-full <?= $error['code'] === 404 ? 'bg-primary-50 text-primary-600 dark:bg-gray-700 dark:text-primary-400' : 'bg-yellow-100 text-yellow-700 dark:bg-gray-700 dark:text-yellow-300' ?>"><?= icon($error['icon'], 'h-8 w-8') ?></span>
        <p class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"><?= lang('App.errorCode', [$error['code']]) ?></p>
        <?php if (! $staff) : ?>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= esc($error['title']) ?></h1>
        <?php endif ?>
        <p class="mt-3 max-w-md text-gray-600 dark:text-gray-300"><?= esc($error['text']) ?></p>
        <div class="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:justify-center">
            <?php if ($error['code'] === 500) : ?>
                <a href="<?= esc(current_url(), 'attr') ?>" class="<?= $primary ?>"><?= lang('App.reload') ?></a>
                <a href="<?= site_url($staff ? 'admin' : '/') ?>" class="<?= $secondary ?>"><?= $staff ? lang('App.backDashboard') : lang('App.backHome') ?></a>
            <?php else : ?>
                <a href="<?= site_url($staff ? 'admin' : '/') ?>" class="<?= $primary ?>"><?= icon($staff ? 'chart' : 'home', 'h-4 w-4') ?><?= $staff ? lang('App.backDashboard') : lang('App.backHome') ?></a>
                <a href="<?= site_url('panduan') ?>" class="<?= $secondary ?>"><?= icon('book', 'h-4 w-4') ?><?= lang('App.readGuide') ?></a>
            <?php endif ?>
            <?php if ($error['whatsapp'] !== '') : ?>
                <a href="https://wa.me/<?= esc($error['whatsapp'], 'attr') ?>" rel="noopener" class="<?= $secondary ?>"><?= lang('App.contactWhatsapp') ?></a>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
