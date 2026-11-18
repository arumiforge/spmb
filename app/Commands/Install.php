<?php

declare(strict_types=1);

namespace App\Commands;

use App\Database\Seeds\DemoUserSeeder;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;
use Throwable;

/**
 * Installs or updates SPMB: checks configuration, runs all migrations and
 * creates the first Super Admin. Safe to run again (idempotent).
 */
class Install extends BaseCommand
{
    protected $group       = 'SPMB';
    protected $name        = 'spmb:install';
    protected $description = 'Check configuration, run all migrations and create the first Super Admin.';
    protected $usage       = 'spmb:install [options]';
    protected $options     = [
        '--username'      => 'Super Admin username (letters, numbers, dots)',
        '--email'         => 'Super Admin email',
        '--name'          => 'Super Admin full name',
        '--password'      => 'Super Admin password (prompted if omitted; prefer the prompt)',
        '--demo'          => 'Also create one demo account per role (development only)',
        '--demo-password' => 'Password for demo accounts (default: Demo-SPMB-2027)',
    ];

    public function run(array $params)
    {
        CLI::write('SPMB installer', 'green');
        CLI::newLine();

        if (! $this->checkEncryptionKey() || ! $this->checkDatabase()) {
            return EXIT_ERROR;
        }

        CLI::write('Running migrations (all namespaces)...');
        service('migrations')->setNamespace(null)->latest();
        CLI::write('  Database is up to date.', 'green');

        if (! $this->ensureSuperAdmin()) {
            return EXIT_ERROR;
        }

        $this->runModuleSeeders('InstallSeeder');

        if (CLI::getOption('demo') !== null) {
            if (! $this->createDemoUsers()) {
                return EXIT_ERROR;
            }

            $this->runModuleSeeders('DemoSeeder');
        }

        CLI::newLine();
        CLI::write('Done. Start the app with "php spark serve" and sign in at /masuk.', 'green');

        return EXIT_SUCCESS;
    }

    private function checkEncryptionKey(): bool
    {
        if (config('Encryption')->key === '') {
            CLI::error('encryption.key is not set. Run "php spark key:generate" first.');

            return false;
        }

        return true;
    }

    private function checkDatabase(): bool
    {
        try {
            db_connect()->initialize();
        } catch (Throwable $e) {
            CLI::error('Cannot connect to the database: ' . $e->getMessage());
            CLI::write('Check database.default.* in .env.');

            return false;
        }

        return true;
    }

    private function ensureSuperAdmin(): bool
    {
        $existing = db_connect()->table(config('Auth')->tables['groups_users'])
            ->where('group', 'superadmin')
            ->countAllResults();

        if ($existing > 0) {
            CLI::write("Super Admin already exists ({$existing}); skipping.", 'yellow');

            return true;
        }

        CLI::write('Create the first Super Admin account:');

        $username = $this->option('username', 'Username', 'required|regex_match[/\A[a-zA-Z0-9\.]{3,30}\z/]');
        $email    = $this->option('email', 'Email', 'required|valid_email');
        $name     = $this->option('name', 'Full name', 'required|max_length[100]');
        $password = $this->option('password', 'Password', 'required');

        $user = new User([
            'username'  => $username,
            'email'     => $email,
            'password'  => $password,
            'full_name' => $name,
        ]);

        $check = service('passwords')->check($password, $user);

        if (! $check->isOK()) {
            CLI::error('Password rejected: ' . $check->reason());

            return false;
        }

        $users = model(UserModel::class);

        if (! $users->save($user)) {
            CLI::error(implode(PHP_EOL, $users->errors()));

            return false;
        }

        $user = $users->findById($users->getInsertID());
        $user->addGroup('superadmin');
        $user->activate();

        CLI::write("  Super Admin \"{$username}\" created.", 'green');

        return true;
    }

    private function createDemoUsers(): bool
    {
        if (ENVIRONMENT === 'production') {
            CLI::error('Demo accounts are not allowed in production.');

            return false;
        }

        $password = (string) (CLI::getOption('demo-password') ?: DemoUserSeeder::DEFAULT_PASSWORD);
        $created  = (new DemoUserSeeder(config('Database')))->createAll($password);

        if ($created === []) {
            CLI::write('Demo accounts already exist; skipping.', 'yellow');

            return true;
        }

        CLI::write('Demo accounts (password: ' . $password . '):', 'green');
        CLI::table(array_map(static fn (array $row): array => array_values($row), $created), ['Username', 'Email', 'Role']);

        return true;
    }

    /**
     * Runs Modules\<Name>\Database\Seeds\<seeder> of every enabled module that has
     * one, in module order. InstallSeeder adds defaults (e.g. document types),
     * DemoSeeder adds demo data (development only). Both must be idempotent.
     */
    private function runModuleSeeders(string $seeder): void
    {
        foreach ($this->modulesInDependencyOrder() as $module) {
            $class = 'Modules\\' . $module . '\\Database\\Seeds\\' . $seeder;

            if (! class_exists($class)) {
                continue;
            }

            CLI::write("Seeding {$module} ({$seeder})...");
            (new $class(config('Database')))->run();
        }
    }

    /**
     * Enabled modules, each after the modules it requires (Config/Module.php
     * 'requires'), so e.g. Berkas demo data finds Pendaftaran's applicants.
     *
     * @return list<string>
     */
    private function modulesInDependencyOrder(): array
    {
        $registry = service('modules');
        $enabled  = $registry->enabled();
        $ordered  = [];
        $visit    = static function (string $module, array $path) use (&$visit, &$ordered, $registry, $enabled): void {
            if (in_array($module, $ordered, true) || in_array($module, $path, true) || ! in_array($module, $enabled, true)) {
                return;
            }

            foreach ($registry->requires($module) as $required) {
                $visit($required, [...$path, $module]);
            }

            $ordered[] = $module;
        };

        foreach ($enabled as $module) {
            $visit($module, []);
        }

        return $ordered;
    }

    private function option(string $name, string $label, string $rules): string
    {
        $value = CLI::getOption($name);

        if (is_string($value) && $value !== '') {
            $validation = service('validation', null, false)->setRules([$name => ['label' => $label, 'rules' => $rules]]);

            if ($validation->run([$name => $value])) {
                return $value;
            }

            CLI::error($validation->getError($name));
        }

        return CLI::prompt($label, null, $rules);
    }
}
