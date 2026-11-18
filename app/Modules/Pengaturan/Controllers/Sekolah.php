<?php

declare(strict_types=1);

namespace Modules\Pengaturan\Controllers;

use App\Controllers\BaseController;
use App\Libraries\BrandingAssets;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * School identity and branding (white label), stored with setting('School.*').
 */
class Sekolah extends BaseController
{
    /**
     * Text settings editable on this page.
     *
     * @var list<string>
     */
    public const FIELDS = ['name', 'shortName', 'tagline', 'address', 'phone', 'whatsapp', 'email', 'website', 'primaryColor', 'timezoneLabel', 'footerText', 'partnerName', 'illustrationAccent'];

    public function index(): string
    {
        $values = [];

        foreach (self::FIELDS as $field) {
            $values[$field] = (string) setting('School.' . $field);
        }

        return view('Modules\Pengaturan\Views\sekolah', [
            'title'       => lang('Pengaturan.schoolTitle'),
            'subtitle'    => lang('Pengaturan.schoolIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Identitas Sekolah']],
            'values'      => $values,
            'images'      => array_map(static fn (string $key): ?string => BrandingAssets::url($key), array_combine(array_keys(BrandingAssets::ASSETS), array_keys(BrandingAssets::ASSETS))),
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'name'          => ['label' => lang('Pengaturan.name'), 'rules' => 'required|max_length[150]'],
            'shortName'     => ['label' => lang('Pengaturan.shortName'), 'rules' => 'required|max_length[40]'],
            'tagline'       => ['label' => lang('Pengaturan.tagline'), 'rules' => 'permit_empty|max_length[150]'],
            'address'       => ['label' => lang('Pengaturan.address'), 'rules' => 'permit_empty|max_length[500]'],
            'phone'         => ['label' => lang('Pengaturan.phone'), 'rules' => 'permit_empty|max_length[30]|regex_match[/\A[0-9+() .-]+\z/]'],
            'whatsapp'      => ['label' => lang('Pengaturan.whatsapp'), 'rules' => 'permit_empty|max_length[30]|regex_match[/\A[0-9+() .-]+\z/]'],
            'email'         => ['label' => lang('Pengaturan.email'), 'rules' => 'permit_empty|valid_email|max_length[150]'],
            'website'       => ['label' => lang('Pengaturan.website'), 'rules' => 'permit_empty|valid_url_strict[https,http]|max_length[200]'],
            'primaryColor'  => ['label' => lang('Pengaturan.primaryColor'), 'rules' => 'required|regex_match[/\A#[0-9a-fA-F]{6}\z/]', 'errors' => ['regex_match' => lang('Pengaturan.colorInvalid')]],
            'timezoneLabel' => ['label' => lang('Pengaturan.timezoneLabel'), 'rules' => 'required|in_list[WIB,WITA,WIT]'],
            'footerText'    => ['label' => lang('Pengaturan.footerText'), 'rules' => 'permit_empty|max_length[255]'],
        ];

        $uploads = [];

        foreach (BrandingAssets::ASSETS as $key => $asset) {
            $file = $this->request->getFile($key);

            if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $uploads[$key] = $file;
            $mimes         = $key === 'favicon' ? 'image/png,image/x-icon,image/vnd.microsoft.icon' : implode(',', array_map(static fn (string $e): string => $e === 'jpg' || $e === 'jpeg' ? 'image/jpeg' : 'image/' . $e, $asset['ext']));
            $rules[$key]   = [
                'label' => lang('Pengaturan.' . $key),
                'rules' => "uploaded[{$key}]|mime_in[{$key},{$mimes}]|ext_in[{$key}," . implode(',', $asset['ext']) . "]|max_size[{$key}," . ($key === 'favicon' ? 200 : 2048) . ']',
            ];
        }

        $rules['partnerName']        = ['label' => lang('Pengaturan.partnerName'), 'rules' => 'permit_empty|max_length[150]'];
        $rules['illustrationAccent'] = ['label' => lang('Pengaturan.illustrationAccent'), 'rules' => 'permit_empty|in_list[hijau]'];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $old = [];
        $new = [];

        foreach (self::FIELDS as $field) {
            $value = trim((string) $this->request->getPost($field));

            if ($field === 'primaryColor') {
                $value = (string) valid_hex_color($value);
            }

            $current = (string) setting('School.' . $field);

            if ($value !== $current) {
                $old[$field] = $current;
                $new[$field] = $value;
                setting('School.' . $field, $value);
            }
        }

        foreach (BrandingAssets::ASSETS as $key => $asset) {
            $setting = $asset['setting'];
            $current = (string) setting('School.' . $setting);
            $file    = $uploads[$key] ?? null;

            if ($file !== null && $file->isValid()) {
                $stored = BrandingAssets::storeUpload($file, $key);

                if ($stored === null) {
                    return redirect()->back()->withInput()->with('error', lang('Pengaturan.uploadFailed'));
                }

                BrandingAssets::remove($current);
                setting('School.' . $setting, $stored);
                $old[$setting] = $current;
                $new[$setting] = $stored;
            } elseif ($this->request->getPost('remove_' . $key) === '1' && $current !== '') {
                BrandingAssets::remove($current);
                setting('School.' . $setting, '');
                $old[$setting] = $current;
                $new[$setting] = '';
            }
        }

        if ($new !== []) {
            service('audit')->log('settings_updated', 'settings', 'School', $old, $new, 'Identitas sekolah diubah');
        }

        return redirect()->route('pengaturan.sekolah')->with('message', lang('Pengaturan.saved'));
    }
}
