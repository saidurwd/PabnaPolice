<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/** Receives the browser's Content-Security-Policy violation reports and writes them to storage/logs/csp-*.log. */
class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $report = json_decode(mb_substr((string) $request->getContent(), 0, 4000), true);
        $r = is_array($report) ? ($report['csp-report'] ?? $report) : [];

        Log::channel('csp')->warning('CSP violation', array_filter([
            'directive' => $r['effective-directive'] ?? $r['violated-directive'] ?? null,
            'blocked' => $r['blocked-uri'] ?? null,
            'page' => $r['document-uri'] ?? null,
            'source' => $r['source-file'] ?? null,
            'line' => $r['line-number'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        return response()->noContent();
    }
}
