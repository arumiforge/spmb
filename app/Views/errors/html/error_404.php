<?php

/**
 * 404 page when the router's override (App\Controllers\Errors::notFound) was not used.
 */
echo App\Controllers\Errors::render(404);
