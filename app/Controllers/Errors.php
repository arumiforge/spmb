<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Locales;
use Config\Services;
use Throwable;

/**
 * Friendly error pages (404 and 500) in the page's language (D-109).
 *
 * Signed-in staff see the page inside the admin layout; everyone else sees the
 * public layout. If the layout itself cannot be rendered (for example the
 * database is down), a self-contained page without settings is shown instead.
 *
 * Used as the 404 override (Config\Routing) and by app/Views/errors/html/*.php.
 */
class Errors extends BaseController
{
    public function notFound(): string
    {
        $this->response->setStatusCode(404);

        return self::render(404);
    }

    /**
     * Full HTML page for an error status (404 or 500).
     */
    public static function render(int $code): string
    {
        $code  = $code === 404 ? 404 : 500;
        $level = ob_get_level();

        try {
            return Services::renderer(null, null, false)
                ->setData(self::data($code), 'raw')
                ->render('errors/friendly', null, false);
        } catch (Throwable $e) {
            // A failed render can leave output buffers open with half a page in them.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            log_message('error', 'Error page could not use the layout: ' . $e->getMessage());

            return self::fallback($code);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function data(int $code): array
    {
        $staff = false;

        try {
            // On an English or Arabic address the visitor sees the public layout in that language (D-109).
            $staff = auth()->loggedIn() && Locales::isDefault();
        } catch (Throwable) {
            $staff = false;
        }

        $title = lang($code === 404 ? 'App.notFoundTitle' : 'App.serverErrorTitle');

        $whatsapp = '';

        try {
            $whatsapp = preg_replace('/\D/', '', (string) setting('School.whatsapp')) ?? '';
        } catch (Throwable) {
            $whatsapp = '';
        }

        if (str_starts_with($whatsapp, '0')) {
            $whatsapp = '62' . substr($whatsapp, 1);
        }

        return [
            'title'       => $title,
            'staff'       => $staff,
            'breadcrumbs' => [['label' => $staff ? lang('App.dashboard') : lang('App.home'), 'url' => $staff ? 'admin' : '/'], ['label' => $title]],
            'error'       => [
                'code'     => $code,
                'title'    => $title,
                'text'     => lang($code === 404 ? 'App.notFoundText' : 'App.serverErrorText'),
                'icon'     => $code === 404 ? 'compass' : 'warning',
                'whatsapp' => $staff ? '' : $whatsapp,
            ],
        ];
    }

    /**
     * Self-contained page: no database, no settings, no build assets.
     */
    public static function fallback(int $code): string
    {
        // No language files either: the three website languages are written here (D-109).
        $texts = [
            'id' => ['Halaman tidak ditemukan', 'Alamat yang Anda buka mungkin salah ketik, sudah dipindahkan, atau tidak tersedia lagi.',
                'Maaf, ada gangguan di sistem', 'Halaman ini belum bisa ditampilkan. Coba muat ulang beberapa saat lagi. Jika masih terjadi, hubungi panitia sekolah.', 'Kembali ke Beranda'],
            'en' => ['Page not found', 'The address you opened may have a typo, may have moved, or is no longer available.',
                'Sorry, something went wrong', 'This page cannot be shown right now. Please reload it in a few moments. If it keeps happening, contact the school’s admission team.', 'Back to Home'],
            'ar' => ['الصفحة غير موجودة', 'ربما يحتوي العنوان على خطأ في الكتابة، أو نُقلت الصفحة، أو لم تعد متاحة.',
                'عذرًا، حدث خلل في النظام', 'لا يمكن عرض هذه الصفحة الآن. أعد تحميلها بعد قليل، وإذا تكرّر الأمر فتواصل مع لجنة القبول في المدرسة.', 'العودة إلى الرئيسية'],
        ];
        $locale = Locales::current();
        $t      = $texts[$locale] ?? $texts['id'];
        $title  = $code === 404 ? $t[0] : $t[2];
        $text   = $code === 404 ? $t[1] : $t[3];
        $home   = $t[4];
        $dir    = Locales::direction($locale);
        $href   = $locale === 'id' ? '/' : '/' . $locale . '/';

        return <<<HTML
            <!doctype html>
            <html lang="{$locale}" dir="{$dir}">
            <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta name="robots" content="noindex">
            <title>{$title}</title>
            <style>
            body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f9fafb;color:#111827;padding:16px;box-sizing:border-box}
            main{max-width:28rem;text-align:center}
            p.code{font-size:3rem;font-weight:800;color:#9ca3af;margin:0}
            h1{font-size:1.5rem;margin:.5rem 0}
            p{color:#4b5563;line-height:1.5}
            a{display:inline-block;margin-top:1rem;padding:.75rem 1.25rem;border-radius:.5rem;background:#1d4ed8;color:#fff;text-decoration:none;font-weight:600}
            </style>
            </head>
            <body>
            <main>
            <p class="code">{$code}</p>
            <h1>{$title}</h1>
            <p>{$text}</p>
            <a href="{$href}">{$home}</a>
            </main>
            </body>
            </html>
            HTML;
    }
}
