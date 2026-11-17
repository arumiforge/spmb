<?php

/**
 * Error page shown to visitors when something breaks (display_errors off).
 * Never shows technical details; see App\Controllers\Errors.
 */
echo App\Controllers\Errors::render(500);
