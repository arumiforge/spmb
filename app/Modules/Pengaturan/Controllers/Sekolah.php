<?php

declare(strict_types=1);

namespace Modules\Pengaturan\Controllers;

use App\Controllers\BaseController;
use App\Libraries\BrandingAssets;
use App\Libraries\TranslationInput;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Referensi\Libraries\RegionInput;
use Modules\Referensi\Models\RegionModel;

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
    public const FIELDS = ['name', 'shortName', 'tagline', 'motto', 'address', 'phone', 'whatsapp', 'email', 'website', 'mapEmbed',
        'socialInstagram', 'socialFacebook', 'socialYoutube', 'socialTiktok', 'primaryColor', 'timezoneLabel', 'footerText', 'partnerName', 'illustrationAccent'];

    /**
     * School texts with optional English and Arabic versions (D-109), with their length limits.
     *
     * @var array<string, array{max: int, multiline?: bool}>
     */
    public const TRANSLATED = [
        'tagline'    => ['max' => 150],
        'motto'      => ['max' => 200],
        'address'    => ['max' => 500, 'multiline' => true],
        'footerText' => ['max' => 255],
    ];

    /**
     * Switches of the English and Arabic website (D-109).
     */
    public const LANGUAGES = ['languageEn', 'languageAr'];

    /**
     * @return list<string> e.g. motto_en, motto_ar
     */
    private static function translatedKeys(): array
    {
        $keys = [];

        foreach (array_keys(self::TRANSLATED) as $field) {
            $keys[] = $field . '_en';
            $keys[] = $field . '_ar';
        }

        return $keys;
    }

    public function index(): string
    {
        $values = [];

        foreach ([...self::FIELDS, ...self::translatedKeys()] as $field) {
            $values[$field] = (string) setting('School.' . $field);
        }

        foreach (self::LANGUAGES as $field) {
            $values[$field] = (bool) setting('School.' . $field) ? '1' : '0';
        }

        // The school's own regency/city from the national region list (D-111), when it is loaded.
        $values['regionCode'] = (string) setting('School.regionCode');
        $regionOptions        = self::regionsAvailable() ? model(RegionModel::class)->regencyOptions() : null;

        return view('Modules\Pengaturan\Views\sekolah', [
            'title'       => lang('Pengaturan.schoolTitle'),
            'subtitle'    => lang('Pengaturan.schoolIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengaturan'], ['label' => 'Identitas Sekolah']],
            'values'      => $values,
            'images'      => array_map(static fn (string $key): ?string => BrandingAssets::url($key), array_combine(array_keys(BrandingAssets::ASSETS), array_keys(BrandingAssets::ASSETS))),
            'regions'     => $regionOptions,
        ]);
    }

    public function update(): RedirectResponse
    {
        $rules = [
            'name'          => ['label' => lang('Pengaturan.name'), 'rules' => 'required|max_length[150]'],
            'shortName'     => ['label' => lang('Pengaturan.shortName'), 'rules' => 'required|max_length[40]'],
            'tagline'       => ['label' => lang('Pengaturan.tagline'), 'rules' => 'permit_empty|max_length[150]'],
            'motto'         => ['label' => lang('Pengaturan.motto'), 'rules' => 'permit_empty|max_length[200]'],
            'mapEmbed'      => ['label' => lang('Pengaturan.mapEmbed'), 'rules' => 'permit_empty|max_length[3000]|map_embed', 'errors' => ['map_embed' => lang('Pengaturan.mapEmbedInvalid')]],
            'address'       => ['label' => lang('Pengaturan.address'), 'rules' => 'permit_empty|max_length[500]'],
            'phone'         => ['label' => lang('Pengaturan.phone'), 'rules' => 'permit_empty|max_length[30]|regex_match[/\A[0-9+() .-]+\z/]'],
            'whatsapp'      => ['label' => lang('Pengaturan.whatsapp'), 'rules' => 'permit_empty|max_length[30]|regex_match[/\A[0-9+() .-]+\z/]'],
            'email'         => ['label' => lang('Pengaturan.email'), 'rules' => 'permit_empty|valid_email|max_length[150]'],
            'website'       => ['label' => lang('Pengaturan.website'), 'rules' => 'permit_empty|valid_url_strict[https,http]|max_length[200]'],
            'primaryColor'  => ['label' => lang('Pengaturan.primaryColor'), 'rules' => 'required|regex_match[/\A#[0-9a-fA-F]{6}\z/]', 'errors' => ['regex_match' => lang('Pengaturan.colorInvalid')]],
            'timezoneLabel' => ['label' => lang('Pengaturan.timezoneLabel'), 'rules' => 'required|in_list[WIB,WITA,WIT]'],
            'footerText'    => ['label' => lang('Pengaturan.footerText'), 'rules' => 'permit_empty|max_length[255]'],
        ];

        foreach (['socialInstagram', 'socialFacebook', 'socialYoutube', 'socialTiktok'] as $social) {
            $rules[$social] = ['label' => lang('Pengaturan.' . $social), 'rules' => 'permit_empty|valid_url_strict[https,http]|max_length[255]'];
        }

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

        // Optional English/Arabic versions of the school texts (D-109), saved as School.motto_en and so on.
        [$translations, $translationRules] = TranslationInput::read($this->request, self::TRANSLATED, false);

        if (! $this->validate($rules + $translationRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $old = [];
        $new = [];

        // School region (D-111): a regency/city code from the list, or empty for none.
        $region = $this->request->getPost('regionCode');

        if (is_string($region) && self::regionsAvailable()) {
            $region = trim($region);

            if ($region !== '' && (RegionModel::levelOf($region) !== 2 || model(RegionModel::class)->region($region) === null)) {
                return redirect()->back()->withInput()->with('errors', ['regionCode' => lang('Pengaturan.regionCodeInvalid')]);
            }

            $current = (string) setting('School.regionCode');

            if ($region !== $current) {
                $old['regionCode'] = $current;
                $new['regionCode'] = $region;
                setting('School.regionCode', $region);
            }
        }

        foreach ($translations as $field => $value) {
            $current = (string) setting('School.' . $field);

            if ($value !== $current) {
                $old[$field] = $current;
                $new[$field] = $value;
                setting('School.' . $field, $value);
            }
        }

        // Website languages: Indonesian is always on; English and Arabic can be switched off.
        foreach (self::LANGUAGES as $field) {
            if ($this->request->getPost($field) === null) {
                continue; // not on the submitted form
            }

            $value   = $this->request->getPost($field) === '1';
            $current = (bool) setting('School.' . $field);

            if ($value !== $current) {
                $old[$field] = (int) $current;
                $new[$field] = (int) $value;
                setting('School.' . $field, $value);
            }
        }

        foreach (self::FIELDS as $field) {
            $value = trim((string) $this->request->getPost($field));

            if ($field === 'primaryColor') {
                $value = (string) valid_hex_color($value);
            }

            if ($field === 'mapEmbed') {
                // Only the embed URL is stored, never the pasted HTML (D-085).
                $value = map_embed_url($value);
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

            // Same guard as Berkas UploadInput: the test suite's fake uploads are not is_uploaded_file().
            if ($file !== null && $file->getError() === UPLOAD_ERR_OK && (ENVIRONMENT === 'testing' || $file->isValid())) {
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

    private static function regionsAvailable(): bool
    {
        return class_exists(RegionInput::class) && RegionInput::available();
    }
}
