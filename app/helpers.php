<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('upload_url')) {
    /** URL of a file in public/uploads/{dir}, or null when the name is empty or the file is missing. */
    function upload_url(string $dir, ?string $file): ?string
    {
        if (empty($file) || ! Storage::disk('uploads')->exists("{$dir}/{$file}")) {
            return null;
        }

        return asset("uploads/{$dir}/".rawurlencode($file));
    }
}

if (! function_exists('setting')) {
    function setting(string $name, mixed $default = null): mixed
    {
        return Setting::get($name, $default);
    }
}

if (! function_exists('legacy_date')) {
    /** Same format the Yii site used for published dates. */
    function legacy_date(DateTimeInterface|string|null $date): ?string
    {
        if ($date instanceof DateTimeInterface) {
            return $date->format('M j, Y, g:i:s A');
        }
        if (empty($date) || str_starts_with($date, '0000-00-00')) {
            return null;
        }

        return date('M j, Y, g:i:s A', strtotime($date));
    }
}

if (! function_exists('str_words_limit')) {
    function str_words_limit(?string $value, int $words = 30, string $end = '...'): string
    {
        $value = (string) $value;
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $m);

        if (! isset($m[0]) || mb_strlen($value) === mb_strlen($m[0])) {
            return $value;
        }

        return rtrim($m[0]).$end;
    }
}

if (! function_exists('str_chars_limit')) {
    function str_chars_limit(?string $value, int $limit = 100, string $end = '...'): string
    {
        $value = (string) $value;

        return mb_strwidth($value, 'UTF-8') <= $limit ? $value : rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')).$end;
    }
}

if (! function_exists('enum_value')) {
    /** Plain value of a backed enum (or the value itself), for views and comparisons. */
    function enum_value(mixed $value): mixed
    {
        return $value instanceof BackedEnum ? $value->value : $value;
    }
}

if (! function_exists('theme_asset')) {
    /** URL of a file under public/assets with a ?v=<mtime> suffix, so browsers refetch it after a change. */
    function theme_asset(string $path): string
    {
        $file = public_path('assets/'.$path);

        return asset('assets/'.$path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
