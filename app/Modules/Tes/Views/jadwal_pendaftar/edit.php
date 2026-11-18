<?php
/**
 * Give or move one applicant's test seat, and take them out of the schedule.
 *
 * @var array<string, mixed>       $applicant
 * @var array<string, mixed>|null  $current   TestSchedule::forApplicant()
 * @var list<array<string, mixed>> $choices   Scheduler::choicesFor()
 * @var list<array<string, mixed>> $changes   TestScheduleChangeModel::latestForApplicant()
 * @var bool                       $canRemove
 */

use Modules\Tes\Libraries\TestSchedule;

$card     = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
$h2       = 'mb-4 text-lg font-semibold text-gray-900 dark:text-white';
$button   = 'inline-flex w-full items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium sm:w-auto';
$isMove   = $current !== null;
$errors   = session('errors');
$error    = is_array($errors) ? ($errors['sesi'] ?? null) : null;
$selected = (string) old('sesi');
$today    = date('Y-m-d');
$groups   = ['upcoming' => [], 'past' => []];

foreach ($choices as $s) {
    $groups[(string) $s['test_date'] < $today ? 'past' : 'upcoming'][] = $s;
}

$option = static function (array $s) use ($selected): string {
    $text = trim(($s['label'] ? $s['label'] . ' · ' : '') . TestSchedule::sessionLabel($s));
    $text = $s['free'] > 0 ? lang('TesPenjadwalan.sessionOption', [$text, $s['free']]) : lang('TesPenjadwalan.sessionOptionFull', [$text]);

    return '<option value="' . (int) $s['id'] . '"' . ($selected === (string) $s['id'] ? ' selected' : '') . '>' . esc($text) . '</option>';
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="<?= $card ?>">
            <h2 class="<?= $h2 ?>"><?= lang($isMove ? 'TesPenjadwalan.seatTitleMove' : 'TesPenjadwalan.seatTitleAssign') ?></h2>

            <?php if ($applicant['status'] === 'cancelled') : ?>
                <p class="flex gap-2 text-sm text-red-700 dark:text-red-400"><?= icon('ban', 'h-5 w-5 shrink-0') ?><?= lang('TesPenjadwalan.cancelledNoSeat') ?></p>
            <?php elseif ($choices === []) : ?>
                <p class="flex gap-2 text-sm text-gray-700 dark:text-gray-300"><?= icon('info', 'h-5 w-5 shrink-0 text-primary-600') ?><?= lang('TesPenjadwalan.noChoices') ?></p>
            <?php else : ?>
                <form method="post" action="<?= url_to('tes.jadwal-pendaftar', $applicant['id']) ?>" class="space-y-5">
                    <?= csrf_field() ?>
                    <div>
                        <label for="sesi" class="mb-2 block text-sm font-medium <?= $error !== null ? 'text-red-700 dark:text-red-500' : 'text-gray-900 dark:text-white' ?>"><?= lang('TesPenjadwalan.chooseSession') ?></label>
                        <select id="sesi" name="sesi" required class="block w-full rounded-lg border <?= $error !== null ? 'border-red-500 bg-red-50' : 'border-gray-300 bg-gray-50' ?> p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"<?= $error !== null ? ' aria-invalid="true" aria-describedby="sesi-error"' : '' ?>>
                            <option value=""><?= lang('TesPenjadwalan.chooseSessionNone') ?></option>
                            <?php if ($groups['past'] === []) : ?>
                                <?php foreach ($groups['upcoming'] as $s) : ?><?= $option($s) ?><?php endforeach ?>
                            <?php else : ?>
                                <optgroup label="<?= esc(lang('TesPenjadwalan.upcomingGroup'), 'attr') ?>">
                                    <?php foreach ($groups['upcoming'] as $s) : ?><?= $option($s) ?><?php endforeach ?>
                                </optgroup>
                                <optgroup label="<?= esc(lang('TesPenjadwalan.pastGroup'), 'attr') ?>">
                                    <?php foreach (array_reverse($groups['past']) as $s) : ?><?= $option($s) ?><?php endforeach ?>
                                </optgroup>
                            <?php endif ?>
                        </select>
                        <?php if ($error !== null) : ?><p id="sesi-error" class="mt-2 text-sm text-red-600 dark:text-red-500"><?= esc($error) ?></p><?php endif ?>
                    </div>
                    <?= view('partials/form_field', ['field' => [
                        'name'     => 'alasan',
                        'type'     => 'textarea',
                        'rows'     => 2,
                        'label'    => lang('TesPenjadwalan.reasonLabel'),
                        'help'     => lang($isMove ? 'TesPenjadwalan.reasonHelpMove' : 'TesPenjadwalan.reasonHelpAssign'),
                        'required' => $isMove,
                        'attrs'    => ['maxlength' => '255'] + ($isMove ? ['minlength' => '5'] : []),
                    ]], ['saveData' => false]) ?>
                    <div>
                        <?= view('partials/form_field', ['field' => ['name' => 'lebih_kuota', 'type' => 'checkbox', 'label' => lang('TesPenjadwalan.overQuota')]], ['saveData' => false]) ?>
                        <p class="ms-7 mt-1 text-sm text-gray-500 dark:text-gray-400"><?= lang('TesPenjadwalan.overQuotaHelp') ?></p>
                    </div>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <a href="<?= url_to('pendaftar.detail', $applicant['id']) ?>" class="<?= $button ?> border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
                        <button type="submit" class="<?= $button ?> bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= icon('calendar', 'h-4 w-4') ?><?= lang($isMove ? 'TesPenjadwalan.moveButton' : 'TesPenjadwalan.assignButton') ?></button>
                    </div>
                </form>
            <?php endif ?>
        </section>

        <section class="<?= $card ?>">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesPenjadwalan.recentChanges') ?></h2>
                <a href="<?= url_to('tes.riwayat-jadwal') . '?q=' . rawurlencode((string) $applicant['registration_number']) ?>" class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"><?= lang('TesPenjadwalan.allChanges') ?></a>
            </div>
            <?= view('Modules\Tes\Views\jadwal_pendaftar\changes', ['changes' => $changes], ['saveData' => false]) ?>
        </section>
    </div>

    <div class="space-y-6">
        <section class="<?= $card ?>">
            <h2 class="<?= $h2 ?>"><?= lang('TesPenjadwalan.currentSchedule') ?></h2>
            <?= view('Modules\Tes\Views\jadwal_pendaftar\schedule', ['schedule' => $current, 'applicant' => $applicant], ['saveData' => false]) ?>
        </section>

        <?php if ($canRemove) : ?>
            <section class="rounded-lg border border-red-200 bg-white p-5 sm:p-6 dark:border-red-900 dark:bg-gray-800">
                <h2 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white"><?= lang('TesPenjadwalan.removeTitle') ?></h2>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400"><?= lang('TesPenjadwalan.removeIntro') ?></p>
                <form method="post" action="<?= url_to('tes.jadwal-pendaftar.hapus', $applicant['id']) ?>" class="space-y-4" data-confirm="<?= esc(lang('TesPenjadwalan.removeConfirm', [$applicant['full_name']]), 'attr') ?>">
                    <?= csrf_field() ?>
                    <?= view('partials/form_field', ['field' => ['name' => 'alasan', 'id' => 'alasan-hapus', 'label' => lang('TesPenjadwalan.reasonLabel'), 'help' => lang('TesPenjadwalan.removeReasonHelp'), 'required' => true, 'attrs' => ['minlength' => '5', 'maxlength' => '255']]], ['saveData' => false]) ?>
                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 bg-white px-5 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700"><?= lang('TesPenjadwalan.removeButton') ?></button>
                </form>
            </section>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
