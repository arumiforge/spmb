<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\HtmlMinifier;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Minifies HTML responses on the way out (D-094). Downloads, JSON, PDFs and redirects pass unchanged.
 */
class MinifyHtml implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $type = $response->getHeaderLine('Content-Type');
        $body = $response->getBody();

        if (! is_string($body) || $body === '' || ($type !== '' && ! str_contains(strtolower($type), 'text/html'))) {
            return null;
        }

        return $response->setBody(HtmlMinifier::minify($body));
    }
}
