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
     * Largest photo accepted before resizing, in KB. Phone cameras produce
     * 3–8 MB JPEGs; after resizing they must fit the document type's own
     * limit (document_types.max_size_kb). PDFs are not resized, so for them
     * the type's limit applies to the uploaded file. The web server must allow
     * uploads of this size (php.ini upload_max_filesize / post_max_size,
     * nginx client_max_body_size).
     */
    public int $imageUploadLimitKb = 10240;

    /**
     * Last day documents marked "menyusul" may be handed in ('' = no deadline).
     * Pending the school's answer. Format Y-m-d.
     */
    public string $deadline = '';
}
