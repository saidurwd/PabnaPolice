<?php

return [
    /*
    | Content-Security-Policy mode:
    |   report-only  (default) the browser only reports violations to /csp-report; nothing is blocked
    |   enforce      violations are blocked
    |   off          no header
    | Run report-only for a few weeks, read storage/logs/csp-*.log, fix or allow what is legitimate, then enforce.
    */
    'csp_mode' => env('CSP_MODE', 'report-only'),

    // Extra hosts the site legitimately loads from, space separated (added to script/style/img/font/connect sources).
    'csp_extra_sources' => env('CSP_EXTRA_SOURCES', ''),
];
