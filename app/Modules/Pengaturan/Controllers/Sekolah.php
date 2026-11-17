<?php

declare(strict_types=1);

namespace Modules\Pengaturan\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Branding;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use Throwable;

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
    public const FIELDS = ['name', 'shortName', 'tagline', 'address', 'phone', 'whatsapp', 'email', 'website', 'primaryColor', 'timezoneLabel', 'footerText'];

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
            'logoUrl'     => school_logo_url(),
            'hasFavicon'  => (string) setting('School.faviconPath') !== '',
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

        $logo    = $this->request->getFile('logo');
        $favicon = $this->request->getFile('favicon');

        if ($logo !== null && $logo->getError() !== UPLOAD_ERR_NO_FILE) {
            $rules['logo'] = ['label' => lang('Pengaturan.logo'), 'rules' => 'uploaded[logo]|is_image[logo]|mime_in[logo,image/png,image/jpeg,image/webp]|ext_in[logo,png,jpg,jpeg,webp]|max_size[logo,1024]'];
        }

        if ($favicon !== null && $favicon->getError() !== UPLOAD_ERR_NO_FILE) {
            $rules['favicon'] = ['label' => lang('Pengaturan.favicon'), 'rules' => 'uploaded[favicon]|mime_in[favicon,image/png,image/x-icon,image/vnd.microsoft.icon]|ext_in[favicon,png,ico]|max_size[favicon,200]'];
        }

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

        foreach (['logo' => 'logoPath', 'favicon' => 'faviconPath'] as $input => $setting) {
            $file    = $input === 'logo' ? $logo : $favicon;
            $current = (string) setting('School.' . $setting);

            if ($file !== null && $file->isValid()) {
                $stored = $this->store($file, $input);

                if ($stored === null) {
                    return redirect()->back()->withInput()->with('error', lang('Pengaturan.uploadFailed'));
                }

                $this->removeFile($current);
                setting('School.' . $setting, $stored);
                $old[$setting] = $current;
                $new[$setting] = $stored;
            } elseif ($this->request->getPost('remove_' . $input) === '1' && $current !== '') {
                $this->removeFile($current);
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

    /**
     * Saves an uploaded branding image under a random name; logos are scaled down to 512 px.
     */
    private function store(UploadedFile $file, string $prefix): ?string
    {
        if (! is_dir(Branding::DIR)) {
            mkdir(Branding::DIR, 0775, true);
        }

        $name = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . strtolower($file->guessExtension() ?: $file->getClientExtension());

        try {
            $file->move(Branding::DIR, $name);

            if ($prefix === 'logo' && extension_loaded('gd')) {
                [$width, $height] = getimagesize(Branding::DIR . $name) ?: [0, 0];

                if ($width > 512 || $height > 512) {
                    service('image')->withFile(Branding::DIR . $name)->resize(512, 512, true)->save();
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Branding upload failed: ' . $e->getMessage());

            return null;
        }

        return $name;
    }

    private function removeFile(string $name): void
    {
        $name = basename($name);

        if ($name !== '' && is_file(Branding::DIR . $name)) {
            unlink(Branding::DIR . $name);
        }
    }
}
