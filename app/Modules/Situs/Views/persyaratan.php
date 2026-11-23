<?php
/**
 * Public requirements page (/persyaratan): document types and registration steps.
 *
 * @var array{documents: list<array<string, mixed>>, berkasOn: bool, kartuOn: bool, onlineOpen: bool, academicYear: string} $page
 */
$steps = [
    $page['documents'] === [] ? lang('Situs.stepPrepareNoDocs') : lang('Situs.stepPrepare'),
    lang('Situs.stepForm'),
    lang('Situs.stepNumber'),
];

if ($page['berkasOn']) {
    $steps[] = lang('Situs.stepUpload');
}

if ($page['kartuOn']) {
    $steps[] = lang('Situs.stepCard');
}

$steps[] = lang('Situs.stepTest');

$size = static function (int $kb): string {
    return $kb >= 1024 ? rtrim(rtrim(number_format($kb / 1024, 1, ',', '.'), '0'), ',') . ' MB' : $kb . ' KB';
};
$primary   = 'inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-6 py-3 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800';
$secondary = 'inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700';
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<div class="mx-auto max-w-screen-lg px-4 py-6 sm:py-10">
    <header class="mb-8">
        <h1 class="mb-1 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl dark:text-white"><?= lang('Situs.requirementsTitle') ?></h1>
        <p class="mb-3 text-lg font-semibold text-primary-700 dark:text-primary-400"><?= lang('Situs.academicYear', [$page['academicYear']]) ?></p>
        <p class="max-w-2xl text-lg text-gray-600 dark:text-gray-300"><?= lang('Situs.requirementsIntro') ?></p>
    </header>

    <section class="mb-10" aria-labelledby="berkas">
        <h2 id="berkas" class="mb-1 text-xl font-bold text-gray-900 dark:text-white"><?= lang('Situs.documentsTitle') ?></h2>
        <?php if ($page['documents'] === []) : ?>
            <div class="mt-4"><?= view('partials/empty_state', ['empty' => ['title' => lang('Situs.noDocumentsTitle'), 'text' => lang('Situs.noDocumentsText'), 'image' => 'state/kosong-data', 'imageAlt' => '']]) ?></div>
        <?php else : ?>
            <p class="mb-4 text-gray-600 dark:text-gray-400"><?= lang('Situs.documentsIntro') ?></p>
            <ul class="grid gap-4 md:grid-cols-2">
                <?php foreach ($page['documents'] as $doc) : ?>
                    <?php
                    $extensions = array_filter(array_map('trim', explode(',', strtoupper((string) $doc['extensions']))));
                    $extensions = array_values(array_unique(array_map(static fn (string $e): string => $e === 'JPEG' ? 'JPG' : $e, $extensions)));
                    ?>
                    <li class="motion-rise flex gap-4 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                        <?= icon('clipboard', 'h-7 w-7 shrink-0 text-primary-700 dark:text-primary-400') ?>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white"><?= esc($doc['name']) ?></h3>
                                <?= view('partials/status_badge', ['badge' => (int) $doc['is_required'] === 1 ? ['label' => lang('Situs.required'), 'color' => 'primary'] : ['label' => lang('Situs.optional'), 'color' => 'gray']]) ?>
                                <?php if ((int) $doc['may_follow'] === 1) : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Situs.mayFollow'), 'color' => 'yellow']]) ?>
                                <?php endif ?>
                            </div>
                            <?php if (! empty($doc['description'])) : ?>
                                <p class="mb-1 text-sm text-gray-600 dark:text-gray-300"><?= esc($doc['description']) ?></p>
                            <?php endif ?>
                            <?php if ((int) $doc['may_follow'] === 1) : ?>
                                <p class="mb-1 text-sm text-gray-600 dark:text-gray-300"><?= lang('Situs.mayFollowHelp') ?></p>
                            <?php endif ?>
                            <p class="text-sm text-gray-500 dark:text-gray-400"><?= esc(lang('Situs.fileRule', [implode(', ', $extensions), $size((int) $doc['max_size_kb'])])) ?></p>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <section class="mb-10" aria-labelledby="langkah">
        <h2 id="langkah" class="mb-4 text-xl font-bold text-gray-900 dark:text-white"><?= lang('Situs.stepsTitle') ?></h2>
        <ol class="space-y-3">
            <?php foreach ($steps as $i => $step) : ?>
                <li class="flex items-start gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white"><?= $i + 1 ?></span>
                    <p class="pt-1.5 text-gray-800 dark:text-gray-200"><?= esc($step) ?></p>
                </li>
            <?php endforeach ?>
        </ol>
    </section>

    <div class="flex flex-col gap-3 sm:flex-row">
        <?php if ($page['onlineOpen']) : ?>
            <a href="<?= site_url('daftar') ?>" class="tombol-daftar tombol-daftar-penuh"><span class="tombol-daftar-isi"><?= lang('Situs.register') ?><?= icon('arrow-right', 'h-5 w-5') ?></span></a>
        <?php endif ?>
        <a href="<?= url_to('situs.jadwal') ?>" class="<?= $page['onlineOpen'] ? $secondary : $primary ?>"><?= icon('calendar', 'h-5 w-5') ?><?= lang('Situs.seeSchedule') ?></a>
        <a href="<?= site_url('cek-status') ?>" class="<?= $secondary ?>"><?= icon('search', 'h-5 w-5') ?><?= lang('Situs.checkStatus') ?></a>
    </div>
</div>
<?= $this->endSection() ?>
