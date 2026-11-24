<?php

declare(strict_types=1);

namespace Modules\Asisten\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Locales;
use App\Libraries\TranslationInput;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Asisten\Libraries\AiGateway;
use Modules\Asisten\Libraries\AvatarStorage;
use Modules\Asisten\Libraries\QuickChoices;
use Modules\Situs\Libraries\FooterLinks;
use Modules\Situs\Libraries\SiteLink;
use RuntimeException;

/**
 * Website → Tanya Asisten (asisten.atur, Admin Sekolah, D-110): switch the widget on or off, the
 * greeting (with optional English/Arabic), the assistant picture and the quick choices. Changes are
 * audited as settings_updated (groups Asisten and AsistenQuickChoices).
 */
class Pengaturan extends BaseController
{
    private const LABEL_MAX  = 40;
    private const ANSWER_MAX = 2000;

    public function index(): string
    {
        $nav     = public_nav();
        $choices = [];

        foreach (QuickChoices::all() as $choice) {
            $choices[] = $choice + ['available' => match ($choice['kind']) {
                'link'     => FooterLinks::available((string) ($choice['url'] ?? ''), $nav),
                'whatsapp' => (string) setting('School.whatsapp') !== '',
                default    => true,
            }];
        }

        $user = auth()->user();

        return view('Modules\Asisten\Views\admin\pengaturan', [
            'title'       => lang('Asisten.adminTitle'),
            'subtitle'    => lang('Asisten.adminIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Website'], ['label' => lang('Asisten.adminTitle')]],
            'values'      => [
                'enabled'     => setting('Asisten.enabled') ? '1' : '0',
                'greeting'    => (string) setting('Asisten.greeting'),
                'greeting_en' => (string) setting('Asisten.greeting_en'),
                'greeting_ar' => (string) setting('Asisten.greeting_ar'),
            ],
            'greetingDefault' => lang('Asisten.greetingDefault', [(string) setting('School.name')]),
            'picture'         => AvatarStorage::url(),
            'choices'         => $choices,
            'custom'          => QuickChoices::isCustom(),
            'full'            => count($choices) >= QuickChoices::MAX,
            'aiOn'            => AiGateway::available(),
            'canAi'           => $user !== null && $user->can('asisten.ai'),
            'hasWhatsapp'     => (string) setting('School.whatsapp') !== '',
        ]);
    }

    public function update(): RedirectResponse
    {
        $post = [
            'greeting' => trim(str_replace("\r\n", "\n", (string) $this->request->getPost('greeting'))),
        ];
        $rules = [
            'greeting' => ['label' => lang('Asisten.fieldGreeting'), 'rules' => 'permit_empty|max_length[300]'],
        ];
        [$translations, $translationRules] = TranslationInput::read($this->request, ['greeting' => ['max' => 300, 'multiline' => true]], false);
        $post += $translations;

        $picture = $this->request->getFile('avatar');

        if (! $this->validateData($post, $rules + $translationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $old = [];
        $new = [];

        if ($picture !== null && $picture->getError() !== UPLOAD_ERR_NO_FILE) {
            try {
                $stored = AvatarStorage::storeUpload($picture);
            } catch (RuntimeException $e) {
                return redirect()->back()->withInput()->with('errors', ['avatar' => $e->getMessage()]);
            }

            $old['avatar'] = AvatarStorage::replace($stored);
            $new['avatar'] = $stored;
        }

        $values = ['enabled' => $this->request->getPost('enabled') === '1'] + $post;

        foreach ($values as $key => $value) {
            $current = setting('Asisten.' . $key);

            if ($current !== $value) {
                $old[$key] = $current;
                $new[$key] = $value;
                setting('Asisten.' . $key, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'Asisten', $old, $new, 'Pengaturan Tanya Asisten diubah');
        }

        return redirect()->route('asisten.admin')->with('message', lang('Asisten.saved'));
    }

    public function removePicture(): RedirectResponse
    {
        $current = (string) setting('Asisten.avatar');

        if ($current !== '') {
            AvatarStorage::replace('');
            service('audit')->log('settings_updated', 'settings', 'Asisten', ['avatar' => $current], ['avatar' => ''], 'Gambar Tanya Asisten dihapus');
        }

        return redirect()->route('asisten.admin')->with('message', lang('Asisten.pictureRemoved'));
    }

    public function newChoice(): RedirectResponse|string
    {
        if (count(QuickChoices::all()) >= QuickChoices::MAX) {
            return redirect()->route('asisten.admin')->with('error', lang('Asisten.choicesFull', [QuickChoices::MAX]));
        }

        return $this->choiceForm(null, ['kind' => 'link', 'label' => '', 'url' => '', 'answer' => '']);
    }

    public function createChoice(): RedirectResponse
    {
        $choices = QuickChoices::all();

        if (count($choices) >= QuickChoices::MAX) {
            return redirect()->route('asisten.admin')->with('error', lang('Asisten.choicesFull', [QuickChoices::MAX]));
        }

        $choice = $this->postedChoice();

        if ($choice === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $choices[] = $choice;
        QuickChoices::save($choices, 'Pilihan cepat Tanya Asisten ditambah: ' . $choice['label']);

        return redirect()->route('asisten.admin')->with('message', lang('Asisten.choiceSaved'));
    }

    public function editChoice(int $position): RedirectResponse|string
    {
        $choice = QuickChoices::find($position);

        if ($choice === null) {
            return redirect()->route('asisten.admin')->with('error', lang('Asisten.choiceNotFound'));
        }

        return $this->choiceForm($position, $choice);
    }

    public function updateChoice(int $position): RedirectResponse
    {
        $choices = QuickChoices::all();

        if (! isset($choices[$position - 1])) {
            return redirect()->route('asisten.admin')->with('error', lang('Asisten.choiceNotFound'));
        }

        $choice = $this->postedChoice();

        if ($choice === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Translations of a switched-off language are not on the form: keep them.
        foreach (array_diff(Locales::ALL, Locales::enabled()) as $locale) {
            foreach (['label', 'answer'] as $field) {
                if (isset($choices[$position - 1][$field . '_' . $locale])) {
                    $choice[$field . '_' . $locale] = $choices[$position - 1][$field . '_' . $locale];
                }
            }
        }

        $choices[$position - 1] = QuickChoices::clean($choice);
        QuickChoices::save($choices, 'Pilihan cepat Tanya Asisten diubah: ' . $choice['label']);

        return redirect()->route('asisten.admin')->with('message', lang('Asisten.choiceSaved'));
    }

    public function moveChoice(int $position): RedirectResponse
    {
        $choices = QuickChoices::all();
        $index   = $position - 1;
        $target  = $this->request->getPost('arah') === 'naik' ? $index - 1 : $index + 1;

        if (isset($choices[$index], $choices[$target])) {
            [$choices[$index], $choices[$target]] = [$choices[$target], $choices[$index]];
            QuickChoices::save($choices, 'Urutan pilihan cepat Tanya Asisten diubah');
        }

        return redirect()->to(url_to('asisten.admin') . '#pilihan-' . ($target + 1));
    }

    public function deleteChoice(int $position): RedirectResponse
    {
        $choices = QuickChoices::all();

        if (! isset($choices[$position - 1])) {
            return redirect()->route('asisten.admin')->with('error', lang('Asisten.choiceNotFound'));
        }

        $label = $choices[$position - 1]['label'];
        array_splice($choices, $position - 1, 1);
        QuickChoices::save($choices, 'Pilihan cepat Tanya Asisten dihapus: ' . $label);

        return redirect()->route('asisten.admin')->with('message', lang('Asisten.choiceDeleted', [$label]));
    }

    public function resetChoices(): RedirectResponse
    {
        QuickChoices::reset();

        return redirect()->route('asisten.admin')->with('message', lang('Asisten.choicesReset'));
    }

    /**
     * @param array<string, string> $choice
     */
    private function choiceForm(?int $position, array $choice): string
    {
        $isNew = $position === null;

        return view('Modules\Asisten\Views\admin\pilihan_form', [
            'title'       => lang($isNew ? 'Asisten.choiceAddTitle' : 'Asisten.choiceEditTitle'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => 'Website'],
                ['label' => lang('Asisten.adminTitle'), 'url' => 'admin/asisten'],
                ['label' => lang($isNew ? 'Asisten.choiceCrumbAdd' : 'Asisten.choiceCrumbEdit')],
            ],
            'choice'   => $choice,
            'position' => $position,
        ]);
    }

    /**
     * Validated choice from the form, or null (errors in $this->validator).
     *
     * @return array<string, string>|null
     */
    private function postedChoice(): ?array
    {
        $kind = (string) $this->request->getPost('kind');
        $post = [
            'kind'   => in_array($kind, QuickChoices::KINDS, true) ? $kind : '',
            'label'  => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('label'))),
            'url'    => trim((string) $this->request->getPost('url')),
            'answer' => trim(str_replace("\r\n", "\n", (string) $this->request->getPost('answer'))),
        ];

        $rules = [
            'kind'  => ['label' => lang('Asisten.fieldKind'), 'rules' => 'required', 'errors' => ['required' => lang('Asisten.kindRequired')]],
            'label' => ['label' => lang('Asisten.fieldLabel'), 'rules' => 'required|max_length[' . self::LABEL_MAX . ']', 'errors' => ['required' => lang('Asisten.labelRequired'), 'max_length' => lang('Asisten.labelTooLong', [self::LABEL_MAX])]],
        ];

        if ($post['kind'] === 'link') {
            $rules['url'] = ['label' => lang('Asisten.fieldUrl'), 'rules' => 'required', 'errors' => ['required' => lang('Asisten.urlRequired')]];
        }

        if ($post['kind'] === 'answer') {
            $rules['answer'] = ['label' => lang('Asisten.fieldAnswer'), 'rules' => 'required|max_length[' . self::ANSWER_MAX . ']', 'errors' => ['required' => lang('Asisten.answerRequired'), 'max_length' => lang('Asisten.answerTooLong', [number_format(self::ANSWER_MAX, 0, ',', '.')])]];
        }

        [$translations, $translationRules] = TranslationInput::read($this->request, [
            'label'  => ['max' => self::LABEL_MAX],
            'answer' => ['max' => self::ANSWER_MAX, 'multiline' => true],
        ], false);
        $post += $translations;

        if (! $this->validateData($post, $rules + $translationRules)) {
            return null;
        }

        if ($post['kind'] === 'link') {
            $url = SiteLink::normalise($post['url']);

            if ($url === null || $url === '') {
                $this->validator->setError('url', lang('Asisten.urlInvalid'));

                return null;
            }

            $post['url'] = $url;
        }

        return QuickChoices::clean($post);
    }
}
