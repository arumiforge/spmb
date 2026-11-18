<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Registration card defaults (module Kartu). Real values: setting('Kartu.<property>').
 */
class Kartu extends BaseConfig
{
    /**
     * When applicants can download the card (FR-CARD-04): 'immediately' after
     * registering, or 'verified' after the committee's first check. Pending the
     * committee's decision; staff can always print.
     */
    public string $availableWhen = 'immediately';

    /**
     * Title printed at the top of the card (school name and logo come from School.*).
     */
    public string $headerText = 'Kartu Pendaftaran Murid Baru';

    /**
     * Short note printed at the bottom of the card.
     */
    public string $footerNote = 'Bawa kartu ini saat tes dan daftar ulang.';
}
