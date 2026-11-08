<?php

namespace App\Models;

use App\Support\Logs\UserAgent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $login_time
 * @property string|null $logout_time
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class AuditTrail extends BaseModel
{
    public $timestamps = false;

    /** The user may have been deleted since (there is deliberately no foreign key). */
    /** "Chrome on Windows (Desktop)" — what the User-Agent header says. */
    public function getBrowserAttribute(): string
    {
        return UserAgent::label($this->user_agent);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
