<?php

declare(strict_types=1);

namespace Modules\Tes\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;
use DomainException;
use Modules\Pendaftaran\Models\ApplicantModel;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Tes\Libraries\TestSchedule;
use Modules\Tes\Models\TestAssignmentModel;
use Modules\Tes\Models\TestSessionModel;
use Modules\Tes\Services\Attendance;
use Modules\Tes\Services\Scheduler;
use Modules\Tes\Services\SchedulerException;

/**
 * Test attendance (FR-TEST-03, D-061), permission tes.kehadiran: today's
 * sessions, the session roster with Hadir / Tidak hadir buttons, card
 * scanning, moving a walk-in to this session, and marking the rest absent.
 *
 * Every write works as a plain form post (redirect back) and, when the
 * request asks for JSON (fetch on the roster), answers with the re-rendered
 * row, the session counts and the new CSRF token so the page updates
 * without a reload.
 */
class Kehadiran extends BaseController
{
    private const VIEWS = 'Modules\Tes\Views\kehadiran\\';

    public function index(): string
    {
        $today = date('Y-m-d');
        $date  = (string) $this->request->getGet('tanggal');

        if (! self::isDate($date)) {
            $date = $today;
        }

        $sessions = model(TestSessionModel::class);

        return view(self::VIEWS . 'index', [
            'title'       => lang('TesKehadiran.title'),
            'subtitle'    => lang('TesKehadiran.subtitle'),
            'breadcrumbs' => [['label' => lang('App.dashboard'), 'url' => 'admin'], ['label' => lang('Tes.breadcrumb')], ['label' => lang('TesKehadiran.title')]],
            'attendance'  => [
                'date'     => $date,
                'today'    => $today,
                'sessions' => $sessions->withStats(['date' => $date]),
                'days'     => $this->testDays($sessions->withStats()),
                'waves'    => $this->waveNames(),
            ],
        ]);
    }

    public function session(int $id): string
    {
        $session = $this->findSession($id);
        $query   = trim((string) $this->request->getGet('q'));
        $roster  = model(TestAssignmentModel::class)->forSession($id);

        if ($query !== '') {
            $needle = mb_strtolower(mb_substr($query, 0, 100));
            $roster = array_values(array_filter($roster, static fn (array $row): bool => str_contains(mb_strtolower($row['registration_number'] . ' ' . $row['full_name']), $needle)));
        }

        $label = TestSchedule::timeLabel((string) $session['starts_at'], (string) $session['ends_at']) . ' · ' . $session['room_name'];

        return view(self::VIEWS . 'session', [
            'title'       => lang('TesKehadiran.rosterTitle'),
            'subtitle'    => TestSchedule::sessionLabel($session) . (($session['label'] ?? '') !== '' ? ' · ' . $session['label'] : ''),
            'breadcrumbs' => [
                ['label' => lang('App.dashboard'), 'url' => 'admin'],
                ['label' => lang('TesKehadiran.title'), 'url' => 'admin/tes/kehadiran?tanggal=' . $session['test_date']],
                ['label' => $label],
            ],
            'roster' => [
                'session' => $session,
                'rows'    => $roster,
                'query'   => $query,
                'scan'    => session()->getFlashdata('tesScan'),
                'ended'   => Attendance::hasEnded($session),
                'canMove' => class_exists(Scheduler::class),
            ],
        ]);
    }

    /**
     * POST admin/tes/kehadiran/catat: applicant_id, status (present | absent |
     * pending = undo), session_id (optional; must be the applicant's session),
     * kembali (relative return URL).
     */
    public function record(): ResponseInterface
    {
        $applicantId = (int) $this->request->getPost('applicant_id');
        $sessionId   = (int) $this->request->getPost('session_id');
        $status      = (string) $this->request->getPost('status');
        $fallback    = $sessionId > 0 ? 'admin/tes/kehadiran/sesi/' . $sessionId : 'admin/tes/kehadiran';
        $back        = $this->safeReturn((string) $this->request->getPost('kembali'), $fallback);

        try {
            $applicant = model(ApplicantModel::class)->find($applicantId);

            if ($applicant === null) {
                throw new DomainException(lang('TesKehadiran.notApplicant'));
            }

            $current = model(TestAssignmentModel::class)->forApplicant($applicantId);

            if ($sessionId > 0 && $current !== null && (int) $current['session_id'] !== $sessionId) {
                throw new DomainException(lang('TesKehadiran.otherSession'));
            }

            $result  = (new Attendance())->record($applicantId, $status, (int) auth()->id());
            $message = $result['changed']
                ? lang('TesKehadiran.recorded.' . $status, [$applicant['full_name']])
                : lang('TesKehadiran.unchanged', [$applicant['full_name']]);
        } catch (DomainException $e) {
            return $this->answer(false, $e->getMessage(), $back, $sessionId);
        }

        return $this->answer(true, $message, $back, $sessionId, $applicantId);
    }

    /**
     * POST admin/tes/kehadiran/sesi/{id}/pindai: kode (card URL, card code or
     * registration number). Someone in this session is marked present; anyone
     * else gets an explanation and, when possible, the offer to move here.
     */
    public function scan(int $id): ResponseInterface
    {
        $session = $this->findSession($id);
        $input   = trim((string) $this->request->getPost('kode'));
        $back    = 'admin/tes/kehadiran/sesi/' . $id;
        $scan    = ['state' => 'notFound', 'input' => mb_substr($input, 0, 60), 'applicant' => null, 'schedule' => null];

        if ($input === '') {
            $scan += ['message' => lang('TesKehadiran.codeRequired')];

            return $this->answerScan($scan, $session);
        }

        try {
            $found = (new Attendance())->resolve($input);
        } catch (DomainException $e) {
            return $this->answerScan(['state' => 'revoked', 'message' => $e->getMessage()] + $scan, $session);
        }

        if ($found === null) {
            return $this->answerScan($scan + ['message' => lang('TesKehadiran.scanNotFound', [$scan['input']])], $session);
        }

        $applicant         = $found['applicant'];
        $schedule          = $found['schedule'];
        $name              = (string) $applicant['full_name'];
        $scan['applicant'] = ['id' => (int) $applicant['id'], 'number' => (string) $applicant['registration_number'], 'name' => $name];
        $scan['schedule']  = $schedule === null ? null : $schedule['label'];

        if ($applicant['status'] === 'cancelled') {
            return $this->answerScan(['state' => 'cancelled', 'message' => lang('TesKehadiran.scanCancelled', [$name])] + $scan, $session);
        }

        if ($schedule === null) {
            return $this->answerScan(['state' => 'unscheduled', 'message' => lang('TesKehadiran.scanUnscheduled', [$name])] + $scan, $session);
        }

        if ($schedule['session_id'] !== $id) {
            return $this->answerScan(['state' => 'elsewhere', 'message' => lang('TesKehadiran.scanElsewhere', [$name])] + $scan, $session);
        }

        try {
            $result = (new Attendance())->record((int) $applicant['id'], TestAssignmentModel::PRESENT, (int) auth()->id());
        } catch (DomainException $e) {
            return $this->answerScan(['state' => 'error', 'message' => $e->getMessage()] + $scan, $session);
        }

        $scan['state']   = $result['changed'] ? 'marked' : 'already';
        $scan['message'] = $result['changed']
            ? lang('TesKehadiran.scanMarked', [$name])
            : lang('TesKehadiran.scanAlready', [$name, self::clock((string) $result['attended_at'])]);

        return $this->answerScan($scan, $session);
    }

    /**
     * POST admin/tes/kehadiran/sesi/{id}/pindahkan: applicant_id, lebih_kuota.
     * Seats a walk-in here (Scheduler::move with reason "Hadir di sesi lain",
     * or assign when they had no seat), then marks them present.
     */
    public function moveHere(int $id): ResponseInterface
    {
        $session     = $this->findSession($id);
        $applicantId = (int) $this->request->getPost('applicant_id');
        $back        = 'admin/tes/kehadiran/sesi/' . $id;
        $staffId     = (int) auth()->id();

        // Package B's Scheduler is built in parallel (docs/phase-3-plan.md).
        if (! class_exists(Scheduler::class)) {
            return $this->answer(false, lang('TesKehadiran.moveUnavailable'), $back, $id);
        }

        try {
            $applicant = model(ApplicantModel::class)->find($applicantId);

            if ($applicant === null) {
                throw new DomainException(lang('TesKehadiran.notApplicant'));
            }

            if ($applicant['status'] === 'cancelled') {
                throw new DomainException(lang('TesKehadiran.cancelled'));
            }

            $current   = model(TestAssignmentModel::class)->forApplicant($applicantId);
            $scheduler = new Scheduler();

            if ($current === null) {
                $scheduler->assign($applicantId, $id, $staffId, lang('TesKehadiran.moveReason'), (bool) $this->request->getPost('lebih_kuota'));
            } elseif ((int) $current['session_id'] !== $id) {
                $scheduler->move($applicantId, $id, $staffId, lang('TesKehadiran.moveReason'), (bool) $this->request->getPost('lebih_kuota'));
            }

            (new Attendance())->record($applicantId, TestAssignmentModel::PRESENT, $staffId);
        } catch (DomainException|SchedulerException $e) {
            return $this->answer(false, $e->getMessage(), $back, $id);
        }

        $name = (string) $applicant['full_name'];
        $scan = [
            'state'     => 'marked',
            'message'   => lang('TesKehadiran.moveDone', [$name]),
            'input'     => '',
            'applicant' => ['id' => $applicantId, 'number' => (string) $applicant['registration_number'], 'name' => $name],
            'schedule'  => null,
        ];

        return $this->answerScan($scan, $this->findSession((int) $session['id']));
    }

    /**
     * POST admin/tes/kehadiran/sesi/{id}/tidak-hadir-semua.
     */
    public function markRemainingAbsent(int $id): ResponseInterface
    {
        $this->findSession($id);
        $back = site_url('admin/tes/kehadiran/sesi/' . $id);

        try {
            $count = (new Attendance())->markRemainingAbsent($id, (int) auth()->id());
        } catch (DomainException $e) {
            return redirect()->to($back)->with('error', $e->getMessage());
        }

        return redirect()->to($back)->with('message', $count === 0 ? lang('TesKehadiran.allAbsentNone') : lang('TesKehadiran.allAbsentDone', [$count]));
    }

    /**
     * JSON for fetch, otherwise a redirect with a flash message.
     */
    private function answer(bool $ok, string $message, string $back, int $sessionId, ?int $applicantId = null): ResponseInterface
    {
        if (! $this->wantsJson()) {
            return redirect()->to($back)->with($ok ? 'message' : 'error', $message);
        }

        $body = ['ok' => $ok, 'message' => $message, 'csrf' => csrf_hash()];

        if ($ok && $sessionId > 0 && $applicantId !== null) {
            $body += $this->rosterUpdate($sessionId, $applicantId);
        }

        return $this->response->setStatusCode($ok ? 200 : 422)->setJSON($body);
    }

    /**
     * @param array<string, mixed> $scan    state, message, input, applicant, schedule
     * @param array<string, mixed> $session session with stats
     */
    private function answerScan(array $scan, array $session): ResponseInterface
    {
        $scan['sessionId']   = (int) $session['id'];
        $scan['sessionFull'] = (int) $session['free'] === 0;

        if (! $this->wantsJson()) {
            return redirect()->to(site_url('admin/tes/kehadiran/sesi/' . $session['id']))->with('tesScan', $scan);
        }

        $body = [
            'ok'         => in_array($scan['state'], ['marked', 'already'], true),
            'state'      => $scan['state'],
            'message'    => $scan['message'],
            'resultHtml' => view(self::VIEWS . '_scan_result', ['scan' => $scan], ['saveData' => false]),
            'csrf'       => csrf_hash(),
        ];

        if ($scan['state'] === 'marked' && $scan['applicant'] !== null) {
            $body += $this->rosterUpdate((int) $session['id'], $scan['applicant']['id']);
        }

        return $this->response->setJSON($body);
    }

    /**
     * The re-rendered roster row of the applicant and the session counts.
     *
     * @return array{applicantId: int, rowHtml: string|null, counts: array<string, int>}
     */
    private function rosterUpdate(int $sessionId, int $applicantId): array
    {
        $row = null;

        foreach (model(TestAssignmentModel::class)->forSession($sessionId) as $candidate) {
            if ((int) $candidate['applicant_id'] === $applicantId) {
                $row = $candidate;
                break;
            }
        }

        $session = model(TestSessionModel::class)->findWithStats($sessionId);

        return [
            'applicantId' => $applicantId,
            'rowHtml'     => $row === null || $session === null ? null : view(self::VIEWS . '_row', ['entry' => ['row' => $row, 'sessionId' => $sessionId]], ['saveData' => false]),
            'counts'      => $session === null ? [] : self::counts($session),
        ];
    }

    /**
     * @param array<string, mixed> $session session with stats
     *
     * @return array{filled: int, present: int, absent: int, pending: int}
     */
    public static function counts(array $session): array
    {
        return [
            'filled'  => (int) $session['filled'],
            'present' => (int) $session['present'],
            'absent'  => (int) $session['absent'],
            'pending' => (int) $session['pending'],
        ];
    }

    /**
     * "07.42" from a DATETIME.
     */
    public static function clock(string $datetime): string
    {
        return $datetime === '' ? '' : str_replace(':', '.', substr($datetime, 11, 5));
    }

    /**
     * @return array<string, mixed>
     */
    private function findSession(int $id): array
    {
        $session = model(TestSessionModel::class)->findWithStats($id);

        if ($session === null) {
            throw PageNotFoundException::forPageNotFound(lang('TesKehadiran.notFound'));
        }

        return $session;
    }

    /**
     * Dates that have sessions this academic year, with the number of sessions.
     *
     * @param list<array<string, mixed>> $sessions
     *
     * @return array<string, int> date => sessions
     */
    private function testDays(array $sessions): array
    {
        $days = [];

        foreach ($sessions as $session) {
            $days[$session['test_date']] = ($days[$session['test_date']] ?? 0) + 1;
        }

        return $days;
    }

    /**
     * @return array<int, string> wave id => name
     */
    private function waveNames(): array
    {
        $names = [];

        foreach (model(WaveModel::class)->withDeleted()->findAll() as $wave) {
            $names[(int) $wave['id']] = (string) $wave['name'];
        }

        return $names;
    }

    private function wantsJson(): bool
    {
        return str_contains($this->request->getHeaderLine('Accept'), 'application/json');
    }

    /**
     * A site URL from a relative path given by the form ("kartu/<token>",
     * "admin/kartu?q=..."); anything else (schemes, hosts, odd characters)
     * falls back to $fallback.
     */
    private function safeReturn(string $path, string $fallback): string
    {
        $path = ltrim(trim($path), '/');

        if ($path === '' || mb_strlen($path) > 300 || preg_match('#\A[A-Za-z0-9_\-/]+(\?[A-Za-z0-9_\-=&%.+]*)?\z#', $path) !== 1) {
            $path = $fallback;
        }

        return site_url($path);
    }

    private static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
