<?php

namespace App\Support\Logs;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes the Audit log and Activity log. Logging must never break the page it is called from, so every failure is
 * swallowed (and sent to the normal application log instead).
 */
class Activity
{
    /** What users did: sign-ins, settings saved, passwords changed, uploads ... */
    public static function record(string $event, string $description, array $properties = [], ?Model $subject = null): void
    {
        self::write('activity', $event, $description, $properties, $subject);
    }

    /** A data change, with the old and new values (called by the Auditable trait). */
    public static function audit(string $event, string $description, array $properties, Model $subject): void
    {
        self::write('audit', $event, $description, $properties, $subject);
    }

    private static function write(string $log, string $event, string $description, array $properties, ?Model $subject): void
    {
        try {
            $user = Auth::user();
            $request = request();

            ActivityLog::create([
                'log' => $log,
                'event' => $event,
                'user_id' => $user?->id,
                'user_name' => $user?->full_name ?: $user?->username,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject ? self::label($subject) : null,
                'description' => mb_substr($description, 0, 500),
                'properties' => $properties ?: null,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Activity log write failed: '.$e->getMessage());
        }
    }

    /** A short human name for a record: its title / name, else "#id". */
    public static function label(Model $m): string
    {
        foreach (['title', 'full_name', 'name', 'username', 'alias'] as $attr) {
            if (filled($m->getAttribute($attr))) {
                return mb_substr(strip_tags((string) $m->getAttribute($attr)), 0, 120);
            }
        }

        return '#'.$m->getKey();
    }
}
