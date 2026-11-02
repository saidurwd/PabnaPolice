<?php

namespace App\Models;

use App\Support\SiteCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $name
 * @property string|null $value
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Setting extends Model
{
    protected $guarded = ['id'];

    private static ?array $loaded = null;

    /** Read a setting, falling back to the default declared in config/site.php. */
    public static function get(string $name, mixed $default = null): mixed
    {
        self::$loaded ??= Cache::remember('site.settings', 600, fn () => self::pluck('value', 'name')->all());

        if (array_key_exists($name, self::$loaded) && self::$loaded[$name] !== null) {
            return self::$loaded[$name];
        }

        foreach (config('site') as $fields) {
            if (isset($fields[$name])) {
                return $fields[$name]['default'];
            }
        }

        return $default;
    }

    public static function put(string $name, ?string $value): void
    {
        self::updateOrCreate(['name' => $name], ['value' => $value]);
        self::flush();
    }

    public static function flush(): void
    {
        self::$loaded = null;
        Cache::forget('site.settings');
        SiteCache::flush();
    }
}
