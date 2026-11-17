<?php

declare(strict_types=1);

namespace Modules\Profil\Controllers;

use App\Controllers\BaseController;
use App\Filters\StaffArea;
use App\Libraries\QrCode;
use App\Libraries\TwoFactor;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Two-step verification (TOTP) for the signed-in user: set up with a QR code
 * or manual key, confirm with a code, see recovery codes once, regenerate
 * them, or turn the feature off (unless the user's role requires it).
 */
class DuaLangkah extends BaseController
{
    /**
     * Session key for recovery codes waiting to be shown once.
     */
    private const CODES_KEY = 'spmb_recovery_codes';

    public function index(): string
    {
        $user      = auth()->user();
        $twoFactor = service('twoFactor');
        $enabled   = $twoFactor->isEnabled((int) $user->id);
        $setup     = $enabled ? null : $twoFactor->pendingSetup((int) $user->id, (string) $user->email);

        return view('Modules\Profil\Views\two_factor', [
            'title'       => lang('Akun.tfTitle'),
            'subtitle'    => lang('Akun.tfIntro'),
            'breadcrumbs' => $this->breadcrumbs(),
            'enabled'     => $enabled,
            'required'    => StaffArea::roleRequiresTwoFactor($user),
            'remaining'   => $enabled ? $twoFactor->remainingRecoveryCodes((int) $user->id) : 0,
            'total'       => TwoFactor::RECOVERY_CODE_COUNT,
            'secret'      => $setup === null ? null : trim(chunk_split($setup['secret'], 4, ' ')),
            'qr'          => $setup === null ? null : QrCode::svgDataUri($setup['uri']),
        ]);
    }

    public function enable(): RedirectResponse
    {
        $user  = auth()->user();
        $codes = service('twoFactor')->confirmSetup((int) $user->id, (string) $this->request->getPost('code'));

        if ($codes === null) {
            return redirect()->route('profil.dua-langkah')->with('errors', ['code' => lang('Akun.tfWrongCode')]);
        }

        session()->set(self::CODES_KEY, $codes);

        return redirect()->route('profil.dua-langkah.kode')->with('message', lang('Akun.tfEnabled'));
    }

    /**
     * Shows freshly created recovery codes exactly once.
     */
    public function codes(): ResponseInterface|string
    {
        $codes = session(self::CODES_KEY);
        session()->remove(self::CODES_KEY);

        if (! is_array($codes) || $codes === []) {
            return redirect()->route('profil.dua-langkah')->with('warning', lang('Akun.tfCodesGone'));
        }

        $this->response->setHeader('Cache-Control', 'no-store');

        return view('Modules\Profil\Views\recovery_codes', [
            'title'       => lang('Akun.tfCodesTitle'),
            'subtitle'    => lang('Akun.tfCodesIntro'),
            'breadcrumbs' => [...array_slice($this->breadcrumbs(true), 0, 3), ['label' => 'Kode Cadangan']],
            'codes'       => $codes,
        ]);
    }

    public function regenerate(): RedirectResponse
    {
        $user = auth()->user();

        if (! service('twoFactor')->isEnabled((int) $user->id)) {
            return redirect()->route('profil.dua-langkah');
        }

        if (! $this->passwordMatches()) {
            return redirect()->route('profil.dua-langkah')->with('errors', ['regen_password' => lang('Akun.passwordWrong')]);
        }

        session()->set(self::CODES_KEY, service('twoFactor')->regenerateRecoveryCodes((int) $user->id));
        service('audit')->log('2fa_recovery_regenerated', 'users', (int) $user->id, null, null, 'Kode cadangan dibuat ulang');

        return redirect()->route('profil.dua-langkah.kode');
    }

    public function disable(): RedirectResponse
    {
        $user = auth()->user();

        if (StaffArea::roleRequiresTwoFactor($user)) {
            return redirect()->route('profil.dua-langkah')->with('error', lang('Akun.tfCannotDisable'));
        }

        if (! $this->passwordMatches()) {
            return redirect()->route('profil.dua-langkah')->with('errors', ['disable_password' => lang('Akun.passwordWrong')]);
        }

        service('twoFactor')->disable((int) $user->id);

        return redirect()->route('profil.dua-langkah')->with('message', lang('Akun.tfDisabled'));
    }

    private function passwordMatches(): bool
    {
        $password = (string) $this->request->getPost('password');

        return $password !== '' && service('passwords')->verify($password, (string) auth()->user()->password_hash);
    }

    /**
     * @return list<array{label: string, url?: string}>
     */
    private function breadcrumbs(bool $linkLast = false): array
    {
        $last = ['label' => 'Verifikasi Dua Langkah'];

        if ($linkLast) {
            $last['url'] = 'admin/profil/verifikasi-dua-langkah';
        }

        return [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Profil Saya', 'url' => 'admin/profil'], $last];
    }
}
