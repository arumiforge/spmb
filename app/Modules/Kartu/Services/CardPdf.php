<?php

declare(strict_types=1);

namespace Modules\Kartu\Services;

use App\Controllers\Branding;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Modules\Pendaftaran\Models\WaveModel;
use Modules\Referensi\Models\SchoolModel;
use Throwable;

/**
 * Draws the registration card (FR-CARD-01, D-030) on the top half of an A4
 * page, with a cut line, so it prints on any school printer and is cut to
 * A5 landscape. Black on white only: it must stay readable on a black and
 * white printer. The QR is drawn as vector squares and holds only
 * site_url('kartu/<token>').
 *
 * FPDF core fonts are Latin-1: text is converted from UTF-8 (windows-1252,
 * transliterated). Rendering never writes to the database.
 */
class CardPdf
{
    /**
     * Card facts shown on the PDF, the check page and the staff lookup.
     *
     * @param array<string, mixed> $applicant applicants row
     *
     * @return array{number: string, name: string, wave: string, school: string, method: string, academicYear: string, testStatus: string}
     */
    public static function details(array $applicant): array
    {
        $wave = empty($applicant['wave_id']) ? null : model(WaveModel::class)->withDeleted()->find((int) $applicant['wave_id']);

        $school = '';

        if (! empty($applicant['previous_school_id'])) {
            $row    = model(SchoolModel::class)->find((int) $applicant['previous_school_id']);
            $school = (string) ($row['name'] ?? '');
        }

        if ($school === '') {
            $school = (string) ($applicant['previous_school_name'] ?? '');
        }

        $method = 'Status.method.' . ($applicant['method'] ?? '');

        return [
            'number'       => (string) $applicant['registration_number'],
            'name'         => (string) $applicant['full_name'],
            'wave'         => $wave === null ? '-' : (string) $wave['name'],
            'school'       => $school === '' ? '-' : $school,
            'method'       => lang($method) === $method ? '-' : lang($method),
            'academicYear' => (string) $applicant['academic_year'],
            'testStatus'   => lang('Kartu.testNotScheduled'),
        ];
    }

    /**
     * @param array<string, mixed> $applicant applicants row
     *
     * @return string PDF bytes
     */
    public function render(array $applicant, string $token): string
    {
        $card = self::details($applicant);
        $pdf  = new CardDocument('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetTitle($this->latin(lang('Kartu.pdfTitle', [$card['number']])));
        $pdf->SetAuthor($this->latin((string) setting('School.name')));
        $pdf->SetCreator('SPMB');
        $pdf->AddPage();
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);

        // Card frame (A5 landscape area on the top half of the page).
        $pdf->SetLineWidth(0.5);
        $pdf->Rect(10, 10, 190, 128.5);

        $this->header($pdf);
        $this->body($pdf, $card);
        $this->photoBox($pdf);
        $this->qr($pdf, site_url('kartu/' . $token), 155, 87, 36);

        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY(150, 124);
        $pdf->Cell(46, 3.5, $this->latin(lang('Kartu.pdfScan')), 0, 0, 'C');

        // Footer.
        $pdf->SetLineWidth(0.2);
        $pdf->Line(16, 129, 194, 129);
        $pdf->SetFont('Helvetica', 'I', 9);
        $pdf->SetXY(16, 130.5);
        $pdf->Cell(118, 5, $this->fit($pdf, (string) setting('Kartu.footerNote'), 118), 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(134, 130.5);
        $pdf->Cell(60, 5, $this->latin(lang('Kartu.pdfPrinted', [tanggal_indo(date('Y-m-d'))])), 0, 0, 'R');

        $this->cutLine($pdf, 148.5);

        return $pdf->Output('S');
    }

    private function header(CardDocument $pdf): void
    {
        $textX = 16;

        if ($this->logo($pdf, 16, 15, 20)) {
            $textX = 40;
        }

        $width = 194 - $textX;
        $pdf->SetFont('Helvetica', 'B', 15);
        $pdf->SetXY($textX, 15.5);
        $pdf->Cell($width, 7, $this->fit($pdf, (string) setting('School.name'), $width), 0, 2, 'L');
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell($width, 6, $this->fit($pdf, (string) setting('Kartu.headerText'), $width), 0, 2, 'L');
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->Cell($width, 5, $this->latin(lang('Kartu.pdfAcademicYear', [(string) setting('Pendaftaran.academicYear')])), 0, 2, 'L');

        $pdf->SetLineWidth(0.4);
        $pdf->Line(16, 39, 194, 39);
    }

    /**
     * @param array{number: string, name: string, wave: string, school: string, method: string, academicYear: string, testStatus: string} $card
     */
    private function body(CardDocument $pdf, array $card): void
    {
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY(16, 43);
        $pdf->Cell(130, 4, $this->latin(mb_strtoupper(lang('Kartu.number'))), 0, 2, 'L');
        $pdf->SetFont('Courier', 'B', 20);
        $pdf->Cell(130, 9, $this->fit($pdf, $card['number'], 130), 0, 2, 'L');

        $rows = [
            lang('Kartu.name')       => $card['name'],
            lang('Kartu.wave')       => $card['wave'],
            lang('Kartu.school')     => $card['school'],
            lang('Kartu.method')     => $card['method'],
            lang('Kartu.testStatus') => $card['testStatus'],
        ];
        $y = 60;

        foreach ($rows as $label => $value) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetXY(16, $y);
            $pdf->Cell(34, 8, $this->latin($label), 0, 0, 'L');
            $pdf->Cell(4, 8, ':', 0, 0, 'L');
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->Cell(92, 8, $this->fit($pdf, $value, 92), 0, 0, 'L');
            $pdf->SetLineWidth(0.1);
            $pdf->SetDrawColor(150, 150, 150);
            $pdf->Line(16, $y + 8, 146, $y + 8);
            $pdf->SetDrawColor(0, 0, 0);
            $y += 11;
        }
    }

    private function photoBox(CardDocument $pdf): void
    {
        $pdf->SetLineWidth(0.2);
        $pdf->Rect(158, 43, 30, 40);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetXY(158, 59);
        $pdf->MultiCell(30, 3.5, $this->latin(lang('Kartu.pdfPhoto')), 0, 'C');
    }

    /**
     * Vector QR code: crisp at any printer resolution, no temporary files.
     */
    private function qr(CardDocument $pdf, string $data, float $x, float $y, float $size): void
    {
        $options = new QROptions(['eccLevel' => EccLevel::M, 'addQuietzone' => false]);
        $matrix  = (new QRCode($options))->addByteSegment($data)->getQRMatrix()->getMatrix(true);
        $count   = count($matrix);
        $module  = $size / $count;

        foreach ($matrix as $row => $cells) {
            $start = null;

            foreach ([...$cells, false] as $col => $dark) {
                if ($dark && $start === null) {
                    $start = $col;
                } elseif (! $dark && $start !== null) {
                    $pdf->Rect($x + $start * $module, $y + $row * $module, ($col - $start) * $module + 0.01, $module + 0.01, 'F');
                    $start = null;
                }
            }
        }
    }

    /**
     * School logo from settings, re-encoded to a plain PNG (FPDF reads neither
     * WebP nor interlaced or 16-bit PNG). Skipped quietly when it cannot be read.
     */
    private function logo(CardDocument $pdf, float $x, float $y, float $size): bool
    {
        $name = basename((string) setting('School.logoPath'));
        $file = Branding::DIR . $name;

        if ($name === '' || ! is_file($file) || ! extension_loaded('gd')) {
            return false;
        }

        $tmp = WRITEPATH . 'cache/kartu-logo-' . bin2hex(random_bytes(6)) . '.png';

        try {
            $image = imagecreatefromstring((string) file_get_contents($file));

            if ($image === false) {
                return false;
            }

            imagesavealpha($image, true);
            imageinterlace($image, false);
            imagepng($image, $tmp);
            $width  = imagesx($image);
            $height = imagesy($image);

            // Fit inside a $size square, keeping the aspect ratio.
            $scale = $size / max($width, $height, 1);
            $w     = $width * $scale;
            $h     = $height * $scale;
            $pdf->Image($tmp, $x + ($size - $w) / 2, $y + ($size - $h) / 2, $w, $h, 'PNG');

            return true;
        } catch (Throwable $e) {
            log_message('warning', 'Card logo skipped: ' . $e->getMessage());

            return false;
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
    }

    private function cutLine(CardDocument $pdf, float $y): void
    {
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(120, 120, 120);

        for ($x = 5.0; $x < 205; $x += 4) {
            $pdf->Line($x, $y, min($x + 2, 205), $y);
        }

        $label = $this->latin(lang('Kartu.pdfCut'));
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(90, 90, 90);
        $width = $pdf->GetStringWidth($label) + 8;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetXY((210 - $width) / 2, $y - 2);
        $pdf->Cell($width, 4, $label, 0, 0, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetDrawColor(0, 0, 0);
    }

    /**
     * Latin-1 text that fits $width at the current font: shrinks the font down
     * to 7 pt, then shortens with "...".
     */
    private function fit(CardDocument $pdf, string $text, float $width): string
    {
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
