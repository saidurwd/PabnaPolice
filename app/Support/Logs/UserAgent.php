<?php

namespace App\Support\Logs;

/** Reads a browser name, operating system and device type out of a User-Agent header (good enough for a log). */
class UserAgent
{
    /** @return array{browser: string, os: string, device: string} */
    public static function parse(?string $ua): array
    {
        $ua = (string) $ua;
        if ($ua === '') {
            return ['browser' => 'Unknown', 'os' => 'Unknown', 'device' => 'Unknown'];
        }

        $browser = 'Other';
        foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari', 'MSIE' => 'Internet Explorer', 'Trident' => 'Internet Explorer', 'curl' => 'curl'] as $needle => $name) {
            if (str_contains($ua, $needle)) {
                $browser = $name;
                break;
            }
        }

        $os = 'Other';
        foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Mac OS X' => 'macOS', 'Linux' => 'Linux'] as $needle => $name) {
            if (str_contains($ua, $needle)) {
                $os = $name;
                break;
            }
        }

        $device = preg_match('/Mobile|Android|iPhone/i', $ua) ? 'Mobile' : (preg_match('/iPad|Tablet/i', $ua) ? 'Tablet' : 'Desktop');

        return ['browser' => $browser, 'os' => $os, 'device' => $device];
    }

    public static function label(?string $ua): string
    {
        $p = self::parse($ua);

        return $p['browser'] === 'Unknown' ? 'Unknown' : "{$p['browser']} on {$p['os']} ({$p['device']})";
    }
}
