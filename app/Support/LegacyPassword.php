<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * Passwords in the live `user` table are unsalted SHA1 hex strings (Yii 1 app). Both that and
 * bcrypt are accepted so nobody is locked out. New hashes stay SHA1 (so the old admin keeps
 * working during the transition) until LEGACY_PASSWORD_UPGRADE=true is set after cutover.
 */
class LegacyPassword
{
    public static function check(string $plain, string $stored): bool
    {
        if (str_starts_with($stored, '$2y$')) {
            return Hash::check($plain, $stored);
        }

        return hash_equals(strtolower($stored), sha1($plain));
    }

    public static function make(string $plain): string
    {
        return self::upgradeEnabled() ? Hash::make($plain) : sha1($plain);
    }

    public static function needsUpgrade(string $stored): bool
    {
        return self::upgradeEnabled() && ! str_starts_with($stored, '$2y$');
    }

    public static function upgradeEnabled(): bool
    {
        return (bool) config('legacy.password_upgrade');
    }
}
