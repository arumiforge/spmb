<?php

use Modules\Berkas\Models\DocumentTypeModel;

/**
 * "Unduh contoh" link of a document type. Prints nothing unless the type is
 * active, its example is switched on and the file exists. Used in the
 * applicant's document panel and on /persyaratan.
 *
 * @var array{type: array<string, mixed>, class?: string} $link
 */
$type = $link['type'];

if (! DocumentTypeModel::templateShown($type)) {
    return;
}
?>
<a href="<?= url_to('berkas.contoh', $type['code']) ?>" download class="<?= esc($link['class'] ?? 'inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-400', 'attr') ?>">
    <?= icon('download', 'h-4 w-4 shrink-0') ?><span><?= esc(DocumentTypeModel::templateLabel($type)) ?><span class="sr-only"> <?= esc(lang('Berkas.templateFor', [$type['name']])) ?></span></span>
</a>
