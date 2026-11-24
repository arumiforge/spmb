<?php

declare(strict_types=1);

namespace Modules\Situs\Controllers;

use App\Controllers\BaseController;
use App\Libraries\TranslationInput;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Situs\Libraries\SiteLink;
use Modules\Situs\Models\SiteNewsModel;

/**
 * Admin screen for the news ticker under the public header ("Berita singkat"):
 * switch and speed (settings Situs.newsEnabled, Situs.newsInterval) and the
 * items (table site_news, audited model). Permission situs.kelola.
 */
class BeritaSingkat extends BaseController
{
    public const MIN_INTERVAL = 3;
    public const MAX_INTERVAL = 30;

    private SiteNewsModel $news;

    public function __construct()
    {
        $this->news = model(SiteNewsModel::class);
    }

    public function index(): string
    {
        return view('Modules\Situs\Views\admin\berita', [
            'title'       => lang('Situs.newsAdminTitle'),
            'subtitle'    => lang('Situs.newsAdminIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Situs.qaMenuGroup')], ['label' => lang('Situs.newsAdminTitle')]],
            'items'       => $this->news->ordered(),
            'enabled'     => (bool) setting('Situs.newsEnabled'),
            'interval'    => (int) setting('Situs.newsInterval'),
        ]);
    }

    public function updateSettings(): RedirectResponse
    {
        $rules = [
            'interval' => [
                'label'  => lang('Situs.newsInterval'),
                'rules'  => 'required|integer|greater_than_equal_to[' . self::MIN_INTERVAL . ']|less_than_equal_to[' . self::MAX_INTERVAL . ']',
                'errors' => array_fill_keys(['required', 'integer', 'greater_than_equal_to', 'less_than_equal_to'], lang('Situs.newsIntervalError', [self::MIN_INTERVAL, self::MAX_INTERVAL])),
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $values = [
            'newsEnabled'  => $this->request->getPost('enabled') === '1',
            'newsInterval' => (int) $this->request->getPost('interval'),
        ];
        $old = [];
        $new = [];

        foreach ($values as $key => $value) {
            $current = setting('Situs.' . $key);

            if ((string) (is_bool($value) ? (int) $value : $value) !== (string) (is_bool($current) ? (int) $current : $current)) {
                $old[$key] = $current;
                $new[$key] = $value;
                setting('Situs.' . $key, $value);
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'News', $old, $new, 'Pengaturan berita singkat diubah');
        }

        return redirect()->route('situs.berita.admin')->with('message', lang('Situs.newsSettingsSaved'));
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

        $data['sort_order'] = $this->news->nextSortOrder();
        $this->news->insert($data);

        return redirect()->route('situs.berita.admin')->with('message', lang('Situs.newsSaved'));
    }

    public function edit(int $id): RedirectResponse|string
    {
        $item = $this->news->find($id);

        return $item === null ? $this->notFound() : $this->form($item);
    }

    public function update(int $id): RedirectResponse
    {
        if ($this->news->find($id) === null) {
            return $this->notFound();
        }

        $data = $this->posted();

        if ($data === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->news->update($id, $data);

        return redirect()->route('situs.berita.admin')->with('message', lang('Situs.newsSaved'));
    }

    /**
     * Moves an item one place up or down (arah = naik | turun).
     */
    public function move(int $id): RedirectResponse
    {
        if ($this->news->find($id) === null) {
            return $this->notFound();
        }

        $this->news->move($id, $this->request->getPost('arah') === 'naik');

        return redirect()->to(url_to('situs.berita.admin') . '#berita-' . $id);
    }

    /**
     * Switches an item on (aktif = 1) or off (aktif = 0).
     */
    public function activate(int $id): RedirectResponse
    {
        $item = $this->news->find($id);

        if ($item === null) {
            return $this->notFound();
        }

        $on = $this->request->getPost('aktif') === '1';

        if ((bool) $item['is_active'] !== $on) {
            $this->news->update($id, ['is_active' => (int) $on]);
        }

        return redirect()->route('situs.berita.admin')->with('message', lang($on ? 'Situs.newsShown' : 'Situs.newsHidden'));
    }

    public function delete(int $id): RedirectResponse
    {
        if ($this->news->find($id) === null) {
            return $this->notFound();
        }

        $this->news->delete($id);
        $this->news->normalise();

        return redirect()->route('situs.berita.admin')->with('message', lang('Situs.newsDeleted'));
    }

    private function notFound(): RedirectResponse
    {
        return redirect()->route('situs.berita.admin')->with('error', lang('Situs.newsNotFound'));
    }

    /**
     * @param array<string, mixed>|null $item
     */
    private function form(?array $item): string
    {
        $isNew = $item === null;

        return view('Modules\Situs\Views\admin\berita_form', [
            'title'       => lang($isNew ? 'Situs.newsAddTitle' : 'Situs.newsEditTitle'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => lang('Situs.qaMenuGroup')],
                ['label' => lang('Situs.newsAdminTitle'), 'url' => 'admin/berita-singkat'],
                ['label' => lang($isNew ? 'Situs.qaCrumbAdd' : 'Situs.qaCrumbEdit')],
            ],
            'item'  => $item ?? ['text' => '', 'url' => '', 'starts_on' => null, 'ends_on' => null, 'is_active' => 1],
            'isNew' => $isNew,
        ]);
    }

    /**
     * Validated item data from the form, or null (errors in $this->validator).
     *
     * @return array<string, mixed>|null
     */
    private function posted(): ?array
    {
        $post = [
            'text'      => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('text'))),
            'url'       => trim((string) $this->request->getPost('url')),
            'starts_on' => trim((string) $this->request->getPost('starts_on')),
            'ends_on'   => trim((string) $this->request->getPost('ends_on')),
            'is_active' => (int) ($this->request->getPost('is_active') === '1'),
        ];

        $rules = [
            'text'      => ['label' => lang('Situs.newsFieldText'), 'rules' => 'required|max_length[160]', 'errors' => ['required' => lang('Situs.newsTextRequired'), 'max_length' => lang('Situs.newsTextTooLong')]],
            'url'       => ['label' => lang('Situs.newsFieldUrl'), 'rules' => 'permit_empty|max_length[255]', 'errors' => ['max_length' => lang('Situs.linkInvalid')]],
            'starts_on' => ['label' => lang('Situs.fieldStartsOn'), 'rules' => 'permit_empty|valid_date[Y-m-d]', 'errors' => ['valid_date' => lang('Situs.dateInvalid')]],
            'ends_on'   => ['label' => lang('Situs.fieldEndsOn'), 'rules' => 'permit_empty|valid_date[Y-m-d]', 'errors' => ['valid_date' => lang('Situs.dateInvalid')]],
        ];

        // Optional English/Arabic versions (D-109).
        [$translations, $translationRules] = TranslationInput::read($this->request, ['text' => ['max' => 160]]);
        $post += $translations;

        if (! $this->validateData($post, $rules + $translationRules)) {
            return null;
        }

        $url = SiteLink::normalise($post['url']);

        if ($url === null) {
            $this->validator->setError('url', lang('Situs.linkInvalid'));

            return null;
        }

        if ($post['starts_on'] !== '' && $post['ends_on'] !== '' && $post['ends_on'] < $post['starts_on']) {
            $this->validator->setError('ends_on', lang('Situs.dateOrder'));

            return null;
        }

        $post['url']       = $url;
        $post['starts_on'] = $post['starts_on'] === '' ? null : $post['starts_on'];
        $post['ends_on']   = $post['ends_on'] === '' ? null : $post['ends_on'];

        return $post;
    }
}
