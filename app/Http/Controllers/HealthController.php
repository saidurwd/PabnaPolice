<?php

namespace App\Http\Controllers;

use App\Support\HealthCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /health          -> {"status":"ok"} (HTTP 200) or {"status":"fail"} (HTTP 503), for uptime monitors.
 * GET /health?token=.. -> the same plus every check's message, when the token equals HEALTH_TOKEN.
 */
class HealthController extends Controller
{
    public function __invoke(Request $request, HealthCheck $health): JsonResponse
    {
        $results = $health->run();
        $overall = HealthCheck::overall($results);

        $body = ['status' => $overall];
        $token = (string) config('app.health_token');
        if ($token !== '' && hash_equals($token, (string) $request->query('token'))) {
            $body['checks'] = $results;
        }

        return response()->json($body, $overall === 'fail' ? 503 : 200);
    }
}
