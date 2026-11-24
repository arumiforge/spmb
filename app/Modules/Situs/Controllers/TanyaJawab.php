<?php

declare(strict_types=1);

namespace Modules\Situs\Controllers;

use App\Controllers\BaseController;
use App\Libraries\TranslationInput;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Situs\Models\SiteQuestionModel;

/**
 * Admin screen for the public /tanya-jawab page (D-072): add, edit, reorder,
 * hide and delete questions. Permission situs.kelola. Changes go through the
 * audited SiteQuestionModel.
 */
class TanyaJawab extends BaseController
{
    private SiteQuestionModel $questions;

    public function __construct()
    {
        $this->questions = model(SiteQuestionModel::class);
    }

    public function index(): string
    {
        $all = $this->questions->ordered();

        return view('Modules\Situs\Views\admin\tanya_jawab', [
            'title'       => lang('Situs.qaAdminTitle'),
            'subtitle'    => lang('Situs.qaAdminIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Situs.qaMenuGroup')], ['label' => lang('Situs.qaAdminTitle')]],
            'groups'      => SiteQuestionModel::group($all),
            'incomplete'  => count(array_filter($all, SiteQuestionModel::needsSchoolInput(...))),
        ]);
    }

    public function new(): string
    {
        return $this->form(null);
    }

    public function create(): RedirectResponse
    {
        $data = $this->posted();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data['sort_order'] = $this->questions->nextSortOrder();
        $this->questions->insert($data);
        $this->questions->normalise();

        return redirect()->route('situs.tanya-jawab.admin')->with('message', lang('Situs.qaSaved'));
    }

    public function edit(int $id): RedirectResponse|string
    {
        $question = $this->questions->find($id);

        if ($question === null) {
            return redirect()->route('situs.tanya-jawab.admin')->with('error', lang('Situs.qaNotFound'));
        }

        return $this->form($question);
    }

    public function update(int $id): RedirectResponse
    {
        if ($this->questions->find($id) === null) {
            return redirect()->route('situs.tanya-jawab.admin')->with('error', lang('Situs.qaNotFound'));
        }

        $data = $this->posted();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->questions->update($id, $data);
        $this->questions->normalise();

        return redirect()->route('situs.tanya-jawab.admin')->with('message', lang('Situs.qaSaved'));
    }

    /**
     * Moves a question one place up or down inside its topic (arah = naik | turun).
     */
    public function move(int $id): RedirectResponse
    {
        if ($this->questions->find($id) === null) {
            return redirect()->route('situs.tanya-jawab.admin')->with('error', lang('Situs.qaNotFound'));
        }

        $this->questions->moveQuestion($id, $this->request->getPost('arah') === 'naik');

        return redirect()->to(url_to('situs.tanya-jawab.admin') . '#pertanyaan-' . $id);
    }

    /**
     * Moves a whole topic one place up or down (topik, arah = naik | turun).
     */
    public function moveTopic(): RedirectResponse
    {
        $topic = trim((string) $this->request->getPost('topik'));
        $this->questions->moveTopic($topic, $this->request->getPost('arah') === 'naik');

        return redirect()->route('situs.tanya-jawab.admin');
    }

    /**
     * Shows (tampil = 1) or hides (tampil = 0) a question on the public page.
     */
    public function publish(int $id): RedirectResponse
    {
        $question = $this->questions->find($id);

        if ($question === null) {
            return redirect()->route('situs.tanya-jawab.admin')->with('error', lang('Situs.qaNotFound'));
        }

        $show = $this->request->getPost('tampil') === '1';

        if ($show && SiteQuestionModel::needsSchoolInput($question)) {
            return redirect()->route('situs.tanya-jawab.admin')->with('error', lang('Situs.qaPlaceholderError'));
        }

        if ((bool) $question['is_published'] !== $show) {
            $this->questions->update($id, ['is_published' => (int) $show]);
        }

        return redirect()->route('situs.tanya-jawab.admin')->with('message', lang($show ? 'Situs.qaShown' : 'Situs.qaHidden', [$question['question']]));
    }

    public function delete(int $id): RedirectResponse
    {
        if ($this->questions->find($id) === null) {
            return redirect()->route('situs.tanya-jawab.admin')->with('error', lang('Situs.qaNotFound'));
        }

        $this->questions->delete($id);

        return redirect()->route('situs.tanya-jawab.admin')->with('message', lang('Situs.qaDeleted'));
    }

    /**
     * @param array<string, mixed>|null $question
     */
    private function form(?array $question): string
    {
        $isNew = $question === null;

        return view('Modules\Situs\Views\admin\tanya_jawab_form', [
            'title'       => lang($isNew ? 'Situs.qaAddTitle' : 'Situs.qaEditTitle'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => lang('Situs.qaMenuGroup')],
                ['label' => lang('Situs.qaAdminTitle'), 'url' => 'admin/tanya-jawab'],
                ['label' => lang($isNew ? 'Situs.qaCrumbAdd' : 'Situs.qaCrumbEdit')],
            ],
            // ?topik= and ?pertanyaan= prefill a new question ("Jadikan tanya jawab" from Tanya Asisten, D-110).
            'question' => $question ?? ['topic' => (string) $this->request->getGet('topik'), 'question' => mb_substr(trim((string) $this->request->getGet('pertanyaan')), 0, 200), 'answer' => '', 'is_published' => 1],
            'topics'   => $this->questions->topics(),
            'isNew'    => $isNew,
        ]);
    }

    /**
     * Validated question data from the form, or null (errors in $this->validator).
     *
     * @return array<string, mixed>|null
     */
    private function posted(): ?array
    {
        $post = [
            'topic'        => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('topic'))),
            'question'     => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('question'))),
            'answer'       => trim(str_replace("\r\n", "\n", (string) $this->request->getPost('answer'))),
            'is_published' => (int) ($this->request->getPost('is_published') === '1'),
        ];

        $rules = [
            'topic'    => ['label' => lang('Situs.qaFieldTopic'), 'rules' => 'required|max_length[80]', 'errors' => ['required' => lang('Situs.qaTopicRequired'), 'max_length' => lang('Situs.qaTopicTooLong')]],
            'question' => ['label' => lang('Situs.qaFieldQuestion'), 'rules' => 'required|max_length[200]', 'errors' => ['required' => lang('Situs.qaQuestionRequired'), 'max_length' => lang('Situs.qaQuestionTooLong')]],
            'answer'   => ['label' => lang('Situs.qaFieldAnswer'), 'rules' => 'required|max_length[5000]', 'errors' => ['required' => lang('Situs.qaAnswerRequired'), 'max_length' => lang('Situs.qaAnswerTooLong')]],
        ];

        // Optional English/Arabic versions (D-109).
        [$translations, $translationRules] = TranslationInput::read($this->request, [
            'topic'    => ['max' => 80],
            'question' => ['max' => 200],
            'answer'   => ['max' => 5000, 'multiline' => true],
        ]);
        $post += $translations;

        if (! $this->validateData($post, $rules + $translationRules)) {
            return null;
        }

        if ($post['is_published'] === 1 && SiteQuestionModel::needsSchoolInput($post)) {
            $this->validator->setError('answer', lang('Situs.qaPlaceholderError'));

            return null;
        }

        return $post;
    }
}
