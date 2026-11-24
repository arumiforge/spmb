<?php
/**
 * Step indicator for a form filled in steps (wizard, resources/js/wizard.js).
 * Desktop: numbered circles joined by a line, labels under the circles.
 * Phones: "Langkah 2 dari 5 · Sekolah asal", a slim progress bar and small numbered dots.
 * Done steps show a check, the current one a ring, steps with errors a red dot.
 * Hidden until JavaScript turns the form into a wizard (.is-wizard on the closest [data-wizard]).
 *
 * view('partials/stepper', ['stepper' => [
 *     'label'   => 'Langkah pengisian formulir',
 *     'steps'   => [['id' => 'langkah-1', 'title' => 'Data calon murid', 'short' => 'Calon murid', 'error' => false], ...],
 *     'current' => 1,
 * ]])
 *
 * @var array{label: string, steps: list<array{id: string, title: string, short?: string, error?: bool}>, current?: int} $stepper
 */
$steps   = array_values($stepper['steps']);
$total   = count($steps);
$current = max(1, min($total, (int) ($stepper['current'] ?? 1)));
$texts   = lang('App.stepper');
?>
<nav class="stepper wizard-saja" aria-label="<?= esc($stepper['label'], 'attr') ?>" data-stepper
     data-teks-hitung="<?= esc($texts['counter'], 'attr') ?>" data-teks-selesai="<?= esc($texts['done'], 'attr') ?>"
     data-teks-sekarang="<?= esc($texts['current'], 'attr') ?>" data-teks-nanti="<?= esc($texts['upcoming'], 'attr') ?>"
     data-teks-salah="<?= esc($texts['error'], 'attr') ?>">
    <p class="stepper-ringkas" aria-hidden="true">
        <span data-stepper-hitung><?= esc(lang('App.stepper.counter', [$current, $total])) ?></span>
        <span class="stepper-ringkas-titik">·</span>
        <span class="stepper-ringkas-judul" data-stepper-judul><?= esc($steps[$current - 1]['title'] ?? '') ?></span>
    </p>
    <div class="stepper-kemajuan" aria-hidden="true"><span data-stepper-kemajuan style="--kemajuan: <?= round($current / max(1, $total) * 100, 2) ?>%"></span></div>
    <ol class="stepper-daftar" style="--stepper-jumlah: <?= $total ?>">
        <?php foreach ($steps as $i => $step) : ?>
            <?php
            $n     = $i + 1;
            $state = $n < $current ? 'selesai' : ($n === $current ? 'aktif' : 'nanti');
            $error = ! empty($step['error']);
            $note  = $error ? $texts['error'] : ['selesai' => $texts['done'], 'aktif' => $texts['current'], 'nanti' => $texts['upcoming']][$state];
            ?>
            <li class="stepper-item is-<?= $state ?><?= $error ? ' has-error' : '' ?>" data-stepper-item="<?= $n ?>">
                <a href="#<?= esc($step['id'], 'attr') ?>" class="stepper-tautan" data-wizard-ke="<?= $n ?>"<?= $n === $current ? ' aria-current="step"' : '' ?>>
                    <span class="langkah-bulat"><span class="langkah-angka"><?= $n ?></span></span>
                    <span class="stepper-label"><?= esc($step['short'] ?? $step['title']) ?></span>
                    <span class="sr-only" data-stepper-status>, <?= esc($note) ?></span>
                </a>
            </li>
        <?php endforeach ?>
    </ol>
</nav>
