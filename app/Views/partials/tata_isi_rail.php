<?php
/**
 * Sticky rail beside a settings form from 2xl (D-102): "Isi halaman" lists the
 * folding cards of the form (built by resources/js/lipatan.js from the cards'
 * titles), "Buka semua / Tutup semua" and the save button for the form.
 * Hidden below 2xl and without JavaScript (the form keeps its own save button).
 *
 *   <div class="tata-isi" data-lipatan-lingkup>
 *       <form id="form-sekolah" class="form-lebar">…</form>
 *       <?= view('partials/tata_isi_rail', ['form' => 'form-sekolah', 'save' => 'Simpan Pengaturan'], ['saveData' => false]) ?>
 *   </div>
 *
 * @var string      $form Id of the form the save button submits
 * @var string|null $save Label of the save button (none when null)
 */
?>
<aside class="tata-isi-rail sticky top-20 hidden 2xl:block" aria-label="<?= esc(lang('App.pageContents'), 'attr') ?>" data-isi-halaman data-error-label="<?= esc(lang('App.pageSectionError'), 'attr') ?>" hidden>
    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"><?= lang('App.pageContents') ?></p>
        <ol class="space-y-0.5 text-sm" data-isi-halaman-daftar></ol>
        <?= view('partials/lipatan_semua', ['class' => 'mt-3 justify-start border-t border-gray-100 pt-3 dark:border-gray-700'], ['saveData' => false]) ?>
    </div>
    <?php if (! empty($save)) : ?>
        <button type="submit" form="<?= esc($form, 'attr') ?>" class="mt-3 w-full rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= esc($save) ?></button>
    <?php endif ?>
</aside>
