<?php

declare(strict_types=1);

namespace Modules\Pengguna\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Entities\User;

/**
 * Staff account management: list, create, edit, roles, deactivate, reset
 * password and reset two-step verification.
 *
 * Super Admin accounts and the Super Admin role can only be managed with the
 * pengguna.kelola-admin permission (Super Admin). Admin Sekolah may manage
 * other Admin Sekolah accounts (D-024).
 */
class Pengguna extends BaseController
{
    /**
     * Roles that need pengguna.kelola-admin to assign or manage.
     */
    public const PROTECTED_ROLES = ['superadmin'];

    private const PER_PAGE = 20;

    private UserModel $users;

    public function __construct()
    {
        $this->users = model(UserModel::class);
    }

    public function index(): string
    {
        $q      = trim((string) $this->request->getGet('q'));
        $role   = (string) $this->request->getGet('peran');
        $status = (string) $this->request->getGet('status');
        $groups = config('AuthGroups')->groups;
        $tables = config('Auth')->tables;

        $builder = $this->users
            ->select('users.*')
            ->join($tables['identities'] . ' AS idn', "idn.user_id = users.id AND idn.type = 'email_password'", 'left');

        if ($q !== '') {
            $builder->groupStart()
                ->like('users.full_name', $q)
                ->orLike('users.username', $q)
                ->orLike('idn.secret', $q)
                ->groupEnd();
        }

        if (isset($groups[$role])) {
            $builder->whereIn('users.id', static fn ($sub) => $sub->select('user_id')->from($tables['groups_users'])->where('group', $role));
        }

        if ($status === 'aktif') {
            $builder->groupStart()->where('users.status IS NULL')->orWhere('users.status !=', 'banned')->groupEnd();
        } elseif ($status === 'nonaktif') {
            $builder->where('users.status', 'banned');
        }

        $rows = $builder->orderBy('users.full_name', 'ASC')->orderBy('users.username', 'ASC')->paginate(self::PER_PAGE);

        $twoFactorIds = $rows === [] ? [] : array_map(
            'intval',
            array_column(db_connect()->table('user_two_factor')->select('user_id')
                ->whereIn('user_id', array_map(static fn (User $u): int => (int) $u->id, $rows))
                ->where('confirmed_at IS NOT NULL')->get()->getResultArray(), 'user_id'),
        );

        return view('Modules\Pengguna\Views\index', [
            'title'        => lang('Pengguna.listTitle'),
            'subtitle'     => lang('Pengguna.listIntro'),
            'breadcrumbs'  => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengguna']],
            'users'        => $rows,
            'pager'        => $this->users->pager,
            'groups'       => $groups,
            'twoFactorIds' => $twoFactorIds,
            'filters'      => ['q' => $q, 'peran' => $role, 'status' => $status],
            'canCreate'    => auth()->user()->can('pengguna.create'),
            'canEdit'      => auth()->user()->can('pengguna.edit'),
        ]);
    }

    public function new(): string
    {
        return view('Modules\Pengguna\Views\form', [
            'title'       => lang('Pengguna.addTitle'),
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengguna', 'url' => 'admin/pengguna'], ['label' => 'Tambah']],
            'target'      => null,
            'roleOptions' => $this->roleOptions(),
            'selected'    => [],
        ]);
    }

    public function create(): RedirectResponse
    {
        $roles = $this->postedRoles();

        if (! $this->validate($this->rules(null))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($roles === null) {
            return redirect()->back()->withInput()->with('errors', ['roles' => lang('Pengguna.rolesRequired')]);
        }

        $password = self::temporaryPassword();
        $user     = new User([
            'username'  => strtolower(trim((string) $this->request->getPost('username'))),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'full_name' => trim((string) $this->request->getPost('full_name')),
            'password'  => $password,
        ]);

        $this->users->save($user);
        $user = $this->users->findById($this->users->getInsertID());
        $user->syncGroups(...$roles);
        $user->activate();
        $user->forcePasswordReset();

        service('audit')->log('user_roles_changed', 'users', (int) $user->id, ['roles' => []], ['roles' => $roles], 'Peran pengguna baru diatur');

        return redirect()->route('pengguna.ubah', [$user->id])
            ->with('message', lang('Pengguna.created', [$user->full_name]))
            ->with('temp_password', $password);
    }

    public function edit(int $id): RedirectResponse|string
    {
        $target = $this->findManageable($id);

        if ($target instanceof RedirectResponse) {
            return $target;
        }

        return view('Modules\Pengguna\Views\form', [
            'title'       => lang('Pengguna.editTitle'),
            'subtitle'    => $target->full_name ?: $target->username,
            'breadcrumbs' => [['label' => 'Dasbor', 'url' => 'admin'], ['label' => 'Pengguna', 'url' => 'admin/pengguna'], ['label' => 'Ubah']],
            'target'      => $target,
            'isSelf'      => (int) $target->id === (int) auth()->id(),
            'twoFactorOn' => service('twoFactor')->isEnabled((int) $target->id),
            'roleOptions' => $this->roleOptions(),
            'selected'    => $target->getGroups() ?? [],
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $target = $this->findManageable($id);

        if ($target instanceof RedirectResponse) {
            return $target;
        }

        if (! $this->validate($this->rules((int) $target->id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $isSelf = (int) $target->id === (int) auth()->id();
        $roles  = $isSelf ? $target->getGroups() : $this->postedRoles($target->getGroups() ?? []);

        if ($roles === null || $roles === []) {
            return redirect()->back()->withInput()->with('errors', ['roles' => lang('Pengguna.rolesRequired')]);
        }

        $target->fill([
            'username'  => strtolower(trim((string) $this->request->getPost('username'))),
            'email'     => strtolower(trim((string) $this->request->getPost('email'))),
            'full_name' => trim((string) $this->request->getPost('full_name')),
        ]);

        $this->users->save($target);

        $before = $target->getGroups() ?? [];
        sort($before);
        sort($roles);

        if ($before !== $roles) {
            $target->syncGroups(...$roles);
            service('audit')->log('user_roles_changed', 'users', (int) $target->id, ['roles' => $before], ['roles' => $roles], 'Peran pengguna diubah');
        }

        return redirect()->route('pengguna.ubah', [$target->id])->with('message', lang('Pengguna.updated'));
    }

    public function deactivate(int $id): RedirectResponse
    {
        $target = $this->findManageable($id, false);

        if ($target instanceof RedirectResponse) {
            return $target;
        }

        $target->ban(lang('Pengguna.bannedMessage'));
        service('audit')->log('user_deactivated', 'users', (int) $target->id, null, null, 'Akun dinonaktifkan');

        return redirect()->route('pengguna.ubah', [$target->id])->with('message', lang('Pengguna.deactivated', [$target->full_name ?: $target->username]));
    }

    public function activate(int $id): RedirectResponse
    {
        $target = $this->findManageable($id, false);

        if ($target instanceof RedirectResponse) {
            return $target;
        }

        $target->unBan();
        service('audit')->log('user_activated', 'users', (int) $target->id, null, null, 'Akun diaktifkan kembali');

        return redirect()->route('pengguna.ubah', [$target->id])->with('message', lang('Pengguna.activated', [$target->full_name ?: $target->username]));
    }

    public function resetPassword(int $id): RedirectResponse
    {
        $target = $this->findManageable($id, false);

        if ($target instanceof RedirectResponse) {
            return $target;
        }

        $password = self::temporaryPassword();
        $target->setPassword($password);
        $this->users->save($target);
        $target->forcePasswordReset();

        // Sign the user out everywhere: remove remember-me tokens.
        db_connect()->table(config('Auth')->tables['remember_tokens'])->where('user_id', $target->id)->delete();

        service('audit')->log('password_reset', 'users', (int) $target->id, null, null, 'Kata sandi dibuat ulang oleh admin');

        return redirect()->route('pengguna.ubah', [$target->id])
            ->with('message', lang('Pengguna.resetDone', [$target->full_name ?: $target->username]))
            ->with('temp_password', $password);
    }

    public function resetTwoFactor(int $id): RedirectResponse
    {
        $target = $this->findManageable($id, false);

        if ($target instanceof RedirectResponse) {
            return $target;
        }

        service('twoFactor')->disable((int) $target->id);

        return redirect()->route('pengguna.ubah', [$target->id])->with('message', lang('Pengguna.twoFactorReset', [$target->full_name ?: $target->username]));
    }

    /**
     * Readable one-time password, e.g. "Kp7w-Tq3x-Mz9d" (no 0/O, 1/l/I).
     */
    public static function temporaryPassword(): string
    {
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $groups   = [];

        for ($g = 0; $g < 3; $g++) {
            $part = '';

            for ($i = 0; $i < 4; $i++) {
                $part .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $groups[] = $part;
        }

        return implode('-', $groups);
    }

    /**
     * Loads a user the current admin may manage, or a redirect explaining why not.
     */
    private function findManageable(int $id, bool $allowSelf = true): RedirectResponse|User
    {
        $target = $this->users->findById($id);

        if (! $target instanceof User) {
            throw PageNotFoundException::forPageNotFound(lang('Pengguna.notFound'));
        }

        if (! $allowSelf && (int) $target->id === (int) auth()->id()) {
            return redirect()->route('pengguna.ubah', [$id])->with('error', lang('Pengguna.cannotSelf'));
        }

        if (! $this->canManageAdmins() && array_intersect($target->getGroups() ?? [], self::PROTECTED_ROLES) !== [] && (int) $target->id !== (int) auth()->id()) {
            return redirect()->route('pengguna')->with('error', lang('Pengguna.protectedAccount'));
        }

        return $target;
    }

    private function canManageAdmins(): bool
    {
        return auth()->user()->can('pengguna.kelola-admin');
    }

    /**
     * @return array<string, array{title: string, description: string, locked: bool}>
     */
    private function roleOptions(): array
    {
        $options = [];

        foreach (config('AuthGroups')->groups as $key => $group) {
            $options[$key] = $group + ['locked' => in_array($key, self::PROTECTED_ROLES, true) && ! $this->canManageAdmins()];
        }

        return $options;
    }

    /**
     * Roles from the form that the admin may assign. Locked roles the user
     * already has are kept as they are.
     *
     * @param list<string> $current
     *
     * @return list<string>|null Null when nothing valid was chosen.
     */
    private function postedRoles(array $current = []): ?array
    {
        $options = $this->roleOptions();
        $roles   = [];

        foreach ((array) $this->request->getPost('roles') as $role) {
            if (isset($options[$role]) && ! $options[$role]['locked']) {
                $roles[] = $role;
            }
        }

        foreach ($current as $role) {
            if (isset($options[$role]) && $options[$role]['locked']) {
                $roles[] = $role;
            }
        }

        $roles = array_values(array_unique($roles));

        return $roles === [] ? null : $roles;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rules(?int $id): array
    {
        $identities = config('Auth')->tables['identities'];
        $ignoreUser = $id === null ? '' : ',id,' . $id;
        $ignoreIdn  = $id === null ? '' : ',user_id,' . $id;

        return [
            'full_name' => ['label' => lang('Pengguna.fullName'), 'rules' => 'required|max_length[100]'],
            'username'  => [
                'label'  => lang('Pengguna.username'),
                'rules'  => 'required|min_length[3]|max_length[30]|regex_match[/\A[a-zA-Z0-9.]+\z/]|is_unique[users.username' . $ignoreUser . ']',
                'errors' => ['is_unique' => 'Nama pengguna ini sudah dipakai. Pilih nama lain.', 'regex_match' => 'Nama pengguna hanya boleh berisi huruf, angka, atau titik.'],
            ],
            'email' => [
                'label'  => lang('Pengguna.email'),
                'rules'  => 'required|valid_email|max_length[254]|is_unique[' . $identities . '.secret' . $ignoreIdn . ']',
                'errors' => ['is_unique' => 'Email ini sudah dipakai akun lain.'],
            ],
        ];
    }
}
