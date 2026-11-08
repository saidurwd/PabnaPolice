<?php

namespace App\Models\Concerns;

use App\Support\Logs\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Records every create / change / delete / restore of the model in the Audit log, with the old and new values.
 * Only changes made by a signed-in user are logged (so migrations, seeders and artisan commands stay out of it).
 * Passwords, secrets and housekeeping columns are never written down; long text is shortened.
 */
trait Auditable
{
    /** Columns that are never logged. */
    private static array $auditHidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'created_at', 'updated_at', 'deleted_at', 'last_login_at'];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => self::auditEvent('created', $m, [], $m->getAttributes()));
        static::updated(function (Model $m) {
            $changes = array_diff_key($m->getChanges(), array_flip(self::$auditHidden));
            if ($changes) {
                self::auditEvent('updated', $m, array_intersect_key($m->getOriginal(), $changes), $changes);
            }
        });
        static::deleted(fn (Model $m) => self::auditEvent(
            method_exists($m, 'isForceDeleting') && ! $m->isForceDeleting() ? 'trashed' : 'deleted', $m, $m->getAttributes(), []
        ));
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::registerModelEvent('restored', fn (Model $m) => self::auditEvent('restored', $m, [], []));
        }
    }

    private static function auditEvent(string $event, Model $m, array $old, array $new): void
    {
        if (! auth()->check()) {
            return;
        }

        $clean = fn (array $a) => array_map(
            fn ($v) => is_string($v) ? mb_substr($v, 0, 300) : (is_scalar($v) || $v === null ? $v : (string) json_encode($v)),
            array_diff_key($a, array_flip(self::$auditHidden))
        );

        $kind = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', class_basename($m)));
        $label = Activity::label($m);

        Activity::audit($event, ucfirst($kind)." \"{$label}\" {$event}", array_filter(['old' => $clean($old), 'new' => $clean($new)]), $m);
    }
}
