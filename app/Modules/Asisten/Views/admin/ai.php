<?php
/**
 * Pengaturan → Koneksi AI (asisten.ai, Super Admin): AI settings and the connections list.
 * API keys never reach this page: only their last 4 characters (key_hint).
 *
 * @var array<string, string>               $values
 * @var array<string, array{0: int, 1: int}> $limits
 * @var list<array<string, mixed>>          $providers
 * @var int                                 $usedToday
 * @var bool                                $widgetOn
 * @var bool                                $hasKey
 */
$card    = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2      = 'text-lg font-semibold text-gray-900 dark:text-white';
$btn     = 'inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$primary = 'inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800';
$iconBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-gray-700';
$on      = old('aiEnabled', $values['aiEnabled']) === '1';
$now     = date('Y-m-d H:i:s');
$last    = count($providers) - 1;
$move    = static function (int $id, string $direction, bool $disabled, string $label) use ($iconBtn): string {
    return '<form method="post" action="' . esc(url_to('asisten.koneksi.pindah', $id), 'attr') . '">' . csrf_field()
        . '<input type="hidden" name="arah" value="' . $direction . '">'
        . '<button type="submit" class="' . $iconBtn . '"' . ($disabled ? ' disabled' : '') . ' title="' . esc($label, 'attr') . '">'
        . '<span class="sr-only">' . esc($label) . '</span>' . icon('chevron-down', 'h-4 w-4' . ($direction === 'naik' ? ' rotate-180' : '')) . '</button></form>';
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="space-y-6">
    <?php if (! $hasKey) : ?>
        <div class="flex items-start gap-3 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-gray-800 dark:text-red-400" role="alert">
            <?= icon('triangle-alert', 'mt-0.5 h-5 w-5 shrink-0') ?><p><?= lang('Asisten.noEncryptionKey') ?></p>
        </div>
    <?php endif ?>
    <?php if (! $widgetOn) : ?>
        <div class="flex items-start gap-3 rounded-lg bg-gray-100 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300" role="status">
            <?= icon('info', 'mt-0.5 h-5 w-5 shrink-0') ?><p><?= lang('Asisten.aiWidgetOff') ?> <a href="<?= url_to('asisten.admin') ?>" class="font-medium underline hover:no-underline"><?= lang('Asisten.adminTitle') ?></a></p>
        </div>
    <?php endif ?>

    <form method="post" action="<?= url_to('asisten.ai') ?>" class="form-lebar">
        <?= csrf_field() ?>
        <section class="<?= $card ?>" aria-labelledby="ai-pengaturan">
            <h2 id="ai-pengaturan" class="<?= $h2 ?> mb-1"><?= lang('Asisten.aiSettingsTitle') ?></h2>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.aiUsedToday', [number_format($usedToday, 0, ',', '.'), number_format((int) $values['dailySiteLimit'], 0, ',', '.')]) ?></p>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <input type="hidden" name="aiEnabled" value="0">
                    <label class="inline-flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="aiEnabled" value="1" class="peer sr-only" <?= $on ? 'checked' : '' ?>>
                        <span class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full bg-gray-200 peer-checked:bg-primary-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white rtl:peer-checked:after:-translate-x-full dark:bg-gray-700 dark:peer-focus:ring-primary-800"></span>
                        <span>
                            <span class="block text-sm font-medium text-gray-900 dark:text-white"><?= lang('Asisten.fieldAiEnabled') ?></span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.fieldAiEnabledHelp') ?></span>
                        </span>
                    </label>
                </div>
                <?php foreach ($limits as $key => [$min, $max]) : ?>
                    <?= view('partials/form_field', ['field' => [
                        'name'     => $key,
                        'label'    => lang('Asisten.field' . ucfirst($key)),
                        'type'     => 'number',
                        'value'    => $values[$key],
                        'required' => true,
                        'help'     => lang('Asisten.field' . ucfirst($key) . 'Help'),
                        'attrs'    => ['min' => (string) $min, 'max' => (string) $max, 'step' => '1', 'inputmode' => 'numeric'],
                    ]]) ?>
                <?php endforeach ?>
                <div class="md:col-span-2">
                    <?= view('partials/form_field', ['field' => ['name' => 'extraInstructions', 'type' => 'textarea', 'rows' => 4, 'label' => lang('Asisten.fieldExtraInstructions'), 'value' => $values['extraInstructions'], 'help' => lang('Asisten.fieldExtraInstructionsHelp'), 'attrs' => ['maxlength' => '2000']]]) ?>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="<?= $primary ?> w-full sm:w-auto"><?= lang('Asisten.save') ?></button>
            </div>
        </section>
    </form>

    <section class="rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800" aria-labelledby="ai-koneksi">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-5 sm:flex-row sm:items-start sm:justify-between sm:px-6 dark:border-gray-700">
            <div>
                <h2 id="ai-koneksi" class="<?= $h2 ?>"><?= lang('Asisten.providersTitle') ?></h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('Asisten.providersIntro') ?></p>
            </div>
            <a href="<?= url_to('asisten.koneksi.tambah') ?>" class="<?= $primary ?> shrink-0 px-4!"><?= icon('plus', 'h-4 w-4') ?><?= lang('Asisten.providerAdd') ?></a>
        </div>
        <?php if ($providers === []) : ?>
            <p class="p-5 text-sm text-gray-500 sm:px-6 dark:text-gray-400"><?= lang('Asisten.providersEmpty') ?></p>
        <?php else : ?>
            <ol class="divide-y divide-gray-100 dark:divide-gray-700">
                <?php foreach ($providers as $i => $provider) : ?>
                    <?php
                    $id      = (int) $provider['id'];
                    $active  = (int) $provider['is_active'] === 1;
                    $resting = $provider['rested_until'] !== null && (string) $provider['rested_until'] > $now;
                    ?>
                    <li id="koneksi-<?= $id ?>" class="flex scroll-mt-24 flex-col gap-3 p-4 sm:px-6 lg:flex-row lg:items-center">
                        <div class="order-last flex shrink-0 gap-1 lg:order-first">
                            <?= $move($id, 'naik', $i === 0, lang('Asisten.moveUp') . ': ' . $provider['name']) ?>
                            <?= $move($id, 'turun', $i === $last, lang('Asisten.moveDown') . ': ' . $provider['name']) ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-medium text-gray-900 dark:text-white">
                                <?= esc($provider['name']) ?>
                                <?php if (! $active) : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Asisten.providerBadgeOff'), 'color' => 'gray']]) ?>
                                <?php elseif ($resting) : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Asisten.providerBadgeResting', [tanggal_indo((string) $provider['rested_until'], true)]), 'color' => 'yellow']]) ?>
                                <?php else : ?>
                                    <?= view('partials/status_badge', ['badge' => ['label' => lang('Asisten.providerBadgeOn'), 'color' => 'green']]) ?>
                                <?php endif ?>
                            </p>
                            <p class="mt-1 break-all text-sm text-gray-600 dark:text-gray-300"><span dir="ltr"><?= esc($provider['base_url']) ?></span> · <span dir="ltr"><?= esc($provider['model']) ?></span></p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <?= lang('Asisten.keyHint', [esc((string) ($provider['key_hint'] ?? ''))]) ?>
                                · <?= lang('Asisten.providerMeta', [(int) $provider['timeout_seconds'], (int) $provider['max_tokens'], esc((string) $provider['temperature'])]) ?>
                                <?php if ($provider['last_used_at'] !== null) : ?> · <?= lang('Asisten.lastUsed', [tanggal_indo((string) $provider['last_used_at'], true)]) ?><?php endif ?>
                            </p>
                            <?php if ($provider['last_error'] !== null) : ?>
                                <p class="mt-1 text-sm text-red-700 dark:text-red-400"><?= lang('Asisten.lastError', [tanggal_indo((string) $provider['last_error_at'], true), esc((string) $provider['last_error'])]) ?></p>
                            <?php endif ?>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2 lg:order-last">
                            <form method="post" action="<?= url_to('asisten.koneksi.tes', $id) ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="<?= $btn ?>"><?= icon('refresh-cw', 'h-4 w-4') ?><?= lang('Asisten.providerTest') ?></button>
                            </form>
                            <a href="<?= url_to('asisten.koneksi.ubah', $id) ?>" class="<?= $btn ?>"><?= icon('pencil', 'h-4 w-4') ?><?= lang('Asisten.edit') ?></a>
                            <form method="post" action="<?= url_to('asisten.koneksi.aktifkan', $id) ?>">
                                <?= csrf_field() ?><input type="hidden" name="aktif" value="<?= $active ? '0' : '1' ?>">
                                <button type="submit" class="<?= $btn ?>"><?= icon($active ? 'eye-off' : 'eye', 'h-4 w-4') ?><?= lang($active ? 'Asisten.providerTurnOff' : 'Asisten.providerTurnOn') ?></button>
                            </form>
                            <form method="post" action="<?= url_to('asisten.koneksi.hapus', $id) ?>" data-confirm="<?= esc(lang('Asisten.providerDeleteConfirm', [$provider['name']]), 'attr') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="<?= $btn ?> text-red-700 dark:text-red-400"><?= icon('trash', 'h-4 w-4') ?><?= lang('Asisten.delete') ?></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>
    </section>

    <section class="<?= $card ?>" aria-labelledby="ai-cara">
        <h2 id="ai-cara" class="<?= $h2 ?> flex items-center gap-2"><?= icon('shield-check', 'h-5 w-5 shrink-0 text-primary-600 dark:text-primary-400') ?><?= lang('Asisten.aiHowTitle') ?></h2>
        <ul class="mt-4 list-disc space-y-2 ps-5 text-sm text-gray-700 dark:text-gray-300">
            <?php foreach ((array) lang('Asisten.aiHowItems') as $item) : ?>
                <li><?= esc($item) ?></li>
            <?php endforeach ?>
        </ul>
    </section>
</div>
<?= $this->endSection() ?>
