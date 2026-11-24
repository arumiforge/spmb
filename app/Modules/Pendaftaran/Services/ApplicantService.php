<?php

declare(strict_types=1);

namespace Modules\Pendaftaran\Services;

use CodeIgniter\Events\Events;
use InvalidArgumentException;
use Modules\Pendaftaran\Exceptions\RegistrationClosed;
use Modules\Pendaftaran\Libraries\ApplicantForm;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use RuntimeException;
use Throwable;

/**
 * Business rules for applicants: registration (number + insert in one
 * transaction), edits, status changes, duplicate review and private links.
 * Every write goes through ApplicantModel, so it is audited (D-015).
 *
 * Events (CodeIgniter Events) for other modules: applicant_registered(array $applicant),
 * applicant_status_changed(array $applicant, string $oldStatus).
 */
class ApplicantService
{
    public const METHODS  = ['online', 'offline'];
    public const STATUSES = ['registered', 'verified', 'cancelled'];

    private ApplicantModel $applicants;
    private WaveModel $waves;

    public function __construct(?ApplicantModel $applicants = null, ?WaveModel $waves = null)
    {
        $this->applicants = $applicants ?? model(ApplicantModel::class);
        $this->waves      = $waves ?? model(WaveModel::class);
    }

    /**
     * Registers an applicant. $data holds cleaned form fields (see ApplicantForm);
     * offline registrations may pass 'wave_id' (staff choice) and 'notes'.
     *
     * Online: needs onlineOpen and a wave open today with room left, otherwise
     * throws RegistrationClosed. Offline: the chosen wave, else today's wave, else none.
     *
     * Returns the new row, the plain private-link token (shown once, only its
     * SHA-256 is stored, D-034) and the possible duplicates found (staff only).
     *
     * @param array<string, mixed> $data
     *
     * @return array{applicant: array<string, mixed>, token: string, duplicates: list<array<string, mixed>>}
     *
     * @throws RegistrationClosed
     */
    public function register(array $data, string $method, ?int $staffId = null): array
    {
        if (! in_array($method, self::METHODS, true)) {
            throw new InvalidArgumentException('Unknown registration method: ' . $method);
        }

        $year = (string) setting('Pendaftaran.academicYear');
        $wave = $this->pickWave($data, $method, $year);

        $fields = array_intersect_key($data, array_flip([...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE, ...ApplicantForm::CHOICES, ...ApplicantForm::REGION_CODES, 'previous_school_id']));

        if ($method === 'offline') {
            $fields['notes'] = $data['notes'] ?? null;
        }

        $fields['name_key']      = DuplicateChecker::nameKey((string) ($fields['full_name'] ?? ''));
        $fields['academic_year'] = $year;
        $duplicates              = (new DuplicateChecker())->find($fields);
        $token                   = self::newToken();

        $row = $fields + [
            'wave_id'            => $wave['id'] ?? null,
            'method'             => $method,
            'status'             => 'registered',
            'possible_duplicate' => $duplicates === [] ? 0 : 1,
            'access_token_hash'  => hash('sha256', $token),
            'created_by'         => $method === 'offline' ? $staffId : null,
            'registered_at'      => date('Y-m-d H:i:s'),
        ];

        $db = db_connect();
        $db->transBegin();

        try {
            $row['registration_number'] = (new RegistrationNumber($db))->next($year);

            if ($this->applicants->insert($row) === false) {
                throw new RuntimeException('Applicant could not be saved: ' . implode(' ', $this->applicants->errors()));
            }

            $id = (int) $this->applicants->getInsertID();
            service('audit')->log('applicant_registered', 'applicants', $id, null, [
                'registration_number' => $row['registration_number'],
                'method'              => $method,
                'wave_id'             => $row['wave_id'],
                'possible_duplicate'  => $row['possible_duplicate'],
            ], 'Pendaftaran baru ' . $row['registration_number'], $staffId);
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        $db->transCommit();

        $applicant = $this->applicants->find($id);
        Events::trigger('applicant_registered', $applicant);

        return ['applicant' => $applicant, 'token' => $token, 'duplicates' => $duplicates];
    }

    /**
     * Why online registration is closed right now, or null when it is open.
     * Returns 'switched_off', 'no_wave' or 'full'.
     */
    public function onlineClosedReason(): ?string
    {
        if (! setting('Pendaftaran.onlineOpen')) {
            return 'switched_off';
        }

        $wave = $this->waves->current();

        if ($wave === null) {
            return 'no_wave';
        }

        return $this->waves->isFull($wave) ? 'full' : null;
    }

    /**
     * Saves changed fields. Recomputes name_key and flags a possible duplicate
     * when identity fields now match another applicant.
     *
     * @param array<string, mixed> $changes Cleaned fields
     *
     * @return array<string, mixed> The updated applicant
     */
    public function update(int $id, array $changes): array
    {
        $current = $this->applicants->find($id) ?? throw new InvalidArgumentException('Applicant not found: ' . $id);
        $changes = array_intersect_key($changes, array_flip([...ApplicantForm::CORE, ...ApplicantForm::CONFIGURABLE, ...ApplicantForm::CHOICES, ...ApplicantForm::REGION_CODES, ...ApplicantForm::STAFF_ONLY, 'previous_school_id']));

        if (array_key_exists('full_name', $changes)) {
            $changes['name_key'] = DuplicateChecker::nameKey((string) $changes['full_name']);
        }

        $identity = array_intersect_key($changes, array_flip(['nik', 'nisn', 'name_key', 'birth_date']));

        if ($identity !== [] && array_diff_assoc(array_map('strval', $identity), array_map('strval', array_intersect_key($current, $identity))) !== []) {
            if ((new DuplicateChecker())->find($changes + $current, $id) !== []) {
                $changes['possible_duplicate'] = 1;
            }
        }

        $changes = array_filter($changes, static fn ($value, string $key): bool => (string) ($current[$key] ?? '') !== (string) ($value ?? ''), ARRAY_FILTER_USE_BOTH);

        if ($changes !== []) {
            $this->applicants->update($id, $changes);
        }

        return $this->applicants->find($id);
    }

    /**
     * Changes the applicant status (registered / verified / cancelled), audited
     * with event applicant_status_changed.
     *
     * @return array<string, mixed>
     */
    public function changeStatus(int $id, string $status, ?string $note = null): array
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Unknown status: ' . $status);
        }

        $current = $this->applicants->find($id) ?? throw new InvalidArgumentException('Applicant not found: ' . $id);

        if ($current['status'] === $status) {
            return $current;
        }

        $this->applicants->update($id, ['status' => $status]);
        service('audit')->log('applicant_status_changed', 'applicants', $id, ['status' => $current['status']], ['status' => $status], $note === null || $note === '' ? null : $note);

        $applicant = $this->applicants->find($id);
        Events::trigger('applicant_status_changed', $applicant, (string) $current['status']);

        return $applicant;
    }

    /**
     * Committee checked a possible duplicate (D-032): clears the flag, audited
     * as duplicate_reviewed with the note.
     */
    public function reviewDuplicate(int $id, string $note): void
    {
        $current = $this->applicants->find($id) ?? throw new InvalidArgumentException('Applicant not found: ' . $id);

        $this->applicants->update($id, ['possible_duplicate' => 0]);
        service('audit')->log('duplicate_reviewed', 'applicants', $id, ['possible_duplicate' => (int) $current['possible_duplicate']], ['possible_duplicate' => 0], mb_substr($note, 0, 255));
    }

    /**
     * Replaces the private link of an applicant (old link stops working) and
     * returns the new plain token. Audited as private_link_created.
     */
    public function renewPrivateLink(int $id): string
    {
        $token = self::newToken();
        $this->applicants->update($id, ['access_token_hash' => hash('sha256', $token)]);
        service('audit')->log('private_link_created', 'applicants', $id, null, null, 'Tautan pribadi baru dibuat');

        return $token;
    }

    /**
     * Random private-link token: 32 bytes, base64url (43 characters).
     */
    public static function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null
     */
    private function pickWave(array $data, string $method, string $year): ?array
    {
        if ($method === 'online') {
            $reason = $this->onlineClosedReason();

            if ($reason !== null) {
                throw new RegistrationClosed($reason);
            }

            return $this->waves->current();
        }

        if (! empty($data['wave_id'])) {
            $wave = $this->waves->find((int) $data['wave_id']);

            if ($wave === null || $wave['academic_year'] !== $year) {
                throw new InvalidArgumentException('Wave does not belong to the academic year.');
            }

            return $wave;
        }

        return $this->waves->current();
    }
}
