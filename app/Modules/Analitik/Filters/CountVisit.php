<?php

declare(strict_types=1);

namespace Modules\Analitik\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Analitik\Libraries\VisitRecorder;

/**
 * Global after-filter `analitik`: counts public page views while the Analitik
 * module is on (rules in VisitRecorder). Never changes the response.
 */
class CountVisit implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (service('modules')->isEnabled('Analitik')) {
            (new VisitRecorder())->handle($request, $response);
        }

        return null;
    }
}
