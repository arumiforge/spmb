<?php

declare(strict_types=1);

namespace Tests\Support\Berkas;

use App\Libraries\ApplicantAccess;
use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use Modules\Berkas\Database\Seeds\InstallSeeder;
use Modules\Berkas\Models\ApplicantDocumentModel;
use Modules\Berkas\Models\DocumentTypeModel;
use Modules\Berkas\Services\TemplateStorage;
use Modules\Pendaftaran\Models\ApplicantModel;

/**
 * Shared set-up for Berkas tests: default document types, applicants inserted
 * through ApplicantModel, staff users, uploaded-file simulation and clean-up of
 * stored files.
 */
trait BerkasTestHelpers
{
    /**
     * @var list<string> temporary copies handed to the upload code
     */
    private array $tmpFiles = [];

    protected function fixture(string $name): string
    {
        return str_starts_with($name, '/') ? $name : __DIR__ . '/' . $name;
    }

    /**
     * Copy of a fixture in the temp dir, as PHP would hold an upload.
     */
    protected function tmpCopy(string $fixture): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'berkas-test');
        copy($this->fixture($fixture), $path);
        $this->tmpFiles[] = $path;

        return $path;
    }

    /**
     * Fakes $_FILES['berkas'] (or another field) for the next request. A
     * fixture path starting with "/" is used as is.
     */
    protected function fakeUpload(string $fixture, ?string $clientName = null, int $error = UPLOAD_ERR_OK, string $field = 'berkas'): void
    {
        $tmp    = $this->tmpCopy($fixture);
        $_FILES = [$field => [
            'name'     => $clientName ?? $fixture,
            'type'     => 'application/octet-stream',
            'tmp_name' => $tmp,
            'error'    => $error,
            'size'     => filesize($tmp),
        ]];
        service('superglobals')->setFilesArray($_FILES);
    }

    protected function clearUpload(): void
    {
        $_FILES = [];
        service('superglobals')->setFilesArray([]);
    }

    /**
     * Loads the route table, for rendering views outside a request.
     */
    protected function loadRoutes(): void
    {
        service('routes')->loadRoutes();
    }

    protected function seedTypes(): void
    {
        (new InstallSeeder(config('Database')))->run();
    }

    /**
     * @return array<string, mixed>
     */
    protected function type(string $code): array
    {
        return model(DocumentTypeModel::class, false)->where('code', $code)->first();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function makeApplicant(array $overrides = []): array
    {
        static $n = 0;
        $n++;
        $model = model(ApplicantModel::class, false);
        $id    = $model->insert($overrides + [
            'registration_number' => sprintf('PMB-2027-%04d', $n + random_int(0, 9000)),
            'academic_year'       => '2027/2028',
            'method'              => 'online',
            'status'              => 'registered',
            'document_status'     => 'incomplete',
            'full_name'           => 'Ahmad Fauzi ' . $n,
            'name_key'            => 'ahmad fauzi ' . $n,
            'gender'              => 'L',
            'birth_place'         => 'Kudus',
            'birth_date'          => '2014-05-17',
            'parent_phone'        => '081234567890',
            'father_name'         => 'Bapak Fauzi',
            'registered_at'       => date('Y-m-d H:i:s', time() - 3600 + $n),
        ]);

        return $model->find($id);
    }

    protected function makeUser(string $username, string $group): User
    {
        $users = model(UserModel::class, false);
        $users->save(new User(['username' => $username, 'email' => $username . '@example.test', 'password' => 'Rahasia-Kuat-2027', 'full_name' => ucfirst($username) . ' Test']));
        $user = $users->findById($users->getInsertID());
        $user->addGroup($group);
        $user->activate();

        return $user;
    }

    /**
     * Session data granting access to one applicant (as after "Cek Status").
     *
     * @return array<string, mixed>
     */
    protected function grantSession(int $applicantId): array
    {
        return [ApplicantAccess::SESSION_KEY => ['id' => $applicantId, 'expires' => time() + 600]];
    }

    protected function removeStoredFiles(): void
    {
        foreach ($this->tmpFiles as $tmp) {
            @unlink($tmp);
        }

        $this->tmpFiles = [];

        if (! db_connect()->tableExists('applicant_documents')) {
            return;
        }

        $rows = model(ApplicantDocumentModel::class, false)->withDeleted()->where('file_path IS NOT NULL')->findAll();

        foreach ($rows as $row) {
            @unlink(WRITEPATH . 'uploads/berkas/' . $row['file_path']);
        }

        // Example files of the test's document types only (the folder is shared with the dev app).
        if (db_connect()->fieldExists('template_path', 'document_types')) {
            foreach (model(DocumentTypeModel::class, false)->withDeleted()->where('template_path IS NOT NULL')->findColumn('template_path') ?? [] as $stored) {
                (new TemplateStorage())->delete((string) $stored);
            }
        }

        $this->clearUpload();
    }
}
