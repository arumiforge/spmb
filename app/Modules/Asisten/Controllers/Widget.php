<?php

declare(strict_types=1);

namespace Modules\Asisten\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Analitik\Libraries\VisitRecorder;
use Modules\Asisten\Libraries\Assistant;
use Modules\Asisten\Libraries\AvatarStorage;
use Modules\Asisten\Libraries\QuickChoices;
use Modules\Asisten\Models\QuestionLogModel;
use Modules\Situs\Libraries\SiteLink;

/**
 * Public endpoints of the "Tanya Asisten" widget (D-110). They answer only while the module and the
 * widget (setting Asisten.enabled) are on, under / as well as /en/ and /ar/ (the answer language).
 *
 * - GET  asisten/mulai     CSRF token for the widget (the session starts only when the chat is used)
 * - POST asisten/tanya     a typed question (pertanyaan, mode=ai to skip Tanya Jawab), a quick choice
 *                          (pilihan) or ulang=1 to forget the conversation; JSON answer with a new token
 * - GET  asisten/buka/<n>  counts a quick choice link in the log and opens its page
 * - GET  asisten/gambar    the assistant picture the school uploaded
 */
class Widget extends BaseController
{
    public function start(): ResponseInterface
    {
        $this->ensureOn();

        return $this->json(['ok' => true]);
    }

    public function ask(): ResponseInterface
    {
        $this->ensureOn();
        $assistant = new Assistant($this->request);

        if ($this->request->getPost('ulang') === '1') {
            $assistant->reset();

            return $this->json(['ok' => true, 'type' => 'reset']);
        }

        $choice = (string) $this->request->getPost('pilihan');

        $payload = $choice !== '' && ctype_digit($choice)
            ? $assistant->choice((int) $choice)
            : $assistant->ask((string) $this->request->getPost('pertanyaan'), $this->request->getPost('mode') === 'ai');

        $status = match ($payload['type'] ?? '') {
            'invalid' => 422,
            'slow'    => 429,
            default   => 200,
        };

        return $this->json(['ok' => $status === 200] + $payload, $status);
    }

    public function open(int $position): RedirectResponse
    {
        $this->ensureOn();
        $choice = QuickChoices::find($position);

        if ($choice === null || $choice['kind'] === 'answer') {
            throw PageNotFoundException::forPageNotFound();
        }

        $target = $choice['kind'] === 'whatsapp'
            ? whatsapp_url((string) setting('School.whatsapp'))
            : SiteLink::href((string) ($choice['url'] ?? '/'));

        if (! VisitRecorder::isBot($this->request->getHeaderLine('User-Agent'))) {
            model(QuestionLogModel::class)->record(localized($choice, 'label'), locale_current(), 'chip', ['choice_label' => mb_substr($choice['label'], 0, 80)]);
        }

        return redirect()->to($target);
    }

    public function picture(): ResponseInterface
    {
        $file = AvatarStorage::file();

        if ($file === '') {
            throw PageNotFoundException::forPageNotFound();
        }

        $path = AvatarStorage::DIR . $file;
        $mime = (string) mime_content_type($path);

        if (! isset(AvatarStorage::MIMES[$mime])) {
            throw PageNotFoundException::forPageNotFound();
        }

        // The URL carries a version (?v=…), so a new picture gets a new address.
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->removeHeader('Pragma')
            ->removeHeader('Expires')
            ->setBody((string) file_get_contents($path));
    }

    private function ensureOn(): void
    {
        if (! service('modules')->isEnabled('Asisten') || ! (bool) setting('Asisten.enabled')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * JSON answer with a fresh CSRF token (tokens change after every POST) and no caching.
     *
     * @param array<string, mixed> $data
     */
    private function json(array $data, int $status = 200): ResponseInterface
    {
        $data['csrf'] = ['name' => csrf_token(), 'header' => csrf_header(), 'hash' => csrf_hash()];

        return $this->response
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store')
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setJSON($data);
    }
}
