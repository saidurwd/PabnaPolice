<?php

return [
    // Read by Laravel's TrustProxies middleware on every request.
    // Reverse proxy / load balancer addresses (comma separated), or * to trust the caller; empty = not behind one.
    // Without this, behind a proxy the site sees the proxy's IP and thinks HTTPS requests are plain HTTP.
    'proxies' => env('TRUSTED_PROXIES'),
];
