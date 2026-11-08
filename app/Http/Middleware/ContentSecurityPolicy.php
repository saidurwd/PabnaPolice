<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Content-Security-Policy, shipped in report-only mode first (see config/security.php).
 *
 * 'unsafe-inline' is still allowed for scripts and styles because the original public theme and the admin use inline
 * handlers; the reports show what else the pages load, which is the input for tightening this later.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $mode = config('security.csp_mode');
        if (! in_array($mode, ['report-only', 'enforce'], true)) {
            return $response;
        }

        $response->headers->set(
            $mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only',
            $this->policy()
        );

        return $response;
    }

    private function policy(): string
    {
        $extra = trim((string) config('security.csp_extra_sources'));
        $with = fn (string $base) => trim($base.' '.$extra);

        return implode('; ', [
            "default-src 'self'",
            $with("script-src 'self' 'unsafe-inline'"),
            $with("style-src 'self' 'unsafe-inline' https://fonts.googleapis.com"),
            $with("font-src 'self' data: https://fonts.gstatic.com"),
            $with("img-src 'self' data: blob: https:"),
            $with("connect-src 'self'"),
            "frame-src 'self' https://www.google.com https://www.youtube.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            'report-uri '.url('/csp-report'),
        ]);
    }
}
