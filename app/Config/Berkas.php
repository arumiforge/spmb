<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Document upload defaults (module Berkas). Real values: setting('Berkas.<property>').
 * The list of documents itself is data (table document_types), not config.
 */
class Berkas extends BaseConfig
{
    /**
     * Images are resized so the longest side is at most this many pixels, then
     * saved as JPEG with this quality (FR-DOC-02).
     */
    public int $imageMaxEdge = 1600;

    public int $imageQuality = 80;

    /**
     * Last day documents marked "menyusul" may be handed in ('' = no deadline).
     * Pending the school's answer.
     */
    public string $deadline = '';
}
