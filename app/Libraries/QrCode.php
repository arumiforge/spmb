<?php

declare(strict_types=1);

namespace App\Libraries;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as Generator;
use chillerlan\QRCode\QROptions;

/**
 * QR codes as inline SVG data URIs (no image files, no external service).
 * Used for 2FA setup now and registration cards later (those carry a random token only).
 */
class QrCode
{
    public static function svgDataUri(string $data): string
    {
        $options = new QROptions([
            'outputType'       => QROutputInterface::MARKUP_SVG,
            'outputBase64'     => true,
            'addQuietzone'     => true,
            'svgAddXmlHeader'  => false,
            'drawLightModules' => false,
            'connectPaths'     => true,
        ]);

        return (new Generator($options))->render($data);
    }
}
