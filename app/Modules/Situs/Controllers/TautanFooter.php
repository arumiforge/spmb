<?php

declare(strict_types=1);

namespace Modules\Situs\Controllers;

use App\Controllers\BaseController;
use App\Libraries\TranslationInput;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Situs\Libraries\FooterLinks;
use Modules\Situs\Libraries\SiteLink;

/**
 * Admin screen for the "Tautan" column of the public footer (FooterLinks,
 * setting Situs.footerLinks). Links are addressed by their position (1-based).
 * Permission situs.kelola; every change is logged.
 */
class TautanFooter extends BaseController
{
    public function index(): string
    {
        $nav   = public_nav();
        $links = array_map(static fn (array $link): array => $link + ['available' => FooterLinks::available($link['url'], $nav)], FooterLinks::all());

        return view('Modules\Situs\Views\admin\tautan', [
            'title'       => lang('Situs.linksAdminTitle'),
            'subtitle'    => lang('Situs.linksAdminIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Situs.qaMenuGroup')], ['label' => lang('Situs.linksAdminTitle')]],
            'links'       => $links,
            'custom'      => FooterLinks::isCustom(),
            'full'        => count(FooterLinks::all()) >= FooterLinks::MAX,
        ]);
    }

    public function new(): RedirectResponse|string
    {
        if (count(FooterLinks::all()) >= FooterLinks::MAX) {
            return redirect()->route('situs.tautan.admin')->with('error', lang('Situs.linksFull', [FooterLinks::MAX]));
        }

        return $this->form(null, ['label' => '', 'url' => '', 'active' => true]);
    }

    public function create(): RedirectResponse
    {
        $links = FooterLinks::all();

        if (count($links) >= FooterLinks::MAX) {
            return redirect()->route('situs.tautan.admin')->with('error', lang('Situs.linksFull', [FooterLinks::MAX]));
        }

        $link = $this->posted();

        if ($link === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $links[] = $link;
        FooterLinks::save($links, 'Tautan bawah website ditambah');

        return redirect()->route('situs.tautan.admin')->with('message', lang('Situs.linkSaved'));
    }

    public function edit(int $position): RedirectResponse|string
    {
        $link = FooterLinks::all()[$position - 1] ?? null;

        return $link === null ? $this->notFound() : $this->form($position, $link);
    }

    public function update(int $position): RedirectResponse
    {
        $links = FooterLinks::all();

        if (! isset($links[$position - 1])) {
            return $this->notFound();
        }

        $link = $this->posted();

        if ($link === null) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Keeps the translation of a language that is switched off (its field is not on the form).
        $links[$position - 1] = array_merge($links[$position - 1], $link);
        FooterLinks::save($links, 'Tautan bawah website diubah');

        return redirect()->route('situs.tautan.admin')->with('message', lang('Situs.linkSaved'));
    }

    /**
     * Moves a link one place up or down (arah = naik | turun).
     */
    public function move(int $position): RedirectResponse
    {
        $links  = FooterLinks::all();
        $index  = $position - 1;
        $target = $this->request->getPost('arah') === 'naik' ? $index - 1 : $index + 1;

        if (! isset($links[$index])) {
            return $this->notFound();
        }

        if (isset($links[$target])) {
            [$links[$index], $links[$target]] = [$links[$target], $links[$index]];
            FooterLinks::save($links, 'Urutan tautan bawah website diubah');
        }

        return redirect()->to(url_to('situs.tautan.admin') . '#tautan-' . (isset($links[$target]) ? $target + 1 : $position));
    }

    /**
     * Shows (tampil = 1) or hides (tampil = 0) a link.
     */
    public function publish(int $position): RedirectResponse
    {
        $links = FooterLinks::all();

        if (! isset($links[$position - 1])) {
            return $this->notFound();
        }

        $show                           = $this->request->getPost('tampil') === '1';
        $links[$position - 1]['active'] = $show;
        FooterLinks::save($links, 'Tautan bawah website ' . ($show ? 'ditampilkan' : 'disembunyikan'));

        return redirect()->route('situs.tautan.admin')->with('message', lang($show ? 'Situs.linkShown' : 'Situs.linkHidden', [$links[$position - 1]['label']]));
    }

    public function delete(int $position): RedirectResponse
    {
        $links = FooterLinks::all();

        if (! isset($links[$position - 1])) {
            return $this->notFound();
        }

        array_splice($links, $position - 1, 1);
        FooterLinks::save($links, 'Tautan bawah website dihapus');

        return redirect()->route('situs.tautan.admin')->with('message', lang('Situs.linkDeleted'));
    }

    /**
     * Back to the default list (Cek Status, Tanya Jawab, Panduan).
     */
    public function reset(): RedirectResponse
    {
        FooterLinks::reset();

        return redirect()->route('situs.tautan.admin')->with('message', lang('Situs.linksReset'));
    }

    private function notFound(): RedirectResponse
    {
        return redirect()->route('situs.tautan.admin')->with('error', lang('Situs.linkNotFound'));
    }

    /**
     * @param array{label: string, url: string, active: bool} $link
     */
    private function form(?int $position, array $link): string
    {
        $isNew = $position === null;

        return view('Modules\Situs\Views\admin\tautan_form', [
            'title'       => lang($isNew ? 'Situs.linkAddTitle' : 'Situs.linkEditTitle'),
            'breadcrumbs' => [
                ['label' => 'Dasbor', 'url' => 'admin'],
                ['label' => lang('Situs.qaMenuGroup')],
                ['label' => lang('Situs.linksAdminTitle'), 'url' => 'admin/tautan-footer'],
                ['label' => lang($isNew ? 'Situs.qaCrumbAdd' : 'Situs.qaCrumbEdit')],
            ],
            'link'     => $link,
            'position' => $position,
        ]);
    }

    /**
     * Validated link from the form, or null (errors in $this->validator).
     *
     * @return array{label: string, url: string, active: bool}|null
     */
    private function posted(): ?array
    {
        $post = [
            'label'  => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('label'))),
            'url'    => trim((string) $this->request->getPost('url')),
            'active' => $this->request->getPost('active') === '1',
        ];

        $rules = [
            'label' => ['label' => lang('Situs.linkFieldLabel'), 'rules' => 'required|max_length[40]', 'errors' => ['required' => lang('Situs.linkLabelRequired'), 'max_length' => lang('Situs.linkLabelTooLong')]],
            'url'   => ['label' => lang('Situs.linkFieldUrl'), 'rules' => 'required|max_length[255]', 'errors' => ['required' => lang('Situs.linkUrlRequired'), 'max_length' => lang('Situs.linkInvalid')]],
        ];

        // Optional English/Arabic versions of the link text (D-109).
        [$translations, $translationRules] = TranslationInput::read($this->request, ['label' => ['max' => 40]], false);
        $post += $translations;

        if (! $this->validateData($post, $rules + $translationRules)) {
            return null;
        }

        $url = SiteLink::normalise($post['url']);

        if ($url === null || $url === '') {
            $this->validator->setError('url', lang('Situs.linkInvalid'));

            return null;
        }

        $post['url'] = $url;

        return $post;
    }
}
