<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Session\Session;

/**
 * Remembers, in the visitor's session, which applicant they proved to be
 * (registration number + date of birth, or the private link; D-008).
 * Applicants have no account: this short-lived grant is what lets them see
 * their status, upload documents and download their card.
 *
 * Shared by Pendaftaran (grants it), Berkas and Kartu (check it). Access
 * slides forward on every use and ends after Pendaftaran.applicantSessionMinutes
 * of inactivity.
 */
class ApplicantAccess
{
    public const SESSION_KEY = 'applicant_access';

    public function __construct(
        private readonly Session $session,
        private readonly int $minutes = 30,
    ) {
    }

    /**
     * Grants access to one applicant (replaces any earlier grant).
     */
    public function grant(int $applicantId): void
    {
        $this->session->regenerate();
        $this->session->set(self::SESSION_KEY, ['id' => $applicantId, 'expires' => time() + $this->minutes * 60]);
    }

    /**
     * Id of the applicant this visitor may see, or null. Extends the grant.
     */
    public function id(): ?int
    {
        $grant = $this->session->get(self::SESSION_KEY);

        if (! is_array($grant) || ! isset($grant['id'], $grant['expires'])) {
            return null;
        }

        if ((int) $grant['expires'] < time()) {
            $this->revoke();

            return null;
        }

        $this->session->set(self::SESSION_KEY, ['id' => (int) $grant['id'], 'expires' => time() + $this->minutes * 60]);

        return (int) $grant['id'];
    }

    public function allows(int $applicantId): bool
    {
        return $this->id() === $applicantId;
    }

    public function revoke(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }
}
