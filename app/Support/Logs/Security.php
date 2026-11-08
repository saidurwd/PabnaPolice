<?php

namespace App\Support\Logs;

use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Writes the Security events log. Like Activity, it never throws. */
class Security
{
    public const INFO = 'info';

    public const WARNING = 'warning';

    public const CRITICAL = 'critical';

    /** Human labels for the event types (also the list in the filter box). */
    public const TYPES = [
        'login_failed' => 'Failed sign-in',
        'login_locked' => 'Sign-in locked (too many attempts)',
        'login_blocked' => 'Sign-in refused (account state)',
        'login_new_ip' => 'Sign-in from a new IP address',
        'two_factor_failed' => 'Wrong two-factor code',
        'recovery_code_used' => 'Recovery code used',
        'two_factor_enabled' => 'Two-factor turned on',
        'two_factor_disabled' => 'Two-factor turned off',
        'password_changed' => 'Password changed',
        'password_reset_requested' => 'Password reset requested',
        'password_reset_completed' => 'Password reset completed',
        'permission_denied' => 'Access denied (no permission)',
        'role_changed' => 'Role or permissions changed',
    ];

    public static function record(string $type, string $severity, string $message, ?int $userId = null, ?string $identifier = null, array $details = []): void
    {
        try {
            $request = request();

            SecurityEvent::create([
                'type' => $type,
                'severity' => $severity,
                'user_id' => $userId ?? $request->user()?->id,
                'identifier' => $identifier ? mb_substr($identifier, 0, 150) : null,
                'message' => mb_substr($message, 0, 500),
                'details' => $details ?: null,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Security event write failed: '.$e->getMessage());
        }
    }
}
