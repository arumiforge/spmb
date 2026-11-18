<?php

declare(strict_types=1);

namespace Modules\Kartu\Services;

use FPDF;

/**
 * FPDF with a reader for the current font size (used to shrink long names to fit).
 */
class CardDocument extends FPDF
{
    public function fontSize(): float
    {
        return (float) $this->FontSizePt;
    }
}
