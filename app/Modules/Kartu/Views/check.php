<?php
/**
 * Public card check page opened by the QR code (/kartu/<token>). Minimal data only.
 *
 * @var array{state: string, number?: string|null, name?: string|null, wave?: string|null, staffLink?: string|null, applicantId?: int|null} $check
 *      state: valid | cancelled | invalid | throttled
 */
$styles = [
    'valid'     => ['icon' => 'check', 'ring' => 'text-green-600 dark:text-green-400', 'title' => lang('Kartu.validTitle'), 'text' => lang('Kartu.validText', [(string) setting('School.name')])],
    'cancelled' => ['icon' => 'warning', 'ring' => 'text-yellow-600 dark:text-yellow-400', 'title' => lang('Kartu.invalidTitle'), 'text' => lang('Kartu.cancelledText')],
    'invalid'   => ['icon' => 'ban', 'ring' => 'text-red-600 dark:text-red-400', 'title' => lang('Kartu.invalidTitle'), 'text' => lang('Kartu.invalidText')],
    'throttled' => ['icon' => 'clock', 'ring' => 'text-gray-600 dark:text-gray-400', 'title' => lang('Kartu.tooManyTitle'), 'text' => lang('Kartu.tooManyChecks')],
];
$style = $styles[$check['state']] ?? $styles['invalid'];
$facts = array_filter([
    lang('Kartu.number') => $check['number'] ?? null,
    lang('Kartu.name')   => $check['name'] ?? null,
    lang('Kartu.wave')   => $check['wave'] ?? null,
], static fn ($value): bool => $value !== null);
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('content') ?>
<section class="mx-auto max-w-lg px-4 py-8 sm:py-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm sm:p-8 dark:border-gray-700 dark:bg-gray-800">
        <?= icon($style['icon'], 'mx-auto mb-4 h-14 w-14 ' . $style['ring']) ?>
        <p class="mb-1 text-sm font-medium text-gray-500 dark:text-gray-400"><?= lang('Kartu.checkTitle') ?></p>
        <h1 class="mb-3 text-2xl font-bold text-gray-900 sm:text-3xl dark:text-white"><?= esc($style['title']) ?></h1>
        <p class="mb-6 text-gray-600 dark:text-gray-300"><?= esc($style['text']) ?></p>

        <?php if ($facts !== []) : ?>
            <dl class="divide-y divide-gray-200 rounded-lg border border-gray-200 text-start dark:divide-gray-700 dark:border-gray-700">
                <?php foreach ($facts as $label => $value) : ?>
                    <div class="flex flex-col gap-0.5 px-4 py-3 sm:flex-row sm:justify-between sm:gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400"><?= esc($label) ?></dt>
                        <dd class="font-semibold break-words text-gray-900 sm:text-end dark:text-white"><?= esc($value) ?></dd>
                    </div>
                <?php endforeach ?>
            </dl>
        <?php endif ?>

        <?php if (! empty($check['staffLink'])) : ?>
            <a href="<?= esc($check['staffLink'], 'attr') ?>" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 sm:w-auto dark:focus:ring-primary-800">
                <?= icon('user', 'h-5 w-5') ?><?= lang('Kartu.openApplicant') ?>
            </a>
        <?php endif ?>

        <?php if (! empty($check['applicantId'])) : ?>
            <?= module_panels('card_check', ['applicantId' => $check['applicantId'], 'cardState' => $check['state']]) ?>
        <?php endif ?>
    </div>
    <p class="mt-4 flex items-start justify-center gap-2 text-center text-xs text-gray-500 dark:text-gray-400">
        <?= icon('lock', 'h-4 w-4 shrink-0') ?><?= lang('Kartu.checkPrivacy') ?>
    </p>
</section>
<?= $this->endSection() ?>
