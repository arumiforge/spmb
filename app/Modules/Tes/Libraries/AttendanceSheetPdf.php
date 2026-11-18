<?php

declare(strict_types=1);

namespace Modules\Tes\Libraries;

use App\Controllers\Branding;
use FPDF;
use Throwable;

/**
 * Attendance sheet of one test session (FR-TEST-03): school header, session
 * facts, a table (no., registration number, name, gender, signature, note)
 * and totals with lines for the room supervisor. A4 portrait, black on white,
 * the table header repeats on every page. Same text handling as
 * Modules\Kartu\Services\CardPdf (FPDF core fonts are Latin-1).
 */
class AttendanceSheetPdf
{
    private const LEFT   = 15.0;
    private const WIDTH  = 180.0;
    private const ROW    = 9.0;
    private const BOTTOM = 270.0;

    /**
     * Column widths in mm (sum = WIDTH).
     *
     * @var array<string, float>
     */
    private const COLS = ['no' => 10, 'number' => 36, 'name' => 66, 'gender' => 10, 'signature' => 42, 'note' => 16];

    private FPDF $pdf;

    /**
     * @param array<string, mixed>       $session TestSessionModel::findWithStats() row
     * @param list<array<string, mixed>> $rows    TestAssignmentModel::forSession() rows
     *
     * @return string PDF bytes
     */
    public function render(array $session, array $rows): string
    {
        $footer = [
            'printed' => $this->latin(lang('TesKehadiran.pdfPrinted', [tanggal_indo(date('Y-m-d'))]) . ' · ' . TestSchedule::sessionLabel($session)),
            'page'    => $this->latin(lang('TesKehadiran.pdfPage', ['{PAGE}', '{nb}'])),
        ];

        $this->pdf = new class ($footer) extends FPDF {
            /**
             * @param array{printed: string, page: string} $footer
             */
            public function __construct(private readonly array $footer)
            {
                parent::__construct('P', 'mm', 'A4');
            }

            public function Footer(): void
            {
                $this->SetY(-14);
                $this->SetFont('Helvetica', '', 8);
                $this->SetTextColor(80, 80, 80);
                $this->Cell(120, 5, $this->footer['printed'], 0, 0, 'L');
                $this->Cell(0, 5, str_replace('{PAGE}', (string) $this->PageNo(), $this->footer['page']), 0, 0, 'R');
                $this->SetTextColor(0, 0, 0);
            }

            public function fontSize(): float
            {
                return (float) $this->FontSizePt;
            }
        };

        $pdf = $this->pdf;
        $pdf->AliasNbPages();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(self::LEFT, 15, self::LEFT);
        $pdf->SetTitle($this->latin(lang('TesKehadiran.pdfTitle', [TestSchedule::sessionLabel($session)])));
        $pdf->SetAuthor($this->latin((string) setting('School.name')));
        $pdf->SetCreator('SPMB');
        $pdf->AddPage();

        $this->header($session);
        $this->tableHeader();

        $male   = 0;
        $female = 0;

        foreach ($rows as $i => $row) {
            if ($pdf->GetY() + self::ROW > self::BOTTOM) {
                $pdf->AddPage();
                $this->tableHeader();
            }

            $this->row($i + 1, $row);
            $row['gender'] === 'L' ? $male++ : $female++;
        }

        if ($rows === []) {
            $pdf->SetFont('Helvetica', 'I', 10);
            $pdf->Cell(self::WIDTH, 12, $this->latin(lang('TesKehadiran.pdfEmpty')), 1, 1, 'C');
        }

        if ($pdf->GetY() + 48 > self::BOTTOM + 10) {
            $pdf->AddPage();
        }

        $this->totals(count($rows), $male, $female);

        return $pdf->Output('S');
    }

    /**
     * @param array<string, mixed> $session
     */
    private function header(array $session): void
    {
        $pdf   = $this->pdf;
        $textX = self::LEFT;

        if ($this->logo(self::LEFT, 14, 18)) {
            $textX = self::LEFT + 23;
        }

        $width = self::LEFT + self::WIDTH - $textX;
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->SetXY($textX, 15);
        $pdf->Cell($width, 7, $this->fit((string) setting('School.name'), $width), 0, 2, 'L');
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell($width, 6, $this->latin(lang('TesKehadiran.pdfHeading')), 0, 2, 'L');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell($width, 5, $this->latin(lang('TesKehadiran.pdfYear', [(string) $session['academic_year']])), 0, 2, 'L');

        $pdf->SetLineWidth(0.4);
        $pdf->Line(self::LEFT, 36, self::LEFT + self::WIDTH, 36);

        $facts = [
            lang('TesKehadiran.pdfDay')  => TestSchedule::dayLabel((string) $session['test_date']),
            lang('TesKehadiran.pdfTime') => TestSchedule::timeLabel((string) $session['starts_at'], (string) $session['ends_at']),
            lang('TesKehadiran.pdfRoom') => trim($session['room_name'] . ((string) ($session['room_location'] ?? '') !== '' ? ' (' . $session['room_location'] . ')' : '')),
        ];

        if ((string) ($session['label'] ?? '') !== '') {
            $facts = [lang('TesKehadiran.pdfSession') => (string) $session['label']] + $facts;
        }

        $pdf->SetY(39);

        foreach ($facts as $label => $value) {
            $pdf->SetX(self::LEFT);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->Cell(30, 6, $this->latin($label), 0, 0, 'L');
            $pdf->Cell(4, 6, ':', 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 10);
            $pdf->Cell(self::WIDTH - 34, 6, $this->fit($value, self::WIDTH - 34), 0, 1, 'L');
        }

        $pdf->Ln(3);
    }

    private function tableHeader(): void
    {
        $pdf    = $this->pdf;
        $labels = lang('TesKehadiran.pdfCols');
        $pdf->SetX(self::LEFT);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetLineWidth(0.2);

        foreach (self::COLS as $key => $width) {
            $pdf->Cell($width, 8, $this->latin($labels[$key]), 1, 0, 'C', true);
        }

        $pdf->Ln();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function row(int $number, array $row): void
    {
        $pdf    = $this->pdf;
        $status = (string) $row['status'];
        $note   = in_array($status, ['present', 'absent'], true) ? lang('Status.test.' . $status) : '';

        $pdf->SetX(self::LEFT);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(self::COLS['no'], self::ROW, (string) $number, 1, 0, 'C');
        $pdf->SetFont('Courier', 'B', 9);
        $pdf->Cell(self::COLS['number'], self::ROW, $this->fit((string) $row['registration_number'], self::COLS['number'] - 2), 1, 0, 'L');
        $pdf->SetFont('Helvetica', '', 9.5);
        $pdf->Cell(self::COLS['name'], self::ROW, $this->fit((string) $row['full_name'], self::COLS['name'] - 2), 1, 0, 'L');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell(self::COLS['gender'], self::ROW, $this->latin((string) $row['gender']), 1, 0, 'C');

        // Signatures alternate left and right so neighbours do not overlap (common school practice).
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Cell(self::COLS['signature'], self::ROW, '', 1, 0);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY($x + ($number % 2 === 1 ? 1 : self::COLS['signature'] / 2), $y + 1);
        $pdf->Cell(8, 3, $number . '.', 0, 0, 'L');
        $pdf->SetXY($x + self::COLS['signature'], $y);

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->Cell(self::COLS['note'], self::ROW, $this->fit($note, self::COLS['note'] - 1), 1, 1, 'C');
    }

    private function totals(int $total, int $male, int $female): void
    {
        $pdf = $this->pdf;
        $pdf->Ln(5);
        $pdf->SetX(self::LEFT);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(110, 6, $this->latin(lang('TesKehadiran.pdfTotals', [$total, $male, $female])), 0, 2, 'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(110, 7, $this->latin(lang('TesKehadiran.pdfFill')), 0, 0, 'L');

        $signX = self::LEFT + self::WIDTH - 62;
        $top   = $pdf->GetY() - 6;
        $pdf->SetXY($signX, $top);
        $pdf->Cell(62, 6, $this->latin(lang('TesKehadiran.pdfPlace')), 0, 2, 'C');
        $pdf->Cell(62, 6, $this->latin(lang('TesKehadiran.pdfSupervisor')), 0, 2, 'C');
        $pdf->SetXY($signX, $top + 32);
        $pdf->Cell(62, 6, $this->latin(lang('TesKehadiran.pdfSignLine')), 0, 2, 'C');
    }

    /**
     * School logo re-encoded to a plain PNG (as on the card); skipped quietly when unreadable.
     */
    private function logo(float $x, float $y, float $size): bool
    {
        $name = basename((string) setting('School.logoPath'));
        $file = Branding::DIR . $name;

        if ($name === '' || ! is_file($file) || ! extension_loaded('gd')) {
            return false;
        }

        $tmp = WRITEPATH . 'cache/daftar-hadir-logo-' . bin2hex(random_bytes(6)) . '.png';

        try {
            $image = imagecreatefromstring((string) file_get_contents($file));

            if ($image === false) {
                return false;
            }

            imagesavealpha($image, true);
            imageinterlace($image, false);
            imagepng($image, $tmp);
            $scale = $size / max(imagesx($image), imagesy($image), 1);
            $w     = imagesx($image) * $scale;
            $h     = imagesy($image) * $scale;
            $this->pdf->Image($tmp, $x + ($size - $w) / 2, $y + ($size - $h) / 2, $w, $h, 'PNG');

            return true;
        } catch (Throwable $e) {
            log_message('warning', 'Attendance sheet logo skipped: ' . $e->getMessage());

            return false;
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
    }

    /**
     * Latin-1 text that fits $width at the current font: shrinks down to 7 pt, then shortens with "...".
     */
    private function fit(string $text, float $width): string
    {
        $pdf  = $this->pdf;
        $text = $this->latin($text);

        while ($pdf->GetStringWidth($text) > $width && $pdf->fontSize() > 7) {
            $pdf->SetFontSize($pdf->fontSize() - 0.5);
        }

        if ($pdf->GetStringWidth($text) <= $width) {
            return $text;
        }

        while ($text !== '' && $pdf->GetStringWidth($text . '...') > $width) {
            $text = substr($text, 0, -1);
        }

        return rtrim($text) . '...';
    }

    /**
     * UTF-8 → windows-1252 for FPDF core fonts; unknown characters become "?".
     */
    private function latin(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);

        return $converted === false ? (string) mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8') : $converted;
    }
}
