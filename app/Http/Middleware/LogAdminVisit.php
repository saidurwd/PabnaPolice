<?php

namespace App\Http\Middleware;

use App\Jobs\RecordAdminVisit;
use Closure;
use Illuminate\Http\Request;

/** Logs admin page views. Dispatched after the response so it never slows a page down. */
class LogAdminVisit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('GET') && ! $request->ajax() && $request->user()) {
            RecordAdminVisit::dispatchAfterResponse([
                'user_type' => 1,
                'user_id' => $request->user()->id,
                'user_name' => $request->user()->username,
                'page_title' => mb_substr((string) $request->route()?->getName(), 0, 255),
                'page_link' => mb_substr($request->getRequestUri(), 0, 150),
                'server_time' => now(),
                'browser' => mb_substr((string) $request->userAgent(), 0, 100),
                'visitor_ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
