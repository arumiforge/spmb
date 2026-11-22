<?php
/**
 * Add or edit one question for /tanya-jawab.
 *
 * @var array<string, mixed> $question
 * @var list<string>         $topics
 * @var bool                 $isNew
 */
$action = $isNew ? url_to('situs.tanya-jawab.tambah') : url_to('situs.tanya-jawab.ubah', $question['id']);
$card   = 'rounded-lg border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-700 dark:bg-gray-800';
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>
    <section class="<?= $card ?>">
        <div class="grid grid-cols-1 gap-5">
            <?= view('partials/form_field', ['field' => ['name' => 'topic', 'label' => lang('Situs.qaFieldTopic'), 'value' => (string) $question['topic'], 'required' => true, 'help' => lang('Situs.qaFieldTopicHelp'), 'attrs' => ['maxlength' => '80', 'list' => 'topik-list', 'autocomplete' => 'off']]]) ?>
            <datalist id="topik-list">
                <?php foreach ($topics as $topic) : ?>
                    <option value="<?= esc($topic, 'attr') ?>"></option>
                <?php endforeach ?>
            </datalist>
            <?= view('partials/form_field', ['field' => ['name' => 'question', 'label' => lang('Situs.qaFieldQuestion'), 'value' => (string) $question['question'], 'required' => true, 'help' => lang('Situs.qaFieldQuestionHelp'), 'attrs' => ['maxlength' => '200']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'answer', 'type' => 'textarea', 'label' => lang('Situs.qaFieldAnswer'), 'value' => (string) $question['answer'], 'required' => true, 'help' => lang('Situs.qaFieldAnswerHelp'), 'attrs' => ['maxlength' => '5000', 'rows' => '8']]]) ?>
            <?= view('partials/form_field', ['field' => ['name' => 'is_published', 'type' => 'checkbox', 'label' => lang('Situs.qaFieldPublished'), 'value' => (string) (int) $question['is_published']]]) ?>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row">
        <a href="<?= url_to('situs.tanya-jawab.admin') ?>" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"><?= lang('App.cancel') ?></a>
        <button type="submit" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-800"><?= lang('Situs.qaSave') ?></button>
    </div>
</form>
<?= $this->endSection() ?>
