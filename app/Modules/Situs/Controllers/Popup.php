<?php

declare(strict_types=1);

namespace Modules\Situs\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Situs\Libraries\PopupFiles;
use Modules\Situs\Libraries\SiteLink;
use Modules\Situs\Services\HomePopup;

/**
 * The home page popup: admin form (permission situs.kelola, settings
 * Situs.popup*) and the public routes that serve its image and attachment
 * from writable/uploads/situs/.
 */
class Popup extends BaseController
{
    /**
     * Text settings saved from the form (setting('Situs.popup<Key>')).
     *
     * @var list<string>
     */
    private const TEXT_FIELDS = ['title', 'text', 'buttonLabel', 'buttonUrl', 'startsOn', 'endsOn', 'frequency'];

    private const IMAGE_MIMES      = 'image/jpeg,image/png,image/webp';
    private const ATTACHMENT_MIMES = 'application/pdf,application/msword,application/vnd.ms-office,application/vnd.ms-excel,application/CDFV2,application/x-ole-storage,'
        . 'application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/zip';

    public function index(): string
    {
        $s = HomePopup::settings();

        return view('Modules\Situs\Views\admin\popup', [
            'title'       => lang('Situs.popupAdminTitle'),
            'subtitle'    => lang('Situs.popupAdminIntro'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => lang('Situs.qaMenuGroup')], ['label' => lang('Situs.popupAdminTitle')]],
            'popup'       => $s,
            'live'        => HomePopup::isLive(),
            'imageUrl'    => PopupFiles::path($s['image']) !== null ? site_url('popup/gambar') . '?v=' . substr(md5($s['image']), 0, 8) : null,
            'attachment'  => PopupFiles::path($s['attachment']) !== null ? PopupFiles::downloadName($s['attachmentName'], $s['attachment']) : null,
        ]);
    }

    public function update(): RedirectResponse
    {
        $post = [
            'enabled'     => $this->request->getPost('enabled') === '1',
            'title'       => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('title'))),
            'text'        => trim(str_replace("\r\n", "\n", (string) $this->request->getPost('text'))),
            'buttonLabel' => trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('buttonLabel'))),
            'buttonUrl'   => trim((string) $this->request->getPost('buttonUrl')),
            'startsOn'    => trim((string) $this->request->getPost('startsOn')),
            'endsOn'      => trim((string) $this->request->getPost('endsOn')),
            'frequency'   => (string) $this->request->getPost('frequency'),
        ];

        $rules = [
            'title'       => ['label' => lang('Situs.popupFieldTitle'), 'rules' => 'max_length[120]', 'errors' => ['max_length' => lang('Situs.popupTitleTooLong')]],
            'text'        => ['label' => lang('Situs.popupFieldText'), 'rules' => 'max_length[3000]', 'errors' => ['max_length' => lang('Situs.popupTextTooLong')]],
            'buttonLabel' => ['label' => lang('Situs.popupFieldButtonLabel'), 'rules' => 'max_length[40]', 'errors' => ['max_length' => lang('Situs.popupButtonLabelTooLong')]],
            'buttonUrl'   => ['label' => lang('Situs.popupFieldButtonUrl'), 'rules' => 'max_length[255]', 'errors' => ['max_length' => lang('Situs.linkInvalid')]],
            'startsOn'    => ['label' => lang('Situs.fieldStartsOn'), 'rules' => 'permit_empty|valid_date[Y-m-d]', 'errors' => ['valid_date' => lang('Situs.dateInvalid')]],
            'endsOn'      => ['label' => lang('Situs.fieldEndsOn'), 'rules' => 'permit_empty|valid_date[Y-m-d]', 'errors' => ['valid_date' => lang('Situs.dateInvalid')]],
            'frequency'   => ['label' => lang('Situs.popupFieldFrequency'), 'rules' => 'required|in_list[' . implode(',', array_keys(HomePopup::FREQUENCIES)) . ']'],
        ];

        if (! $this->validateData($post, $rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $errors = [];
        $url    = SiteLink::normalise($post['buttonUrl']);

        if ($url === null) {
            $errors['buttonUrl'] = lang('Situs.linkInvalid');
        } elseif (($post['buttonLabel'] === '') !== ($url === '')) {
            $errors[$post['buttonLabel'] === '' ? 'buttonLabel' : 'buttonUrl'] = lang('Situs.popupButtonIncomplete');
        }

        if ($post['startsOn'] !== '' && $post['endsOn'] !== '' && $post['endsOn'] < $post['startsOn']) {
            $errors['endsOn'] = lang('Situs.dateOrder');
        }

        if ($post['enabled'] && $post['title'] === '') {
            $errors['title'] = lang('Situs.popupTitleRequired');
        }

        $image      = $this->upload('image', PopupFiles::IMAGE_EXT, self::IMAGE_MIMES, PopupFiles::IMAGE_MAX_KB, lang('Situs.popupImageInvalid'), $errors);
        $attachment = $this->upload('attachment', PopupFiles::ATTACHMENT_EXT, self::ATTACHMENT_MIMES, PopupFiles::ATTACHMENT_MAX_KB, lang('Situs.popupAttachmentInvalid'), $errors);

        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $post['buttonUrl'] = (string) $url;
        $current           = HomePopup::settings();
        $old               = [];
        $new               = [];

        foreach (['enabled' => $post['enabled'], ...array_intersect_key($post, array_flip(self::TEXT_FIELDS))] as $key => $value) {
            if ($current[$key] !== $value) {
                $old['popup' . ucfirst($key)] = $current[$key];
                $new['popup' . ucfirst($key)] = $value;
                setting('Situs.popup' . ucfirst($key), $value);
            }
        }

        if ($image !== null) {
            $stored = PopupFiles::storeImage($image);

            if ($stored === null) {
                return redirect()->back()->withInput()->with('errors', ['image' => lang('Situs.popupImageInvalid')]);
            }

            $this->replaceFile('popupImage', $current['image'], $stored, $old, $new);
        } elseif ($this->request->getPost('remove_image') === '1' && $current['image'] !== '') {
            $this->replaceFile('popupImage', $current['image'], '', $old, $new);
        }

        if ($attachment !== null) {
            $stored = PopupFiles::storeAttachment($attachment);

            if ($stored === null) {
                return redirect()->back()->withInput()->with('errors', ['attachment' => lang('Situs.popupAttachmentInvalid')]);
            }

            $this->replaceFile('popupAttachment', $current['attachment'], $stored, $old, $new);
            setting('Situs.popupAttachmentName', mb_substr($attachment->getClientName(), 0, 150));
        } elseif ($this->request->getPost('remove_attachment') === '1' && $current['attachment'] !== '') {
            $this->replaceFile('popupAttachment', $current['attachment'], '', $old, $new);
            setting('Situs.popupAttachmentName', '');
        }

        // Every save is a new version: "sekali saja" shows the popup again after an edit.
        $old['popupVersion'] = $current['version'];
        $new['popupVersion'] = $current['version'] + 1;
        setting('Situs.popupVersion', $current['version'] + 1);

        service('audit')->log('settings_updated', 'settings', 'Popup', $old, $new, 'Popup beranda diubah');

        return redirect()->route('situs.popup.admin')->with('message', lang('Situs.popupSaved'));
    }

    /**
     * The popup image (public while the popup shows, always for admins).
     */
    public function image(): ResponseInterface
    {
        return $this->serve((string) setting('Situs.popupImage'), false);
    }

    /**
     * The popup attachment, as a download with its original name.
     */
    public function attachment(): ResponseInterface
    {
        return $this->serve((string) setting('Situs.popupAttachment'), true);
    }

    private function serve(string $name, bool $download): ResponseInterface
    {
        $path = PopupFiles::path($name);

        if ($path === null || ! (HomePopup::isLive() || HomePopup::canManage())) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($download) {
            return $this->response->download($path, null)
                ->setFileName(PopupFiles::downloadName((string) setting('Situs.popupAttachmentName'), $name))
                ->setHeader('X-Content-Type-Options', 'nosniff');
        }

        // URLs carry ?v=<hash of the stored name>, so a new upload gets a new URL.
        $this->response->setHeader('X-Content-Type-Options', 'nosniff')->setHeader('Cache-Control', 'public, max-age=86400');

        $mime = (string) (mime_content_type($path) ?: '');

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->setHeader('Content-Type', $mime)->setBody((string) file_get_contents($path));
    }

    /**
     * The uploaded file of $field when one was sent and it passes the checks, else null
     * (with a message in $errors when it was sent but refused).
     *
     * @param list<string>          $extensions
     * @param array<string, string> $errors
     */
    private function upload(string $field, array $extensions, string $mimes, int $maxKb, string $message, array &$errors): ?UploadedFile
    {
        $file = $this->request->getFile($field);

        if ($file === null || $file->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $rules = [$field => "uploaded[{$field}]|ext_in[{$field}," . implode(',', $extensions) . "]|mime_in[{$field},{$mimes}]|max_size[{$field},{$maxKb}]"];

        // Same guard as Pengaturan/Sekolah: the test suite's fake uploads are not is_uploaded_file().
        if ($file->getError() !== UPLOAD_ERR_OK || ! (ENVIRONMENT === 'testing' || $file->isValid()) || ! service('validation')->reset()->setRules($rules)->withRequest($this->request)->run()) {
            $errors[$field] = $message;

            return null;
        }

        return $file;
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     */
    private function replaceFile(string $key, string $current, string $stored, array &$old, array &$new): void
    {
        PopupFiles::remove($current);
        setting('Situs.' . $key, $stored);
        $old[$key] = $current;
        $new[$key] = $stored;
    }
}
