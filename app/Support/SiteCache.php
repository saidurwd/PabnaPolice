<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/** The public layout's shared data (menu, side panels, footer links) is cached; flush it after any admin change. */
class SiteCache
{
    public const SHARED_KEY = 'front.shared';

    public const SITEMAP_KEY = 'front.sitemap';

    public const TTL = 600;

    public static function flush(): void
    {
        Cache::forget(self::SHARED_KEY);
        Cache::forget(self::SITEMAP_KEY);
    }
}
